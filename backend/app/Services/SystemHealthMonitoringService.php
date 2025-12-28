<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * SystemHealthMonitoringService - Operational Visibility (H.26)
 *
 * META-FIX (I.28): This service DELEGATES to EconomicImpactService
 * - No longer has assessEconomicImpact() logic (REMOVED)
 * - Delegates all economic impact assessment to unified authority
 *
 * PURPOSE:
 * - Provide dashboards and alerts for system health
 * - Detect mismatches, stuck funds, reconciliation gaps
 * - Real-time visibility into financial and operational state
 * - Proactive alerting before issues escalate
 *
 * MONITORED METRICS:
 * 1. Financial Health:
 *    - Wallet balance mismatches
 *    - Orphaned transactions
 *    - Allocation gaps
 *    - Stuck funds
 *
 * 2. Operational Health:
 *    - Stuck payments (pending > 24h)
 *    - Stuck investments (processing > 30min)
 *    - Failed jobs (>5% failure rate)
 *    - Queue backlog
 *
 * 3. System Health:
 *    - Database connectivity
 *    - Redis connectivity
 *    - Payment gateway status
 *    - API response times
 *
 * USAGE:
 * ```php
 * $monitor = app(SystemHealthMonitoringService::class);
 *
 * // Check all health metrics
 * $health = $monitor->checkAllMetrics();
 *
 * // Get dashboard data
 * $dashboard = $monitor->getDashboardData('financial_health');
 *
 * // Get active alerts
 * $alerts = $monitor->getActiveAlerts();
 * ```
 */
class SystemHealthMonitoringService
{
    /**
     * Unified economic impact service (DELEGATION)
     */
    private EconomicImpactService $economicImpact;

    public function __construct(EconomicImpactService $economicImpact)
    {
        $this->economicImpact = $economicImpact;
    }

    /**
     * Check all health metrics
     *
     * @return array ['overall_health' => string, 'metrics' => [...], 'critical_issues' => [...]]
     */
    public function checkAllMetrics(): array
    {
        $metrics = [
            'financial' => $this->checkFinancialHealth(),
            'operational' => $this->checkOperationalHealth(),
            'system' => $this->checkSystemHealth(),
        ];

        // Determine overall health
        $criticalIssues = [];
        foreach ($metrics as $category => $categoryMetrics) {
            foreach ($categoryMetrics as $metric) {
                if ($metric['severity'] === 'critical' && !$metric['is_healthy']) {
                    $criticalIssues[] = $metric;
                }
            }
        }

        $overallHealth = empty($criticalIssues) ? 'healthy' : 'critical';

        return [
            'overall_health' => $overallHealth,
            'metrics' => $metrics,
            'critical_issues' => $criticalIssues,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Check financial health metrics
     *
     * @return array
     */
    private function checkFinancialHealth(): array
    {
        return [
            'wallet_balance_mismatches' => $this->checkWalletBalanceMismatches(),
            'orphaned_transactions' => $this->checkOrphanedTransactions(),
            'allocation_gaps' => $this->checkAllocationGaps(),
            'stuck_funds' => $this->checkStuckFunds(),
        ];
    }

    /**
     * Check wallet balance mismatches (ENHANCED with economic impact metrics)
     *
     * @return array
     */
    private function checkWalletBalanceMismatches(): array
    {
        // Query wallets where stored balance != computed balance
        $mismatches = DB::select("
            SELECT
                w.id as wallet_id,
                w.user_id,
                w.balance_paise as stored_balance,
                (
                    COALESCE(
                        (SELECT SUM(amount_paise)
                         FROM transactions
                         WHERE wallet_id = w.id
                           AND type IN ('deposit', 'credit', 'bonus', 'refund', 'referral_bonus')
                           AND is_reversed = FALSE
                        ), 0
                    ) -
                    COALESCE(
                        (SELECT SUM(amount_paise)
                         FROM transactions
                         WHERE wallet_id = w.id
                           AND type IN ('debit', 'withdrawal', 'investment', 'fee', 'tds')
                           AND is_reversed = FALSE
                        ), 0
                    )
                ) as computed_balance,
                ABS(w.balance_paise - (
                    COALESCE(
                        (SELECT SUM(amount_paise)
                         FROM transactions
                         WHERE wallet_id = w.id
                           AND type IN ('deposit', 'credit', 'bonus', 'refund', 'referral_bonus')
                           AND is_reversed = FALSE
                        ), 0
                    ) -
                    COALESCE(
                        (SELECT SUM(amount_paise)
                         FROM transactions
                         WHERE wallet_id = w.id
                           AND type IN ('debit', 'withdrawal', 'investment', 'fee', 'tds')
                           AND is_reversed = FALSE
                        ), 0
                    )
                )) as discrepancy_paise
            FROM wallets w
            HAVING stored_balance != computed_balance
            LIMIT 100
        ");

        $count = count($mismatches);
        $isHealthy = $count === 0;

        // Calculate total monetary discrepancy
        $totalDiscrepancy = 0;
        foreach ($mismatches as $mismatch) {
            $totalDiscrepancy += $mismatch->discrepancy_paise;
        }
        $totalDiscrepancyRupees = $totalDiscrepancy / 100;

        // ECONOMIC IMPACT ASSESSMENT (DELEGATED to unified authority)
        // For balance mismatches, time-weighted risk is not applicable (use 0)
        // User impact is the count of affected wallets
        $impactLevel = $this->economicImpact->assessByValues($totalDiscrepancyRupees, 0, $count);

        // Create alerts for mismatches
        if (!$isHealthy) {
            foreach ($mismatches as $mismatch) {
                $this->createReconciliationAlert(
                    'balance_mismatch',
                    'high',
                    'wallet',
                    $mismatch->wallet_id,
                    $mismatch->user_id,
                    $mismatch->computed_balance,
                    $mismatch->stored_balance,
                    "Wallet balance mismatch: stored=₹" . ($mismatch->stored_balance / 100) .
                    ", computed=₹" . ($mismatch->computed_balance / 100)
                );
            }
        }

        // Enhanced message with economic context
        $message = $isHealthy
            ? "All wallet balances reconciled"
            : "₹" . number_format($totalDiscrepancyRupees, 2) . " total discrepancy across {$count} wallets. Impact: {$impactLevel}";

        return $this->createMetric(
            'wallet_balance_mismatches',
            'financial',
            $count > 0 ? 'error' : 'info',
            $count,
            0, // warning threshold
            0, // critical threshold
            'count',
            $isHealthy,
            $message,
            [
                'mismatches' => array_slice($mismatches, 0, 10), // First 10
                'total_discrepancy' => $totalDiscrepancyRupees,
                'unique_users_affected' => $count,
                'economic_impact' => $impactLevel,
            ]
        );
    }

    /**
     * Check orphaned transactions
     *
     * @return array
     */
    private function checkOrphanedTransactions(): array
    {
        // Transactions with no paired transaction (double-entry violation)
        $orphaned = DB::table('transactions')
            ->whereNull('paired_transaction_id')
            ->whereIn('type', ['deposit', 'withdrawal']) // Should have pairs
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        $isHealthy = $orphaned === 0;

        return $this->createMetric(
            'orphaned_transactions',
            'financial',
            $orphaned > 0 ? 'warning' : 'info',
            $orphaned,
            0,
            10,
            'count',
            $isHealthy,
            $isHealthy ? "No orphaned transactions" : "{$orphaned} orphaned transactions found"
        );
    }

    /**
     * Check allocation gaps
     *
     * @return array
     */
    private function checkAllocationGaps(): array
    {
        // Payments marked 'paid' but no investment allocated
        $gaps = DB::table('payments')
            ->leftJoin('investments', 'payments.id', '=', 'investments.payment_id')
            ->whereNull('investments.id')
            ->where('payments.status', 'paid')
            ->where('payments.created_at', '>', now()->subDays(7))
            ->count();

        $isHealthy = $gaps === 0;

        return $this->createMetric(
            'allocation_gaps',
            'financial',
            $gaps > 0 ? 'error' : 'info',
            $gaps,
            0,
            5,
            'count',
            $isHealthy,
            $isHealthy ? "No allocation gaps" : "{$gaps} payments without allocations"
        );
    }

    /**
     * Check stuck funds
     *
     * @return array
     */
    private function checkStuckFunds(): array
    {
        // Funds in "processing" state for too long
        $stuck = DB::table('payments')
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subHours(1))
            ->sum('amount');

        $isHealthy = $stuck == 0;

        return $this->createMetric(
            'stuck_funds',
            'financial',
            $stuck > 0 ? 'warning' : 'info',
            $stuck,
            10000, // ₹10K warning
            50000, // ₹50K critical
            'rupees',
            $isHealthy,
            $isHealthy ? "No stuck funds" : "₹{$stuck} in stuck payments"
        );
    }

    /**
     * Check operational health metrics
     *
     * @return array
     */
    private function checkOperationalHealth(): array
    {
        return [
            'stuck_payments' => $this->checkStuckPayments(),
            'stuck_investments' => $this->checkStuckInvestments(),
            'failed_jobs' => $this->checkFailedJobs(),
            'queue_backlog' => $this->checkQueueBacklog(),
        ];
    }

    /**
     * Check stuck payments (ENHANCED with economic impact metrics)
     *
     * @return array
     */
    private function checkStuckPayments(): array
    {
        // Get stuck payment details with economic impact
        $stuckPayments = DB::select("
            SELECT
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total_amount,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_hours_stuck,
                MAX(TIMESTAMPDIFF(HOUR, created_at, NOW())) as max_hours_stuck
            FROM payments
            WHERE status = 'pending'
              AND created_at < NOW() - INTERVAL 24 HOUR
        ");

        $data = $stuckPayments[0];
        $count = $data->count ?? 0;
        $totalAmount = $data->total_amount ?? 0;
        $uniqueUsers = $data->unique_users ?? 0;
        $avgHoursStuck = round($data->avg_hours_stuck ?? 0, 1);
        $maxHoursStuck = round($data->max_hours_stuck ?? 0, 1);

        $isHealthy = $count === 0;

        // ECONOMIC IMPACT ASSESSMENT (DELEGATED to unified authority)
        $impactLevel = $this->economicImpact->assessByValues($totalAmount, $avgHoursStuck, $uniqueUsers);

        // Enhanced message with economic context
        $message = $isHealthy
            ? "No stuck payments"
            : "₹" . number_format($totalAmount, 2) . " stuck for {$avgHoursStuck}h (max {$maxHoursStuck}h) affecting {$uniqueUsers} users ({$count} payments). Impact: {$impactLevel}";

        return $this->createMetric(
            'stuck_payments',
            'operational',
            $count > 10 ? 'error' : ($count > 0 ? 'warning' : 'info'),
            $count,
            5,
            10,
            'count',
            $isHealthy,
            $message,
            [
                'monetary_exposure' => $totalAmount,
                'avg_hours_stuck' => $avgHoursStuck,
                'max_hours_stuck' => $maxHoursStuck,
                'unique_users_affected' => $uniqueUsers,
                'economic_impact' => $impactLevel,
            ]
        );
    }

    /**
     * Check stuck investments (ENHANCED with economic impact metrics)
     *
     * @return array
     */
    private function checkStuckInvestments(): array
    {
        // Get stuck investment details with economic impact
        $stuckInvestments = DB::select("
            SELECT
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total_amount,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(TIMESTAMPDIFF(MINUTE, updated_at, NOW())) as avg_minutes_stuck,
                MAX(TIMESTAMPDIFF(MINUTE, updated_at, NOW())) as max_minutes_stuck
            FROM investments
            WHERE allocation_status = 'processing'
              AND updated_at < NOW() - INTERVAL 30 MINUTE
        ");

        $data = $stuckInvestments[0];
        $count = $data->count ?? 0;
        $totalAmount = $data->total_amount ?? 0;
        $uniqueUsers = $data->unique_users ?? 0;
        $avgMinutesStuck = round($data->avg_minutes_stuck ?? 0, 1);
        $maxMinutesStuck = round($data->max_minutes_stuck ?? 0, 1);

        $avgHoursStuck = round($avgMinutesStuck / 60, 1);

        $isHealthy = $count === 0;

        // ECONOMIC IMPACT ASSESSMENT (DELEGATED to unified authority)
        $impactLevel = $this->economicImpact->assessByValues($totalAmount, $avgHoursStuck, $uniqueUsers);

        // Enhanced message with economic context
        $message = $isHealthy
            ? "No stuck investments"
            : "₹" . number_format($totalAmount, 2) . " stuck for {$avgMinutesStuck}min (max {$maxMinutesStuck}min) affecting {$uniqueUsers} users ({$count} investments). Impact: {$impactLevel}";

        return $this->createMetric(
            'stuck_investments',
            'operational',
            $count > 5 ? 'error' : ($count > 0 ? 'warning' : 'info'),
            $count,
            3,
            5,
            'count',
            $isHealthy,
            $message,
            [
                'monetary_exposure' => $totalAmount,
                'avg_minutes_stuck' => $avgMinutesStuck,
                'max_minutes_stuck' => $maxMinutesStuck,
                'unique_users_affected' => $uniqueUsers,
                'economic_impact' => $impactLevel,
            ]
        );
    }

    /**
     * Check failed jobs
     *
     * @return array
     */
    private function checkFailedJobs(): array
    {
        $failed = DB::table('failed_jobs')
            ->where('failed_at', '>', now()->subHours(24))
            ->count();

        $total = DB::table('job_executions')
            ->where('created_at', '>', now()->subHours(24))
            ->count();

        $failureRate = $total > 0 ? ($failed / $total) * 100 : 0;
        $isHealthy = $failureRate < 5;

        return $this->createMetric(
            'failed_jobs',
            'operational',
            $failureRate > 10 ? 'error' : ($failureRate > 5 ? 'warning' : 'info'),
            $failureRate,
            5,
            10,
            'percentage',
            $isHealthy,
            $isHealthy ? "Job failure rate normal" : "{$failureRate}% job failure rate",
            ['failed_count' => $failed, 'total_count' => $total]
        );
    }

    /**
     * Check queue backlog
     *
     * @return array
     */
    private function checkQueueBacklog(): array
    {
        // This is a simplified check - actual implementation depends on queue driver
        $backlog = Cache::get('queue_size', 0);

        $isHealthy = $backlog < 1000;

        return $this->createMetric(
            'queue_backlog',
            'operational',
            $backlog > 5000 ? 'error' : ($backlog > 1000 ? 'warning' : 'info'),
            $backlog,
            1000,
            5000,
            'count',
            $isHealthy,
            $isHealthy ? "Queue backlog normal" : "{$backlog} jobs in queue"
        );
    }

    /**
     * Check system health metrics
     *
     * @return array
     */
    private function checkSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'redis' => $this->checkRedisHealth(),
        ];
    }

    /**
     * Check database health
     *
     * @return array
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000; // ms

            $isHealthy = $responseTime < 100;

            return $this->createMetric(
                'database',
                'system',
                $responseTime > 500 ? 'error' : ($responseTime > 100 ? 'warning' : 'info'),
                round($responseTime, 2),
                100,
                500,
                'milliseconds',
                $isHealthy,
                $isHealthy ? "Database responsive" : "Database slow: {$responseTime}ms"
            );

        } catch (\Throwable $e) {
            return $this->createMetric(
                'database',
                'system',
                'critical',
                0,
                100,
                500,
                'milliseconds',
                false,
                "Database unreachable: " . $e->getMessage()
            );
        }
    }

    /**
     * Check Redis health
     *
     * @return array
     */
    private function checkRedisHealth(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', 'ok', 1);
            Cache::get('health_check');
            $responseTime = (microtime(true) - $start) * 1000;

            $isHealthy = $responseTime < 50;

            return $this->createMetric(
                'redis',
                'system',
                $responseTime > 200 ? 'error' : ($responseTime > 50 ? 'warning' : 'info'),
                round($responseTime, 2),
                50,
                200,
                'milliseconds',
                $isHealthy,
                $isHealthy ? "Redis responsive" : "Redis slow: {$responseTime}ms"
            );

        } catch (\Throwable $e) {
            return $this->createMetric(
                'redis',
                'system',
                'critical',
                0,
                50,
                200,
                'milliseconds',
                false,
                "Redis unreachable: " . $e->getMessage()
            );
        }
    }

    /**
     * Create metric array
     *
     * @param string $name
     * @param string $category
     * @param string $severity
     * @param float $value
     * @param float|null $thresholdWarning
     * @param float|null $thresholdCritical
     * @param string|null $unit
     * @param bool $isHealthy
     * @param string $message
     * @param array $details
     * @return array
     */
    private function createMetric(
        string $name,
        string $category,
        string $severity,
        float $value,
        ?float $thresholdWarning,
        ?float $thresholdCritical,
        ?string $unit,
        bool $isHealthy,
        string $message,
        array $details = []
    ): array {
        // Store metric in database for historical tracking
        DB::table('system_health_metrics')->updateOrInsert(
            ['metric_name' => $name],
            [
                'category' => $category,
                'severity' => $severity,
                'current_value' => $value,
                'threshold_warning' => $thresholdWarning,
                'threshold_critical' => $thresholdCritical,
                'unit' => $unit,
                'is_healthy' => $isHealthy,
                'health_message' => $message,
                'last_checked_at' => now(),
                'unhealthy_since' => $isHealthy ? null : DB::raw('COALESCE(unhealthy_since, NOW())'),
                'details' => json_encode($details),
                'updated_at' => now(),
            ]
        );

        return [
            'name' => $name,
            'category' => $category,
            'severity' => $severity,
            'current_value' => $value,
            'threshold_warning' => $thresholdWarning,
            'threshold_critical' => $thresholdCritical,
            'unit' => $unit,
            'is_healthy' => $isHealthy,
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * Create reconciliation alert
     *
     * @param string $alertType
     * @param string $severity
     * @param string $entityType
     * @param int $entityId
     * @param int|null $userId
     * @param float|null $expectedValue
     * @param float|null $actualValue
     * @param string $description
     * @return void
     */
    private function createReconciliationAlert(
        string $alertType,
        string $severity,
        string $entityType,
        int $entityId,
        ?int $userId,
        ?float $expectedValue,
        ?float $actualValue,
        string $description
    ): void {
        // Check if alert already exists
        $exists = DB::table('reconciliation_alerts')
            ->where('alert_type', $alertType)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('resolved', false)
            ->exists();

        if (!$exists) {
            DB::table('reconciliation_alerts')->insert([
                'alert_type' => $alertType,
                'severity' => $severity,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'user_id' => $userId,
                'expected_value' => $expectedValue,
                'actual_value' => $actualValue,
                'discrepancy' => $expectedValue && $actualValue ? abs($expectedValue - $actualValue) : null,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get active alerts
     *
     * @param string|null $severity
     * @return array
     */
    public function getActiveAlerts(?string $severity = null): array
    {
        $query = DB::table('reconciliation_alerts')
            ->where('resolved', false)
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'asc');

        if ($severity) {
            $query->where('severity', $severity);
        }

        return $query->get()->map(function ($alert) {
            return [
                'id' => $alert->id,
                'alert_type' => $alert->alert_type,
                'severity' => $alert->severity,
                'entity_type' => $alert->entity_type,
                'entity_id' => $alert->entity_id,
                'description' => $alert->description,
                'discrepancy' => $alert->discrepancy,
                'created_at' => $alert->created_at,
            ];
        })->toArray();
    }

    /**
     * Get dashboard data
     *
     * @param string $dashboardName
     * @return array
     */
    public function getDashboardData(string $dashboardName): array
    {
        $health = $this->checkAllMetrics();

        switch ($dashboardName) {
            case 'financial_health':
                return [
                    'name' => 'Financial Health',
                    'overall_health' => $health['overall_health'],
                    'metrics' => $health['metrics']['financial'],
                    'alerts' => $this->getActiveAlerts(),
                ];

            case 'operations':
                return [
                    'name' => 'Operational Health',
                    'overall_health' => $health['overall_health'],
                    'metrics' => $health['metrics']['operational'],
                    'stuck_states' => app(StuckStateDetectorService::class)->getStatistics(),
                ];

            case 'system':
                return [
                    'name' => 'System Health',
                    'overall_health' => $health['overall_health'],
                    'metrics' => $health['metrics']['system'],
                ];

            default:
                return $health;
        }
    }

    /**
     * REMOVED: assessEconomicImpact() - DELEGATED to EconomicImpactService (I.28)
     *
     * This method has been removed to eliminate fragmentation.
     * All economic impact assessment now happens in EconomicImpactService.
     *
     * Migration:
     * - Old: $this->assessEconomicImpact($amount, $hours, $users)
     * - New: $this->economicImpact->assessByValues($amount, $hours, $users)
     */
}
