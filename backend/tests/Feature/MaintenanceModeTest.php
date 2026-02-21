<?php
// V-FINAL-1730-TEST-68 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class MaintenanceModeTest extends TestCase
{
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /**
     * Helper to turn on Maintenance Mode
     */
    private function turnMaintenanceOn($message = null, $ips = null)
    {
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => 'true']);
        if ($message) {
            Setting::updateOrCreate(['key' => 'maintenance_message'], ['value' => $message]);
        }
        if ($ips) {
            Setting::updateOrCreate(['key' => 'allowed_ips'], ['value' => $ips]);
        }
        // Bust cache
        \Illuminate\Support\Facades\Cache::flush();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_maintenance_mode_blocks_all_users()
    {
        $this->turnMaintenanceOn();

        // Try to access a protected user route
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/profile');
        
        $response->assertStatus(503);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_maintenance_mode_allows_admins()
    {
        $this->turnMaintenanceOn();

        // Admin can still access their dashboard
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        
        $response->assertStatus(200); // 200 OK (or whatever the dashboard returns)
        
        // Admin can also access the user profile route
        $response = $this->actingAs($this->admin)->getJson('/api/v1/user/profile');
        
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_maintenance_mode_shows_custom_message()
    {
        $this->turnMaintenanceOn("Be Right Back!");

        $response = $this->actingAs($this->user)->getJson('/api/v1/user/profile');
        
        $response->assertStatus(503);
        $response->assertJson(['message' => 'Be Right Back!']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_maintenance_mode_respects_ip_whitelist()
    {
        // 127.0.0.1 is the default IP for tests
        $this->turnMaintenanceOn("Maintenance", "127.0.0.1, 8.8.8.8");

        // Even though this is a normal user, their IP is whitelisted
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/profile');

        $response->assertStatus(200); // Bypassed maintenance mode
    }
}