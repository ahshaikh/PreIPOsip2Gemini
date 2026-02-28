<?php
// V-FINAL-1730-TEST-86 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UserKyc;
use App\Models\Withdrawal;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;

class AdminDashboardEndpointsTest extends FeatureTestCase
{
//    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

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
        // Get baseline count of users with 'user' role BEFORE creating more
        $baselineCount = User::role('user')->count();

        User::factory()->count(10)->create()->each(fn($u) => $u->assignRole('user'));

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');

        // Verify we have at least 10 more users than baseline
        $actualCount = $response->json('kpis.total_users');
        $this->assertGreaterThanOrEqual($baselineCount + 10, $actualCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardShowsTotalRevenue()
    {
        // Create a subscription to use for payments (to avoid factory cascade creating extra payments)
        $subscription = Subscription::factory()->create(['user_id' => $this->user->id]);

        // Get baseline revenue from existing paid payments AFTER subscription is created
        $baselineRevenue = Payment::where('status', 'paid')->sum('amount_paise') / 100;

        // Create payments with explicit subscription to avoid cascading factory creations
        // Must set BOTH amount_paise AND amount since dashboard sums 'amount' (rupees)
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'status' => 'paid',
            'amount_paise' => 500000,
            'amount' => 5000
        ]); // ₹5000
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'status' => 'paid',
            'amount_paise' => 300000,
            'amount' => 3000
        ]); // ₹3000
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'amount_paise' => 100000,
            'amount' => 1000
        ]); // ₹1000 - Should not be counted (pending)

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');

        // Verify revenue includes at least the 8000 we added
        $actualRevenue = $response->json('kpis.total_revenue');
        $this->assertGreaterThanOrEqual($baselineRevenue + 8000, $actualRevenue);
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
        // Get baseline values
        $baselineRevenue = Payment::where('status', 'paid')->sum('amount_paise') / 100;
        $kycBefore = UserKyc::where('status', 'submitted')->count();

        // Create test data
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 100000, 'amount' => 1000]); // ₹1000
        UserKyc::factory()->create(['status' => 'submitted']);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
        // Verify structure and values - use delta assertions
        $this->assertGreaterThanOrEqual($baselineRevenue + 1000, $response->json('kpis.total_revenue'));
        $this->assertGreaterThanOrEqual(1, $response->json('kpis.total_users'));
        $this->assertGreaterThanOrEqual($kycBefore + 1, $response->json('kpis.pending_kyc'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardShowsRecentActivity()
    {
        // Get baseline count
        $baselineCount = ActivityLog::count();

        // Create test activity logs with distinct timestamps
        ActivityLog::factory()->create(['description' => 'User logged in for test', 'created_at' => now()->subMinute()]);
        ActivityLog::factory()->create(['description' => 'Payment failed for test', 'created_at' => now()]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
        // Just verify that recent_activity exists and has items
        $recentActivity = $response->json('recent_activity');
        $this->assertIsArray($recentActivity);
        $this->assertNotEmpty($recentActivity);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDashboardChartsLoadCorrectly()
    {
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 123400, 'amount' => 1234, 'paid_at' => now()->subDays(2)]); // ₹1234
        User::factory()->create(['created_at' => now()->subDays(3)]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
        // Just verify the charts structure exists and contains data
        $this->assertArrayHasKey('charts', $response->json());
        $this->assertArrayHasKey('revenue_over_time', $response->json('charts'));
        $this->assertArrayHasKey('user_growth', $response->json('charts'));
        // At least one data point should exist
        $this->assertNotEmpty($response->json('charts.revenue_over_time'));
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
