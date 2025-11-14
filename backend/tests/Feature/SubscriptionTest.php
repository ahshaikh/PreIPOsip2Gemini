<?php
// V-FINAL-1730-TEST-07

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

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

    /** @test */
    public function user_can_create_subscription()
    {
        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription', [
                             'plan_id' => $this->planA->id
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->planA->id,
            'status' => 'active'
        ]);
        // Ensure first payment created
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function user_can_upgrade_plan()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planA->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/subscription/change-plan', [
                             'new_plan_id' => $this->planB->id
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'plan_id' => $this->planB->id
        ]);
    }

    /** @test */
    public function user_can_pause_subscription()
    {
        $sub = Subscription::factory()->create([
            'user_id' => $this->user->id,
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

    /** @test */
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