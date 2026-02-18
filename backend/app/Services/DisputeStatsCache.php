<?php

/**
 * V-DISPUTE-RISK-2026-007: Dispute Stats Cache Service
 *
 * Provides cached dispute and chargeback statistics for admin dashboard.
 * Uses Redis cache with configurable TTL (default 30 minutes).
 *
 * CACHING STRATEGY:
 * - Statistics are computed once and cached
 * - Cache is warmed hourly via scheduled command
 * - Manual refresh available for admin users
 * - Stale data acceptable for dashboard (not used in financial decisions)
 */

namespace App\Services;

use App\Models\Dispute;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisputeStatsCache
{
    /**
     * Cache key prefix for dispute stats.
     */
    protected const CACHE_PREFIX = 'dispute_stats:';

    /**
     * Default TTL in seconds (30 minutes).
     */
    protected const DEFAULT_TTL = 1800;

    /**
     * Get dispute overview statistics.
     *
     * @param bool $forceRefresh Force cache refresh
     * @return array
     */
    public function getOverview(bool $forceRefresh = false): array
    {
        $cacheKey = self::CACHE_PREFIX . 'overview';
        $ttl = $this->getTtl();

        if ($forceRefresh) {
            Cache::store($this->getCacheStore())->forget($cacheKey);
        }

        return Cache::store($this->getCacheStore())->remember($cacheKey, $ttl, function () {
            return $this->computeOverview();
        });
    }

    /**
     * Get chargeback statistics.
     *
     * @param bool $forceRefresh Force cache refresh
     * @return array
     */
    public function getChargebackStats(bool $forceRefresh = false): array
    {
        $cacheKey = self::CACHE_PREFIX . 'chargebacks';
        $ttl = $this->getTtl();

        if ($forceRefresh) {
            Cache::store($this->getCacheStore())->forget($cacheKey);
        }

        return Cache::store($this->getCacheStore())->remember($cacheKey, $ttl, function () {
            return $this->computeChargebackStats();
        });
    }

    /**
     * Get risk distribution statistics.
     *
     * @param bool $forceRefresh Force cache refresh
     * @return array
     */
    public function getRiskDistribution(bool $forceRefresh = false): array
    {
        $cacheKey = self::CACHE_PREFIX . 'risk_distribution';
        $ttl = $this->getTtl();

        if ($forceRefresh) {
            Cache::store($this->getCacheStore())->forget($cacheKey);
        }

        return Cache::store($this->getCacheStore())->remember($cacheKey, $ttl, function () {
            return $this->computeRiskDistribution();
        });
    }

    /**
     * Get all cached stats combined.
     *
     * @param bool $forceRefresh Force cache refresh
     * @return array
     */
    public function getAllStats(bool $forceRefresh = false): array
    {
        return [
            'overview' => $this->getOverview($forceRefresh),
            'chargebacks' => $this->getChargebackStats($forceRefresh),
            'risk_distribution' => $this->getRiskDistribution($forceRefresh),
            'cached_at' => now()->toIso8601String(),
            'cache_ttl_seconds' => $this->getTtl(),
        ];
    }

    /**
     * Warm all caches (called by scheduled command).
     *
     * @return void
     */
    public function warmCache(): void
    {
        $startTime = microtime(true);

        Log::info('Starting dispute stats cache warm...');

        // Force refresh all stats
        $this->getOverview(true);
        $this->getChargebackStats(true);
        $this->getRiskDistribution(true);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        Log::info("Dispute stats cache warmed in {$duration}ms");
    }

    /**
     * Clear all dispute stats caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $store = Cache::store($this->getCacheStore());

        $store->forget(self::CACHE_PREFIX . 'overview');
        $store->forget(self::CACHE_PREFIX . 'chargebacks');
        $store->forget(self::CACHE_PREFIX . 'risk_distribution');

        Log::info('Dispute stats cache cleared');
    }

    /**
     * Compute overview statistics (called on cache miss).
     *
     * @return array
     */
    protected function computeOverview(): array
    {
        // Total disputes by status
        $disputesByStatus = Dispute::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Total disputes by severity
        $disputesBySeverity = Dispute::select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'status')
            ->toArray();

        // Total disputes by category
        $disputesByCategory = Dispute::select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Active disputes (open, under_investigation, escalated)
        $activeDisputes = Dispute::active()->count();

        // Disputes opened in last 7 days
        $recentDisputes = Dispute::where('created_at', '>=', now()->subDays(7))->count();

        // Average resolution time (for resolved disputes)
        $avgResolutionDays = Dispute::whereNotNull('resolved_at')
            ->whereNotNull('opened_at')
            ->select(DB::raw('AVG(DATEDIFF(resolved_at, opened_at)) as avg_days'))
            ->value('avg_days');

        return [
            'total_disputes' => array_sum($disputesByStatus),
            'active_disputes' => $activeDisputes,
            'recent_disputes_7d' => $recentDisputes,
            'by_status' => $disputesByStatus,
            'by_severity' => $disputesBySeverity,
            'by_category' => $disputesByCategory,
            'avg_resolution_days' => $avgResolutionDays ? round($avgResolutionDays, 1) : null,
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Compute chargeback statistics.
     *
     * @return array
     */
    protected function computeChargebackStats(): array
    {
        // Total chargebacks (confirmed)
        $totalChargebacks = Payment::where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)->count();

        // Total chargeback amount (paise)
        $totalChargebackAmountPaise = Payment::where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->sum('chargeback_amount_paise');

        // Pending chargebacks
        $pendingChargebacks = Payment::where('status', Payment::STATUS_CHARGEBACK_PENDING)->count();

        // Chargebacks in last 30 days
        $recentChargebacks = Payment::where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->where('chargeback_confirmed_at', '>=', now()->subDays(30))
            ->count();

        // Chargebacks by month (last 6 months)
        $chargebacksByMonth = Payment::where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->where('chargeback_confirmed_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(chargeback_confirmed_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(chargeback_amount_paise) as total_amount_paise')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get()
            ->toArray();

        // Top chargeback reasons
        $topReasons = Payment::where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->whereNotNull('chargeback_reason')
            ->select('chargeback_reason', DB::raw('COUNT(*) as count'))
            ->groupBy('chargeback_reason')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'chargeback_reason')
            ->toArray();

        return [
            'total_confirmed' => $totalChargebacks,
            'total_amount_paise' => $totalChargebackAmountPaise,
            'total_amount_rupees' => $totalChargebackAmountPaise / 100,
            'pending_count' => $pendingChargebacks,
            'recent_30d_count' => $recentChargebacks,
            'by_month' => $chargebacksByMonth,
            'top_reasons' => $topReasons,
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Compute risk distribution statistics.
     *
     * @return array
     */
    protected function computeRiskDistribution(): array
    {
        $blockingThreshold = config('risk.thresholds.blocking', 70);
        $highRiskThreshold = config('risk.thresholds.high_risk', 50);
        $reviewThreshold = config('risk.thresholds.review', 30);

        // Blocked users count
        $blockedUsers = User::where('is_blocked', true)->count();

        // Users by risk category
        $highRiskUsers = User::where('risk_score', '>=', $highRiskThreshold)
            ->where('risk_score', '<', $blockingThreshold)
            ->count();

        $reviewUsers = User::where('risk_score', '>=', $reviewThreshold)
            ->where('risk_score', '<', $highRiskThreshold)
            ->count();

        $lowRiskUsers = User::where('risk_score', '<', $reviewThreshold)->count();

        // Risk score distribution (histogram buckets)
        $riskDistribution = User::select(
            DB::raw('FLOOR(risk_score / 10) * 10 as bucket'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('count', 'bucket')
            ->toArray();

        // Recently blocked users (last 7 days)
        $recentlyBlocked = User::where('is_blocked', true)
            ->where('last_risk_update_at', '>=', now()->subDays(7))
            ->count();

        return [
            'blocked_users' => $blockedUsers,
            'high_risk_users' => $highRiskUsers,
            'review_users' => $reviewUsers,
            'low_risk_users' => $lowRiskUsers,
            'recently_blocked_7d' => $recentlyBlocked,
            'distribution_by_bucket' => $riskDistribution,
            'thresholds' => [
                'blocking' => $blockingThreshold,
                'high_risk' => $highRiskThreshold,
                'review' => $reviewThreshold,
            ],
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the cache TTL in seconds.
     *
     * @return int
     */
    protected function getTtl(): int
    {
        return (int) config('dispute.cache_ttl', self::DEFAULT_TTL);
    }

    /**
     * Get the cache store to use.
     *
     * @return string
     */
    protected function getCacheStore(): string
    {
        return config('dispute.cache_store', 'redis');
    }
}
