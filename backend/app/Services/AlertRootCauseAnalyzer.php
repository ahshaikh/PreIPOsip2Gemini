<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AlertRootCauseAnalyzer - Root Cause Tracking (Addressing Audit Feedback)
 *
 * META-FIX (I.28): This service DELEGATES to EconomicImpactService
 * - No longer has calculateSeverity() logic (REMOVED)
 * - Delegates all economic impact assessment to unified authority
 *
 * PURPOSE:
 * - "Alert volume can itself become a failure mode" - prevent alert fatigue
 * - Group similar alerts by root cause
 * - Identify systemic issues vs one-off incidents
 * - Prevent symptoms from masking underlying problems
 *
 * ANTI-PATTERN:
 * ```
 * Alert: Payment #123 stuck
 * Alert: Payment #124 stuck
 * Alert: Payment #125 stuck
 * ...
 * Alert: Payment #170 stuck (47 individual alerts)
 * ```
 *
 * CORRECT PATTERN:
 * ```
 * ROOT CAUSE: Payment gateway timeout (Razorpay API)
 *   - First occurrence: 2h ago
 *   - Affected: 47 payments (₹2.3L total)
 *   - Severity: HIGH
 *   - Action: Monitor Razorpay status page, process queue after recovery
 * ```
 *
 * USAGE:
 * ```php
 * $analyzer = app(AlertRootCauseAnalyzer::class);
 *
 * // Analyze all unresolved alerts
 * $rootCauses = $analyzer->identifyRootCauses();
 *
 * // Get aggregated view
 * $summary = $analyzer->getAggregatedAlerts();
 * ```
 */
class AlertRootCauseAnalyzer
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
     * Identify root causes for all unresolved alerts
     *
     * @return array ['root_causes' => [...], 'ungrouped_alerts' => int]
     */
    public function identifyRootCauses(): array
    {
        $stuckAlerts = DB::table('stuck_state_alerts')
            ->where('reviewed', false)
            ->whereNull('root_cause')
            ->get();

        $reconciliationAlerts = DB::table('reconciliation_alerts')
            ->where('resolved', false)
            ->whereNull('root_cause')
            ->get();

        $identifiedRootCauses = [];

        // Pattern 1: Payment gateway timeouts (multiple stuck payments in short time)
        $rootCause = $this->detectPaymentGatewayIssues($stuckAlerts);
        if ($rootCause) {
            $identifiedRootCauses[] = $rootCause;
        }

        // Pattern 2: Service outages (multiple stuck investments/allocations)
        $rootCause = $this->detectServiceOutages($stuckAlerts);
        if ($rootCause) {
            $identifiedRootCauses[] = $rootCause;
        }

        // Pattern 3: Webhook delivery failures (pattern detection)
        $rootCause = $this->detectWebhookFailures($reconciliationAlerts);
        if ($rootCause) {
            $identifiedRootCauses[] = $rootCause;
        }

        // Pattern 4: Concurrency bugs (same user/entity affected multiple times)
        $rootCause = $this->detectConcurrencyBugs($stuckAlerts, $reconciliationAlerts);
        if ($rootCause) {
            $identifiedRootCauses[] = $rootCause;
        }

        return [
            'root_causes' => $identifiedRootCauses,
            'ungrouped_alerts' => $this->countUngroupedAlerts(),
        ];
    }

    /**
     * Detect payment gateway issues (pattern: multiple stuck payments in short time)
     *
     * @param \Illuminate\Support\Collection $alerts
     * @return array|null
     */
    private function detectPaymentGatewayIssues($alerts): ?array
    {
        // Pattern: >10 stuck payments created within 1 hour window
        $stuckPayments = $alerts->where('entity_type', 'payment')
            ->where('alert_type', 'stuck_payment')
            ->where('created_at', '>', now()->subHours(2));

        if ($stuckPayments->count() < 10) {
            return null;
        }

        // Calculate impact
        $affectedPaymentIds = $stuckPayments->pluck('entity_id');
        $paymentDetails = DB::table('payments')
            ->whereIn('id', $affectedPaymentIds)
            ->select(DB::raw('
                COUNT(DISTINCT id) as payment_count,
                COUNT(DISTINCT user_id) as user_count,
                SUM(amount) as total_amount
            '))
            ->first();

        // Create or update root cause
        $rootCauseId = $this->createOrUpdateRootCause(
            'payment_gateway_timeout',
            "Payment gateway timeout detected - {$paymentDetails->payment_count} payments stuck",
            $paymentDetails->payment_count,
            $paymentDetails->total_amount,
            $paymentDetails->user_count,
            $stuckPayments->min('created_at')
        );

        // Link alerts to root cause
        foreach ($stuckPayments as $alert) {
            DB::table('stuck_state_alerts')
                ->where('id', $alert->id)
                ->update([
                    'root_cause' => 'payment_gateway_timeout',
                    'root_cause_group' => "root_cause_{$rootCauseId}",
                    'root_cause_identified_at' => now(),
                ]);
        }

        return [
            'root_cause_id' => $rootCauseId,
            'type' => 'payment_gateway_timeout',
            'affected_count' => $paymentDetails->payment_count,
            'monetary_impact' => $paymentDetails->total_amount,
            'users_affected' => $paymentDetails->user_count,
        ];
    }

    /**
     * Detect service outages (pattern: multiple stuck allocations)
     *
     * @param \Illuminate\Support\Collection $alerts
     * @return array|null
     */
    private function detectServiceOutages($alerts): ?array
    {
        // Pattern: >5 stuck investments within 30 minutes
        $stuckInvestments = $alerts->where('entity_type', 'investment')
            ->where('alert_type', 'stuck_allocation')
            ->where('created_at', '>', now()->subMinutes(30));

        if ($stuckInvestments->count() < 5) {
            return null;
        }

        $affectedInvestmentIds = $stuckInvestments->pluck('entity_id');
        $investmentDetails = DB::table('investments')
            ->whereIn('id', $affectedInvestmentIds)
            ->select(DB::raw('
                COUNT(DISTINCT id) as investment_count,
                COUNT(DISTINCT user_id) as user_count,
                SUM(amount) as total_amount
            '))
            ->first();

        $rootCauseId = $this->createOrUpdateRootCause(
            'allocation_service_timeout',
            "Allocation service timeout - {$investmentDetails->investment_count} allocations stuck",
            $investmentDetails->investment_count,
            $investmentDetails->total_amount,
            $investmentDetails->user_count,
            $stuckInvestments->min('created_at')
        );

        foreach ($stuckInvestments as $alert) {
            DB::table('stuck_state_alerts')
                ->where('id', $alert->id)
                ->update([
                    'root_cause' => 'allocation_service_timeout',
                    'root_cause_group' => "root_cause_{$rootCauseId}",
                    'root_cause_identified_at' => now(),
                ]);
        }

        return [
            'root_cause_id' => $rootCauseId,
            'type' => 'allocation_service_timeout',
            'affected_count' => $investmentDetails->investment_count,
            'monetary_impact' => $investmentDetails->total_amount,
            'users_affected' => $investmentDetails->user_count,
        ];
    }

    /**
     * Detect webhook delivery failures
     *
     * @param \Illuminate\Support\Collection $alerts
     * @return array|null
     */
    private function detectWebhookFailures($alerts): ?array
    {
        // Pattern: Multiple "orphaned payment" or "missing webhook" alerts
        $webhookFailures = $alerts->whereIn('alert_type', ['orphaned_payment', 'missing_webhook'])
            ->where('created_at', '>', now()->subHours(1));

        if ($webhookFailures->count() < 5) {
            return null;
        }

        $rootCauseId = $this->createOrUpdateRootCause(
            'webhook_delivery_failure',
            "Webhook delivery failures detected - {$webhookFailures->count()} missed webhooks",
            $webhookFailures->count(),
            0, // No direct monetary impact (webhooks themselves have no value)
            $webhookFailures->count(), // Each webhook is a separate event
            $webhookFailures->min('created_at')
        );

        foreach ($webhookFailures as $alert) {
            DB::table('reconciliation_alerts')
                ->where('id', $alert->id)
                ->update([
                    'root_cause' => 'webhook_delivery_failure',
                    'root_cause_group' => "root_cause_{$rootCauseId}",
                    'root_cause_identified_at' => now(),
                ]);
        }

        return [
            'root_cause_id' => $rootCauseId,
            'type' => 'webhook_delivery_failure',
            'affected_count' => $webhookFailures->count(),
        ];
    }

    /**
     * Detect concurrency bugs (same entity affected multiple times)
     *
     * @param \Illuminate\Support\Collection $stuckAlerts
     * @param \Illuminate\Support\Collection $reconciliationAlerts
     * @return array|null
     */
    private function detectConcurrencyBugs($stuckAlerts, $reconciliationAlerts): ?array
    {
        // Pattern: Same user_id appears in >3 alerts within 1 hour
        $userAlerts = $stuckAlerts->merge($reconciliationAlerts)
            ->whereNotNull('user_id')
            ->where('created_at', '>', now()->subHour())
            ->groupBy('user_id');

        $suspiciousUsers = $userAlerts->filter(function ($alerts) {
            return $alerts->count() >= 3;
        });

        if ($suspiciousUsers->isEmpty()) {
            return null;
        }

        $affectedUserIds = $suspiciousUsers->keys();
        $totalAlerts = $suspiciousUsers->flatten()->count();

        $rootCauseId = $this->createOrUpdateRootCause(
            'concurrency_bug',
            "Potential concurrency bug - {$affectedUserIds->count()} users with multiple alerts",
            $totalAlerts,
            0, // Monetary impact varies
            $affectedUserIds->count(),
            now()
        );

        // Mark alerts as part of concurrency bug root cause
        foreach ($suspiciousUsers->flatten() as $alert) {
            $table = isset($alert->stuck_state) ? 'stuck_state_alerts' : 'reconciliation_alerts';
            DB::table($table)
                ->where('id', $alert->id)
                ->update([
                    'root_cause' => 'concurrency_bug',
                    'root_cause_group' => "root_cause_{$rootCauseId}",
                    'root_cause_identified_at' => now(),
                ]);
        }

        return [
            'root_cause_id' => $rootCauseId,
            'type' => 'concurrency_bug',
            'affected_count' => $totalAlerts,
            'users_affected' => $affectedUserIds->count(),
        ];
    }

    /**
     * Create or update root cause record
     *
     * @param string $type
     * @param string $description
     * @param int $affectedCount
     * @param float $monetaryImpact
     * @param int $usersAffected
     * @param string $firstOccurrence
     * @return int Root cause ID
     */
    private function createOrUpdateRootCause(
        string $type,
        string $description,
        int $affectedCount,
        float $monetaryImpact,
        int $usersAffected,
        string $firstOccurrence
    ): int {
        // Check if root cause already exists (not yet resolved)
        $existing = DB::table('alert_root_causes')
            ->where('root_cause_type', $type)
            ->where('is_resolved', false)
            ->where('first_occurrence', '>', now()->subHours(6)) // Same incident if within 6h
            ->first();

        if ($existing) {
            // Update existing root cause
            DB::table('alert_root_causes')
                ->where('id', $existing->id)
                ->update([
                    'affected_alerts_count' => DB::raw("affected_alerts_count + {$affectedCount}"),
                    'total_monetary_impact' => DB::raw("total_monetary_impact + {$monetaryImpact}"),
                    'affected_users_count' => DB::raw("GREATEST(affected_users_count, {$usersAffected})"),
                    'last_occurrence' => now(),
                    'updated_at' => now(),
                ]);

            return $existing->id;
        }

        // Create new root cause
        // DELEGATED to unified authority (I.28)
        // For root causes, calculate hours stuck from first occurrence
        $hoursStuck = Carbon::parse($firstOccurrence)->diffInHours(now());
        $severity = strtolower($this->economicImpact->assessByValues($monetaryImpact, $hoursStuck, $usersAffected));

        return DB::table('alert_root_causes')->insertGetId([
            'root_cause_type' => $type,
            'description' => $description,
            'affected_alerts_count' => $affectedCount,
            'total_monetary_impact' => $monetaryImpact,
            'affected_users_count' => $usersAffected,
            'first_occurrence' => $firstOccurrence,
            'last_occurrence' => now(),
            'severity' => $severity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * REMOVED: calculateSeverity() - DELEGATED to EconomicImpactService (I.28)
     *
     * This method has been removed to eliminate fragmentation.
     * All severity/impact assessment now happens in EconomicImpactService.
     *
     * Migration:
     * - Old: $this->calculateSeverity($count, $amount, $users)
     * - New: $this->economicImpact->assessByValues($amount, $hours, $users)
     *
     * Note: For root causes, calculate hours from first_occurrence
     */

    /**
     * Get aggregated view of alerts grouped by root cause
     *
     * @return array
     */
    public function getAggregatedAlerts(): array
    {
        $rootCauses = DB::table('alert_root_causes')
            ->where('is_resolved', false)
            ->orderBy('severity', 'desc')
            ->orderBy('first_occurrence', 'asc')
            ->get();

        return [
            'root_causes' => $rootCauses->map(function ($rc) {
                return [
                    'id' => $rc->id,
                    'type' => $rc->root_cause_type,
                    'description' => $rc->description,
                    'severity' => $rc->severity,
                    'affected_alerts' => $rc->affected_alerts_count,
                    'monetary_impact' => $rc->total_monetary_impact,
                    'users_affected' => $rc->affected_users_count,
                    'first_occurrence' => $rc->first_occurrence,
                    'duration' => Carbon::parse($rc->first_occurrence)->diffForHumans(),
                ];
            })->toArray(),
            'ungrouped_alerts' => $this->countUngroupedAlerts(),
        ];
    }

    /**
     * Count alerts not yet grouped by root cause
     *
     * @return int
     */
    private function countUngroupedAlerts(): int
    {
        $stuckUngrouped = DB::table('stuck_state_alerts')
            ->where('reviewed', false)
            ->whereNull('root_cause')
            ->count();

        $reconciliationUngrouped = DB::table('reconciliation_alerts')
            ->where('resolved', false)
            ->whereNull('root_cause')
            ->count();

        return $stuckUngrouped + $reconciliationUngrouped;
    }

    /**
     * Mark root cause as resolved
     *
     * @param int $rootCauseId
     * @param int $resolvedBy Admin user ID
     * @param string|null $resolutionNotes
     * @return bool
     */
    public function markRootCauseResolved(int $rootCauseId, int $resolvedBy, ?string $resolutionNotes = null): bool
    {
        DB::table('alert_root_causes')
            ->where('id', $rootCauseId)
            ->update([
                'is_resolved' => true,
                'resolved_by' => $resolvedBy,
                'resolved_at' => now(),
                'resolution_notes' => $resolutionNotes,
                'updated_at' => now(),
            ]);

        Log::info("ROOT CAUSE RESOLVED", [
            'root_cause_id' => $rootCauseId,
            'resolved_by' => $resolvedBy,
        ]);

        return true;
    }
}
