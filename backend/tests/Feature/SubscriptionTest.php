<?php
// V-FINAL-1730-TEST-07

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionTest extends FeatureTestCase
{
    protected $user;
    protected $planA;
    protected $planB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class); // For investment_enabled toggle

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        // Bypass KYC for this test
        $this->user->kyc->update(['status' => 'verified']);

        $this->planA = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->planB = Plan::factory()->create(['monthly_amount' => 5000]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_subscription()
    {
        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription', [
                             'plan_id' => $this->planA->id
                         ]);

        $response->assertStatus(201);
        // Status is 'pending' until first payment succeeds (Free Ride fix)
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->planA->id,
            'status' => 'pending'
        ]);
        // Ensure first payment created
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'status' => 'pending'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_upgrade_plan()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planA->id,
            'status' => 'active'
        ]);

        // Ensure no pending payments block the upgrade
        \App\Models\Payment::where('subscription_id', $sub->id)->update(['status' => 'paid']);

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription/change-plan', [
                             'new_plan_id' => $this->planB->id
                         ]);

        if ($response->status() !== 200) {
            $response->dump(); // Diagnostic dump
        }

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'plan_id' => $this->planB->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_pause_subscription()
    {
        // Explicitly set max_pause_duration_months to ensure validation passes
        $plan = Plan::factory()->create([
            'max_pause_duration_months' => 3,
            'allow_pause' => true,
        ]);

        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription/pause', [
                             'months' => 2
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'status' => 'paused'
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
                         ->postJson('/api/v1/user/subscription/cancel', [
                             'reason' => 'Not interested'
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'status' => 'cancelled'
        ]);
    }
}
