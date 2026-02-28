<?php
// V-FINAL-1730-TEST-67 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Plan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class PermissionMiddlewareTest extends FeatureTestCase
{
    protected $adminUser;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles AND permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PermissionsSeeder::class);
        
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin'); // Assign base 'admin' role
        
        $this->plan = Plan::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_permission_allows_authorized_user()
    {
        // The 'admin' role (from seeder) *has* 'plans.view'
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/api/v1/admin/plans');
        
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_permission_blocks_unauthorized_user()
    {
        // 'admin' role does NOT have 'system.view_health' by default
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/api/v1/admin/system/health');
        
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Forbidden: You do not have the required permission.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_permission_checks_granular_permissions()
    {
        // Admin routes require role:admin|super-admin middleware first
        // Then specific permissions are checked via permission: middleware

        // 1. Create a user with admin role but WITHOUT the specific permission
        $adminWithoutPermission = User::factory()->create();
        $adminRole = Role::findByName('admin', 'web');

        // Remove the plans.edit permission from admin role for this test
        $plansEditPermission = Permission::firstOrCreate(['name' => 'plans.edit', 'guard_name' => 'web']);
        $adminRole->revokePermissionTo($plansEditPermission);

        // Assign the modified role to user
        $adminWithoutPermission->assignRole('admin');
        $adminWithoutPermission->forgetCachedPermissions();

        // 2. Try to access plans (should fail due to missing permission)
        $response = $this->actingAs($adminWithoutPermission)
                         ->getJson('/api/v1/admin/plans');
        $response->assertStatus(403);

        // 3. Now grant the specific permission
        $adminWithoutPermission->givePermissionTo($plansEditPermission);
        $adminWithoutPermission->forgetCachedPermissions();

        // 4. Try again (should pass)
        $response = $this->actingAs($adminWithoutPermission)
                         ->getJson('/api/v1/admin/plans');
        $response->assertStatus(200);

        // Restore the permission to admin role for other tests
        $adminRole->givePermissionTo($plansEditPermission);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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
