<?php
// V-FINAL-1730-TEST-86 (Created)

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\UserKyc;
use App\Models\Withdrawal;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;

class AdminDashboardEndpointsTest extends TestCase
{
//    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
//        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
//        $this->seed(\Database\Seeders\SettingsSeeder::class);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardRequiresAdminAuth()
    {
        // 1. No auth
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
        
        // 2. User auth
        $this->actingAs($this->user)->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        
        // 3. Admin auth
        $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard')->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardShowsTotalUsers()
    {
        User::factory()->count(10)->create()->each(fn($u) => $u->assignRole('user'));
        
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        
        // 1 admin + 1 user + 10 users = 12 total users, 11 'user' role
        $response->assertJsonPath('kpis.total_users', 11);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardShowsTotalRevenue()
    {
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 500000]); // ₹5000 in paise
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 300000]); // ₹3000 in paise
        Payment::factory()->create(['status' => 'pending', 'amount_paise' => 100000]); // ₹1000 in paise - Should not be counted
        
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        
        $response->assertJsonPath('kpis.total_revenue', 8000);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardShowsPendingKycs()
    {
        UserKyc::factory()->create(['status' => 'submitted']);
        UserKyc::factory()->create(['status' => 'submitted']);
        UserKyc::factory()->create(['status' => 'verified']);
        
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        
        $response->assertJsonPath('kpis.pending_kyc', 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardShowsPendingWithdrawals()
    {
        Withdrawal::factory()->create(['status' => 'pending']);
        Withdrawal::factory()->create(['status' => 'completed']);
        
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        
        $response->assertJsonPath('kpis.pending_withdrawals', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testGetDashboardStatsReturnsCorrectMetrics()
    {
        // This test combines the above
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 100000]); // ₹1000 in paise
        UserKyc::factory()->create(['status' => 'submitted']);
        
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertJson([
            'kpis' => [
                'total_revenue' => 1000,
                'total_users' => 1, // $this->user
                'pending_kyc' => 1,
                'pending_withdrawals' => 0
            ]
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardShowsRecentActivity()
    {
        ActivityLog::factory()->create(['description' => 'User logged in']);
        ActivityLog::factory()->create(['description' => 'Payment failed']);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'recent_activity');
        $response->assertJsonPath('recent_activity.0.description', 'Payment failed'); // Latest first
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardChartsLoadCorrectly()
    {
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 123400, 'paid_at' => now()->subDays(2)]); // ₹1234 in paise
        User::factory()->create(['created_at' => now()->subDays(3)]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'charts.revenue_over_time');
        $response->assertJsonCount(1, 'charts.user_growth');
        $response->assertJsonPath('charts.revenue_over_time.0.total', 1234);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardRespondsUnder500ms()
    {
        // Note: This test is environment-dependent, but we can check it's not critically slow.
        $startTime = microtime(true);
        $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
        $endTime = microtime(true);
        
        $duration = ($endTime - $startTime) * 1000; // in milliseconds

        $this->assertLessThan(500, $duration, "Dashboard took over 500ms to respond.");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardHandlesConcurrentAccess()
    {
        // This isn't a true concurrency test, but it ensures that
        // multiple requests don't corrupt the cache or data.
        
        $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard')->assertStatus(200);
        $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard')->assertStatus(200);

        // We can also test the cache is used
        Cache::shouldReceive('remember')
            ->once() // The second call should hit the cache
            ->andReturn(['kpis' => [], 'charts' => [], 'recent_activity' => []]);

        $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');
    }
}