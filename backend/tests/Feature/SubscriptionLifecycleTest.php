<?php
// V-FINAL-1730-TEST-02

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionLifecycleTest extends FeatureTestCase
{
    protected $user;
    protected $planA;
    protected $planB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->kyc->update(['status' => 'verified']);

        $this->planA = Plan::factory()->create(['name' => 'Plan A', 'monthly_amount' => 1000]);
        $this->planB = Plan::factory()->create(['name' => 'Plan B', 'monthly_amount' => 5000]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_subscribe_successfully()
    {
        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription', ['plan_id' => $this->planA->id]);

        $response->assertStatus(201);
        // Status is 'pending' until first payment succeeds (Free Ride fix)
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->planA->id,
            'status' => 'pending'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_upgrade_plan()
    {
        // Create existing subscription
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planA->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription/change-plan', ['new_plan_id' => $this->planB->id]);

        $response->assertStatus(200);
        
        // Verify Plan Changed
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->planB->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_pause_subscription()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'next_payment_date' => now()->addDays(5)
        ]);

        $originalNextDate = $sub->next_payment_date;

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription/pause', ['months' => 2]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'status' => 'paused',
            // Should shift 2 months
            'next_payment_date' => $originalNextDate->addMonths(2)->toDateTimeString() 
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_cancel_subscription()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription/cancel', ['reason' => 'Too expensive']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Too expensive'
        ]);
    }
}
