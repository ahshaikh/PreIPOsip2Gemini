<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CampaignMetricsService
{
    /**
     * Get comprehensive business metrics for a campaign
     *
     * @param Campaign $campaign
     * @param int $days Number of days for trend analysis (default: 30)
     * @return array
     */
    public function getComprehensiveMetrics(Campaign $campaign, int $days = 30): array
    {
        return [
            'basic_metrics' => $this->getBasicMetrics($campaign),
            'financial_metrics' => $this->getFinancialMetrics($campaign),
            'conversion_metrics' => $this->getConversionMetrics($campaign),
            'user_metrics' => $this->getUserMetrics($campaign),
            'abuse_signals' => $this->getAbuseSignals($campaign),
            'trend_analysis' => $this->getTrendAnalysis($campaign, $days),
            'revenue_impact' => $this->getRevenueImpact($campaign),
        ];
    }

    /**
     * Get basic campaign metrics
     */
    protected function getBasicMetrics(Campaign $campaign): array
    {
        return [
            'total_usages' => $campaign->usage_count,
            'usage_limit' => $campaign->usage_limit,
            'remaining_usage' => $campaign->remaining_usage,
            'usage_percentage' => $campaign->usage_percentage,
            'is_live' => $campaign->is_live,
            'days_until_expiry' => $campaign->end_at ?
                max(0, now()->diffInDays($campaign->end_at, false)) : null,
        ];
    }

    /**
     * Get financial metrics
     */
    protected function getFinancialMetrics(Campaign $campaign): array
    {
        $usages = CampaignUsage::where('campaign_id', $campaign->id);

        return [
            'total_discount_given' => round($usages->sum('discount_applied'), 2),
            'total_original_amount' => round($usages->sum('original_amount'), 2),
            'total_final_amount' => round($usages->sum('final_amount'), 2),
            'average_discount' => round($usages->avg('discount_applied'), 2),
            'average_transaction_value' => round($usages->avg('original_amount'), 2),
            'max_discount_applied' => round($usages->max('discount_applied'), 2),
            'min_discount_applied' => round($usages->min('discount_applied'), 2),
        ];
    }

    /**
     * Get conversion metrics
     */
    protected function getConversionMetrics(Campaign $campaign): array
    {
        // Views vs Applications (if tracking is implemented)
        // For now, we'll calculate based on validation attempts
        $totalUsages = $campaign->usage_count;
        $uniqueUsers = CampaignUsage::where('campaign_id', $campaign->id)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'unique_users' => $uniqueUsers,
            'average_usages_per_user' => $uniqueUsers > 0 ?
                round($totalUsages / $uniqueUsers, 2) : 0,
            'terms_acceptance_rate' => $this->getTermsAcceptanceRate($campaign),
            'disclaimer_acknowledgment_rate' => $this->getDisclaimerAcknowledgmentRate($campaign),
        ];
    }

    /**
     * Get user behavior metrics
     */
    protected function getUserMetrics(Campaign $campaign): array
    {
        $usages = CampaignUsage::where('campaign_id', $campaign->id);

        // User distribution by usage count
        $userUsageCounts = $usages->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->pluck('count');

        return [
            'new_users' => $this->getNewUserCount($campaign),
            'returning_users' => $this->getReturningUserCount($campaign),
            'power_users' => $userUsageCounts->filter(fn($c) => $c >= 3)->count(),
            'average_days_between_usage' => $this->getAverageDaysBetweenUsage($campaign),
        ];
    }

    /**
     * Get abuse/fraud signals
     */
    protected function getAbuseSignals(Campaign $campaign): array
    {
        $usages = CampaignUsage::where('campaign_id', $campaign->id);

        // Suspicious patterns
        $suspiciousIPs = $usages->select('ip_address', DB::raw('COUNT(DISTINCT user_id) as user_count'))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('user_count', '>', 3) // Same IP, multiple users
            ->count();

        $rapidUsage = $usages->select('user_id')
            ->whereRaw('used_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 5')
            ->count();

        return [
            'suspicious_ip_count' => $suspiciousIPs,
            'rapid_usage_count' => $rapidUsage,
            'terms_not_accepted_count' => $usages->where('terms_accepted', false)->count(),
            'duplicate_attempts' => $this->getDuplicateAttempts($campaign),
            'risk_score' => $this->calculateRiskScore($suspiciousIPs, $rapidUsage),
        ];
    }

    /**
     * Get trend analysis
     */
    protected function getTrendAnalysis(Campaign $campaign, int $days): array
    {
        $usageByDay = DB::table('campaign_usages')
            ->where('campaign_id', $campaign->id)
            ->where('used_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(used_at) as date, COUNT(*) as count, SUM(discount_applied) as total_discount')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $dailyCounts = $usageByDay->pluck('count')->toArray();
        $trend = $this->calculateTrend($dailyCounts);

        return [
            'usage_by_day' => $usageByDay,
            'trend_direction' => $trend['direction'], // 'increasing', 'decreasing', 'stable'
            'trend_percentage' => $trend['percentage'],
            'peak_day' => $usageByDay->sortByDesc('count')->first(),
            'average_daily_usage' => round($usageByDay->avg('count'), 2),
        ];
    }

    /**
     * Get revenue impact metrics
     */
    protected function getRevenueImpact(Campaign $campaign): array
    {
        $usages = CampaignUsage::where('campaign_id', $campaign->id);

        $totalRevenueLost = $usages->sum('discount_applied');
        $totalRevenuePotential = $usages->sum('original_amount');
        $actualRevenue = $usages->sum('final_amount');

        // ROI calculation (simplified - assumes campaign brings new business)
        $estimatedNewBusinessPercentage = 0.70; // Assume 70% wouldn't have purchased without campaign
        $valueOfNewBusiness = $actualRevenue * $estimatedNewBusinessPercentage;
        $roi = $totalRevenueLost > 0 ?
            (($valueOfNewBusiness - $totalRevenueLost) / $totalRevenueLost) * 100 : 0;

        return [
            'total_revenue_lost' => round($totalRevenueLost, 2),
            'total_revenue_potential' => round($totalRevenuePotential, 2),
            'actual_revenue' => round($actualRevenue, 2),
            'revenue_retention_rate' => $totalRevenuePotential > 0 ?
                round(($actualRevenue / $totalRevenuePotential) * 100, 2) : 0,
            'estimated_roi_percentage' => round($roi, 2),
            'cost_per_acquisition' => $campaign->usage_count > 0 ?
                round($totalRevenueLost / $campaign->usage_count, 2) : 0,
        ];
    }

    /**
     * Helper methods
     */
    protected function getTermsAcceptanceRate(Campaign $campaign): float
    {
        $total = CampaignUsage::where('campaign_id', $campaign->id)->count();
        if ($total === 0) return 0.0;

        $accepted = CampaignUsage::where('campaign_id', $campaign->id)
            ->where('terms_accepted', true)
            ->count();

        return round(($accepted / $total) * 100, 2);
    }

    protected function getDisclaimerAcknowledgmentRate(Campaign $campaign): float
    {
        $total = CampaignUsage::where('campaign_id', $campaign->id)->count();
        if ($total === 0) return 0.0;

        $acknowledged = CampaignUsage::where('campaign_id', $campaign->id)
            ->where('disclaimer_acknowledged', true)
            ->count();

        return round(($acknowledged / $total) * 100, 2);
    }

    protected function getNewUserCount(Campaign $campaign): int
    {
        // Users who used campaign only once
        return DB::table('campaign_usages')
            ->where('campaign_id', $campaign->id)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();
    }

    protected function getReturningUserCount(Campaign $campaign): int
    {
        // Users who used campaign more than once
        return DB::table('campaign_usages')
            ->where('campaign_id', $campaign->id)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }

    protected function getAverageDaysBetweenUsage(Campaign $campaign): float
    {
        // Calculate average time between consecutive usages
        $usages = DB::table('campaign_usages')
            ->where('campaign_id', $campaign->id)
            ->orderBy('used_at')
            ->pluck('used_at')
            ->toArray();

        if (count($usages) < 2) return 0.0;

        $intervals = [];
        for ($i = 1; $i < count($usages); $i++) {
            $intervals[] = strtotime($usages[$i]) - strtotime($usages[$i - 1]);
        }

        $averageSeconds = array_sum($intervals) / count($intervals);
        return round($averageSeconds / 86400, 2); // Convert to days
    }

    protected function getDuplicateAttempts(Campaign $campaign): int
    {
        // Count how many times users tried to use same campaign on same entity
        // This would be tracked in application logs, simplified here
        return 0; // Placeholder
    }

    protected function calculateRiskScore(int $suspiciousIPs, int $rapidUsage): string
    {
        $score = $suspiciousIPs * 2 + $rapidUsage;

        if ($score >= 10) return 'HIGH';
        if ($score >= 5) return 'MEDIUM';
        return 'LOW';
    }

    protected function calculateTrend(array $dailyCounts): array
    {
        if (empty($dailyCounts)) {
            return ['direction' => 'stable', 'percentage' => 0];
        }

        $half = ceil(count($dailyCounts) / 2);
        $firstHalf = array_slice($dailyCounts, 0, $half);
        $secondHalf = array_slice($dailyCounts, $half);

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($firstAvg == 0) {
            return ['direction' => 'stable', 'percentage' => 0];
        }

        $percentageChange = (($secondAvg - $firstAvg) / $firstAvg) * 100;

        if (abs($percentageChange) < 10) {
            $direction = 'stable';
        } else {
            $direction = $percentageChange > 0 ? 'increasing' : 'decreasing';
        }

        return [
            'direction' => $direction,
            'percentage' => round(abs($percentageChange), 2),
        ];
    }

    /**
     * Cache comprehensive metrics for performance
     */
    public function getCachedMetrics(Campaign $campaign, int $cacheDuration = 300): array
    {
        $cacheKey = "campaign_metrics:{$campaign->id}";

        return Cache::remember($cacheKey, $cacheDuration, function () use ($campaign) {
            return $this->getComprehensiveMetrics($campaign);
        });
    }
}
