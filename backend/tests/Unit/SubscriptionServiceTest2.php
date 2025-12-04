<?php
// V-FINAL-1730-TEST-78 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SubscriptionService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(SubscriptionService::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class); // For KYC toggle

        $this->user = User::factory()->create();
        $this->user->kyc->update(['status' => 'verified']);
        $this->plan = Plan::factory()->create(['monthly_amount' => 1000]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_create_kyc_pending_if_required()
    {
        $this->user->kyc->update(['status' => 'pending']);
        Setting::updateOrCreate(['key' => 'kyc_required_for_investment'], ['value' => 'true']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("KYC must be verified");
        
        $this->service->createSubscription($this->user, $this->plan);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_create_with_inactive_plan()
    {
        $this->plan->update(['is_active' => false]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Plan '{$this->plan->name}' is not currently available.");

        $this->service->createSubscription($this->user, $this->plan);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_pause_max_pause_count_exceeded()
    {
        $this->plan->update(['max_pause_count' => 2]);
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'pause_count' => 2 // Already used 2
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("You have reached the maximum of 2 pause requests");

        $this->service->pauseSubscription($sub, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_pause_during_payment_processing()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
        Payment::factory()->create(['subscription_id' => $sub->id, 'status' => 'pending']);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot pause while a payment is pending");

        $this->service->pauseSubscription($sub, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_upgrade_prorated_calculation_exact()
    {
        $this->travelTo(Carbon::parse('2025-01-15')); // Mid-month
        
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id, // 1000/mo
            'status' => 'active',
            'next_payment_date' => Carbon::parse('2025-01-31')
        ]);
        
        // Cycle is 30 days (Jan 1 to Jan 31). 16 days remain.
        $newPlan = Plan::factory()->create(['monthly_amount' => 4000]); // Diff=3000
        
        // Proration: (3000 / 30 days) * 16 days = 1600
        $proratedAmount = $this->service->upgradePlan($sub, $newPlan);

        $this->assertEquals(1600, $proratedAmount);
        $this->assertDatabaseHas('payments', [
            'subscription_id' => $sub->id,
            'amount' => 1600,
            'status' => 'pending'
        ]);

        $this->travelBack();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_cancel_with_pending_payments()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
        $pending = Payment::factory()->create(['subscription_id' => $sub->id, 'status' => 'pending']);
        
        $this->service->cancelSubscription($sub, "Test");

        $this->assertEquals('cancelled', $sub->fresh()->status);
        $this->assertEquals('failed', $pending->fresh()->status);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_next_payment_date_calculation_leap_year()
    {
        $this->travelTo(Carbon::parse('2024-02-01')); // Leap Year
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'next_payment_date' => '2024-02-29'
        ]);
        
        // This logic is in PaymentWebhookService, but we test the model
        $newDate = $sub->next_payment_date->addMonth();
        
        // addMonth() on Feb 29 2024 results in Mar 29 2024
        $this->assertEquals('2024-03-29', $newDate->toDateString());
    }
}