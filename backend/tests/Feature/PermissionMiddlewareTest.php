<?php
// V-FINAL-1730-TEST-67 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles AND permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin'); // Assign base 'admin' role
        
        $this->plan = Plan::factory()->create();
    }

    /** @test */
    public function test_permission_allows_authorized_user()
    {
        // The 'admin' role (from seeder) *has* 'plans.view'
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/api/v1/admin/plans');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function test_permission_blocks_unauthorized_user()
    {
        // 'admin' role does NOT have 'system.view_health' by default
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/api/v1/admin/system/health');
        
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Forbidden: You do not have the required permission.']);
    }

    /** @test */
    public function test_permission_checks_granular_permissions()
    {
        // 1. Manually remove 'plans.view' from admin
        $this->adminUser->removePermissionTo('plans.view');

        // 2. Try to view plans (should fail)
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/api/v1/admin/plans');
        
        $response->assertStatus(403);

        // 3. Now, give *only* 'plans.view'
        $this->adminUser->givePermissionTo('plans.view');

        // 4. Try again (should pass)
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/api/v1/admin/plans');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function test_permission_logs_unauthorized_attempts()
    {
        // Mock the Log facade
        Log::shouldReceive('warning')->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Authorization Failed') &&
                       str_contains($message, 'system.view_health');
            });
            
        // 'admin' role does NOT have 'system.view_health'
        $this->actingAs($this->adminUser)
             ->getJson('/api/v1/admin/system/health');
    }
}