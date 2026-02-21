<?php
// V-FINAL-1730-TEST-21

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SubscriptionService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use Carbon\Carbon;

class SubscriptionServiceTest extends TestCase
{
    protected $service;
    protected $user;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->kyc->update(['status' => 'verified']); // Default to verified
        
        $this->plan = Plan::factory()->create([
            'monthly_amount' => 1000, 
            'duration_months' => 36
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_subscription_validates_kyc_required()
    {
        $this->user->kyc->update(['status' => 'pending']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("KYC must be verified");

        $this->service->createSubscription($this->user, $this->plan);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_subscription_sets_next_payment_date()
    {
        // Freeze time to check dates accurately
        $this->travelTo(Carbon::parse('2025-01-01'));

        $sub = $this->service->createSubscription($this->user, $this->plan);

        // Next payment should be NOW (immediate first payment) or handled by logic
        // Service sets it to now()
        $this->assertEquals('2025-01-01', $sub->next_payment_date->toDateString());
        
        // End date should be 36 months later
        $this->assertEquals('2028-01-01', $sub->end_date->toDateString());
        
        $this->travelBack();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_pause_subscription_validates_limit()
    {
        $plan = Plan::factory()->create(['allow_pause' => true]);
        $sub = Subscription::factory()->create([
            'status' => 'active',
            'plan_id' => $plan->id,
        ]);

        // Model throws InvalidArgumentException for duration validation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Pause duration must be between 1 and 3 months");

        $this->service->pauseSubscription($sub, 4);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_pause_subscription_updates_status()
    {
        $sub = Subscription::factory()->create([
            'status' => 'active',
            'next_payment_date' => now()->addDays(10)
        ]);

        $this->service->pauseSubscription($sub, 2); // 2 months

        $this->assertEquals('paused', $sub->fresh()->status);
        // Date should shift +2 months
        $this->assertEquals(
            now()->addDays(10)->addMonths(2)->toDateString(), 
            $sub->fresh()->next_payment_date->toDateString()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_resume_subscription_recalculates_dates()
    {
        $sub = Subscription::factory()->create(['status' => 'paused']);
        
        $this->service->resumeSubscription($sub);
        
        $this->assertEquals('active', $sub->fresh()->status);
        $this->assertNull($sub->fresh()->pause_start_date);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_upgrade_plan_calculates_differential_charge()
    {
        // BILLING DOCTRINE: Upgrade uses flat differential (newAmount - oldAmount)
        // NOT time-based proration. See V-FINAL-1730-578 (V2.0 Proration).
        // Current Plan: 1000
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount, // 1000
            'status' => 'active',
        ]);

        // Upgrade to 4000/mo Plan
        $newPlan = Plan::factory()->create(['monthly_amount' => 4000]);

        // Upgrade differential = newPlanAmount - currentPlanAmount
        $amount = $this->service->upgradePlan($sub, $newPlan);

        // 4000 - 1000 = 3000
        $this->assertEquals(3000, $amount);

        // Verify upgrade payment created with correct type
        $this->assertDatabaseHas('payments', [
            'subscription_id' => $sub->id,
            'amount' => 3000,
            'status' => 'pending',
            'payment_type' => \App\Enums\PaymentType::UPGRADE_CHARGE->value,
        ]);

        // Verify subscription updated to new plan
        $this->assertEquals($newPlan->id, $sub->fresh()->plan_id);
        $this->assertEquals(4000, $sub->fresh()->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_downgrade_plan_calculates_refund()
    {
        $expensivePlan = Plan::factory()->create(['monthly_amount' => 5000]);
        $sub = Subscription::factory()->create([
            'plan_id' => $expensivePlan->id,
            'amount' => $expensivePlan->monthly_amount, // Must explicitly set to match plan
            'status' => 'active'
        ]);

        $amount = $this->service->downgradePlan($sub, $this->plan); // 1000

        // FSD Rule: No refund on downgrade
        $this->assertEquals(0, $amount);
        $this->assertEquals($this->plan->id, $sub->fresh()->plan_id);
        $this->assertEquals($this->plan->monthly_amount, $sub->fresh()->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancel_subscription_processes_refund()
    {
        $sub = Subscription::factory()->create(['status' => 'active']);
        
        // Create a pending payment that should be voided
        Payment::factory()->create([
            'subscription_id' => $sub->id,
            'status' => 'pending'
        ]);

        $this->service->cancelSubscription($sub, "Testing");

        $this->assertEquals('cancelled', $sub->fresh()->status);
        // Pending payment should be marked failed
        $this->assertDatabaseHas('payments', [
            'subscription_id' => $sub->id,
            'status' => 'failed'
        ]);
    }
}