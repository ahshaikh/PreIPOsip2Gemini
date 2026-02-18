<?php

// V-DISPUTE-RISK-2026-TEST-004: Dispute Stats Cache Unit Tests

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DisputeStatsCache;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DisputeStatsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected DisputeStatsCache $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->cacheService = new DisputeStatsCache();
    }

    // ==================== OVERVIEW TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_overview_returns_expected_structure()
    {
        $overview = $this->cacheService->getOverview();

        $this->assertArrayHasKey('total_disputes', $overview);
        $this->assertArrayHasKey('active_disputes', $overview);
        $this->assertArrayHasKey('recent_disputes_7d', $overview);
        $this->assertArrayHasKey('by_status', $overview);
        $this->assertArrayHasKey('by_severity', $overview);
        $this->assertArrayHasKey('computed_at', $overview);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_overview_counts_disputes_correctly()
    {
        // Create disputes with various statuses
        $this->createDispute('open', 'low');
        $this->createDispute('open', 'medium');
        $this->createDispute('under_investigation', 'high');
        $this->createDispute('resolved', 'low');

        // Force refresh to get fresh data
        $overview = $this->cacheService->getOverview(true);

        $this->assertEquals(4, $overview['total_disputes']);
        $this->assertEquals(3, $overview['active_disputes']); // open + under_investigation
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_overview_caches_results()
    {
        // First call computes and caches
        $overview1 = $this->cacheService->getOverview();
        $computedAt1 = $overview1['computed_at'];

        // Create a new dispute
        $this->createDispute('open', 'low');

        // Second call should return cached data
        $overview2 = $this->cacheService->getOverview();
        $computedAt2 = $overview2['computed_at'];

        // Timestamps should match (cached)
        $this->assertEquals($computedAt1, $computedAt2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_overview_force_refresh_bypasses_cache()
    {
        // First call
        $overview1 = $this->cacheService->getOverview();
        $count1 = $overview1['total_disputes'];

        // Create a new dispute
        $this->createDispute('open', 'low');

        // Force refresh
        $overview2 = $this->cacheService->getOverview(true);
        $count2 = $overview2['total_disputes'];

        // Count should reflect new dispute
        $this->assertEquals($count1 + 1, $count2);
    }

    // ==================== CHARGEBACK STATS TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_chargeback_stats_returns_expected_structure()
    {
        $stats = $this->cacheService->getChargebackStats();

        $this->assertArrayHasKey('total_confirmed', $stats);
        $this->assertArrayHasKey('total_amount_paise', $stats);
        $this->assertArrayHasKey('total_amount_rupees', $stats);
        $this->assertArrayHasKey('pending_count', $stats);
        $this->assertArrayHasKey('recent_30d_count', $stats);
        $this->assertArrayHasKey('by_month', $stats);
        $this->assertArrayHasKey('top_reasons', $stats);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_chargeback_stats_counts_correctly()
    {
        $user = User::factory()->create();

        // Create confirmed chargebacks
        $this->createChargebackPayment($user, 100000, 'fraud');
        $this->createChargebackPayment($user, 50000, 'fraud');
        $this->createChargebackPayment($user, 75000, 'not_received');

        // Create pending chargeback
        Payment::factory()->create([
            'user_id' => $user->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount_paise' => 30000,
        ]);

        $stats = $this->cacheService->getChargebackStats(true);

        $this->assertEquals(3, $stats['total_confirmed']);
        $this->assertEquals(225000, $stats['total_amount_paise']);
        $this->assertEquals(2250, $stats['total_amount_rupees']);
        $this->assertEquals(1, $stats['pending_count']);
    }

    // ==================== RISK DISTRIBUTION TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_risk_distribution_returns_expected_structure()
    {
        $dist = $this->cacheService->getRiskDistribution();

        $this->assertArrayHasKey('blocked_users', $dist);
        $this->assertArrayHasKey('high_risk_users', $dist);
        $this->assertArrayHasKey('review_users', $dist);
        $this->assertArrayHasKey('low_risk_users', $dist);
        $this->assertArrayHasKey('thresholds', $dist);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_risk_distribution_categorizes_users_correctly()
    {
        // Create users with various risk scores
        User::factory()->create(['risk_score' => 0, 'is_blocked' => false]);
        User::factory()->create(['risk_score' => 20, 'is_blocked' => false]);
        User::factory()->create(['risk_score' => 35, 'is_blocked' => false]); // review
        User::factory()->create(['risk_score' => 55, 'is_blocked' => false]); // high
        User::factory()->create(['risk_score' => 80, 'is_blocked' => true]); // blocked

        $dist = $this->cacheService->getRiskDistribution(true);

        $this->assertEquals(1, $dist['blocked_users']);
        $this->assertEquals(1, $dist['high_risk_users']);
        $this->assertEquals(1, $dist['review_users']);
        // Low risk includes users with score < 30
        $this->assertGreaterThanOrEqual(2, $dist['low_risk_users']);
    }

    // ==================== CACHE MANAGEMENT TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function warm_cache_populates_all_caches()
    {
        // Clear any existing cache
        $this->cacheService->clearCache();

        // Warm cache
        $this->cacheService->warmCache();

        // Get all stats (should come from cache)
        $stats = $this->cacheService->getAllStats();

        $this->assertArrayHasKey('overview', $stats);
        $this->assertArrayHasKey('chargebacks', $stats);
        $this->assertArrayHasKey('risk_distribution', $stats);
        $this->assertArrayHasKey('cached_at', $stats);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function clear_cache_removes_all_stats()
    {
        // Populate cache
        $this->cacheService->warmCache();

        // Clear cache
        $this->cacheService->clearCache();

        // Next call should compute fresh data
        $stats1 = $this->cacheService->getOverview();
        $computedAt1 = $stats1['computed_at'];

        // Wait a tiny bit and call again
        usleep(1000); // 1ms
        $this->cacheService->clearCache();
        $stats2 = $this->cacheService->getOverview();
        $computedAt2 = $stats2['computed_at'];

        // Timestamps should be different (freshly computed)
        $this->assertNotEquals($computedAt1, $computedAt2);
    }

    // ==================== ALL STATS COMBINED TEST ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_all_stats_returns_complete_data()
    {
        $stats = $this->cacheService->getAllStats();

        $this->assertArrayHasKey('overview', $stats);
        $this->assertArrayHasKey('chargebacks', $stats);
        $this->assertArrayHasKey('risk_distribution', $stats);
        $this->assertArrayHasKey('cached_at', $stats);
        $this->assertArrayHasKey('cache_ttl_seconds', $stats);
    }

    // ==================== HELPER METHODS ====================

    protected function createDispute(string $status, string $severity): Dispute
    {
        // FIX: Create company fixture instead of assuming one exists
        $company = Company::factory()->create();

        return Dispute::create([
            'company_id' => $company->id,
            'user_id' => User::factory()->create()->id,
            'status' => $status,
            'severity' => $severity,
            'category' => 'other',
            'title' => 'Test Dispute',
            'description' => 'Test description',
            'opened_at' => now(),
        ]);
    }

    protected function createChargebackPayment(User $user, int $amountPaise, string $reason): Payment
    {
        return Payment::factory()->create([
            'user_id' => $user->id,
            'status' => Payment::STATUS_CHARGEBACK_CONFIRMED,
            'amount_paise' => $amountPaise,
            'chargeback_amount_paise' => $amountPaise,
            'chargeback_reason' => $reason,
            'chargeback_confirmed_at' => now(),
        ]);
    }
}
