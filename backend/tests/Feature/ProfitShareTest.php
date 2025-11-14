<?php
// V-FINAL-1730-TEST-09

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ProfitShare;
use App\Models\Subscription;
use App\Models\Plan;

class ProfitShareTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create a Plan with 10% share
        $plan = Plan::factory()->create();
        $plan->configs()->create([
            'config_key' => 'profit_share', 
            'value' => ['percentage' => 10] // 10%
        ]);

        // Create 2 Users with this plan
        $u1 = User::factory()->create();
        Subscription::factory()->create(['user_id' => $u1->id, 'plan_id' => $plan->id, 'start_date' => now()->subMonths(3)]);
        
        $u2 = User::factory()->create();
        Subscription::factory()->create(['user_id' => $u2->id, 'plan_id' => $plan->id, 'start_date' => now()->subMonths(3)]);

        // Create Period
        $this->period = ProfitShare::create([
            'period_name' => 'Q1 2025',
            'start_date' => now()->subMonths(3),
            'end_date' => now(),
            'net_profit' => 1000000,
            'total_pool' => 100000, // We are sharing 1 Lakh
            'admin_id' => $this->admin->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function admin_can_calculate_profit_share()
    {
        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/profit-sharing/{$this->period->id}/calculate");

        $response->assertStatus(200);
        
        // Check distribution created
        $this->assertDatabaseCount('user_profit_shares', 2);
        
        // Check status update
        $this->assertEquals('calculated', $this->period->fresh()->status);
    }

    /** @test */
    public function admin_can_distribute_profit_share()
    {
        // Calculate first
        $this->actingAs($this->admin)
             ->postJson("/api/v1/admin/profit-sharing/{$this->period->id}/calculate");

        // Distribute
        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/profit-sharing/{$this->period->id}/distribute");

        $response->assertStatus(200);
        
        // Check wallets credited
        $this->assertDatabaseHas('wallets', ['balance' => 5000]); // Approx split
        
        // Check status update
        $this->assertEquals('distributed', $this->period->fresh()->status);
    }
}