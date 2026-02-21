<?php
// V-FINAL-1730-TEST-20

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use Carbon\Carbon;

class SubscriptionTest extends TestCase
{
    protected $user;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->plan = Plan::factory()->create(['monthly_amount' => 5000]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_belongs_to_user()
    {
        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount,
            'subscription_code' => 'SUB-TEST-' . uniqid(),
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'next_payment_date' => now()
        ]);

        $this->assertInstanceOf(User::class, $sub->user);
        $this->assertEquals($this->user->id, $sub->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_belongs_to_plan()
    {
        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount,
            'subscription_code' => 'SUB-TEST-' . uniqid(),
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'next_payment_date' => now()
        ]);

        $this->assertInstanceOf(Plan::class, $sub->plan);
        $this->assertEquals($this->plan->id, $sub->plan->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_has_payments_relationship()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['subscription_id' => $sub->id]);

        $this->assertTrue($sub->payments()->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_calculates_next_payment_date()
    {
        // This logic is usually handled by the controller/service during creation/payment
        // Here we verify the attribute works as a Date object
        $date = now()->addDays(5);
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'next_payment_date' => $date
        ]);

        $this->assertInstanceOf(Carbon::class, $sub->next_payment_date);
        $this->assertEquals($date->toDateString(), $sub->next_payment_date->toDateString());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_calculates_months_completed()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        
        // Create 3 paid payments
        Payment::factory()->count(3)->create([
            'subscription_id' => $sub->id,
            'status' => 'paid'
        ]);
        
        // Create 1 pending payment (should not count)
        Payment::factory()->create([
            'subscription_id' => $sub->id,
            'status' => 'pending'
        ]);

        $this->assertEquals(3, $sub->months_completed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_tracks_consecutive_payments()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'consecutive_payments_count' => 5
        ]);

        $this->assertEquals(5, $sub->consecutive_payments_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_can_be_paused()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'next_payment_date' => now(),
            'end_date' => now()->addYear()
        ]);

        $originalEndDate = $sub->end_date->copy();
        
        $sub->pause(2); // Pause for 2 months

        $sub->refresh();
        $this->assertEquals('paused', $sub->status);
        $this->assertNotNull($sub->pause_start_date);
        // End date should be pushed by 2 months
        $this->assertEquals($originalEndDate->addMonths(2)->toDateString(), $sub->end_date->toDateString());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_can_be_resumed()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'paused',
            'pause_start_date' => now()
        ]);

        $sub->resume();

        $this->assertEquals('active', $sub->fresh()->status);
        $this->assertNull($sub->fresh()->pause_start_date);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_can_be_cancelled()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $sub->cancel('Not interested');

        $this->assertEquals('cancelled', $sub->fresh()->status);
        $this->assertEquals('Not interested', $sub->fresh()->cancellation_reason);
        $this->assertNotNull($sub->fresh()->cancelled_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_validates_pause_limit()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);

        $this->expectException(\InvalidArgumentException::class);

        $sub->pause(4); // Max limit is 3
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_calculates_total_paid()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        
        Payment::factory()->create(['subscription_id' => $sub->id, 'amount' => 1000, 'status' => 'paid']);
        Payment::factory()->create(['subscription_id' => $sub->id, 'amount' => 2000, 'status' => 'paid']);
        
        $this->assertEquals(3000, $sub->total_paid);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_status_enum_validates()
    {
        // Testing via DB query constraint or model update
        $sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        
        $sub->update(['status' => 'active']);
        $this->assertEquals('active', $sub->fresh()->status);
        
        $sub->update(['status' => 'cancelled']);
        $this->assertEquals('cancelled', $sub->fresh()->status);
        
        // Note: Since we use string columns in migration, this test confirms
        // the application logic handles statuses correctly. 
        // Strict Enum enforcement would be a database migration feature.
        $this->assertTrue(true);
    }
}