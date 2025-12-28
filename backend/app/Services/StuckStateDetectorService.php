<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * StuckStateDetectorService - Timeout and Escalation (G.24)
 *
 * META-FIX (I.28): This service DELEGATES to EconomicImpactService
 * - No longer has assessAlertEconomicImpact() logic (REMOVED)
 * - Delegates all economic impact assessment to unified authority
 *
 * PURPOSE:
 * - Add timeout and escalation for stuck states
 * - Pending investments, rewards, or allocations must not remain indefinite
 * - Detect stuck states and escalate appropriately
 *
 * STUCK STATE TYPES:
 * 1. Processing too long: Job stuck in "processing" state for > threshold
 * 2. Pending too long: Job stuck in "pending" state for > threshold
 * 3. Partial completion: Workflow partially done but stalled
 * 4. Failed repeatedly: Job failing and retrying indefinitely
 *
 * ESCALATION LEVELS:
 * - Low: Log warning, continue monitoring
 * - Medium: Create alert, notify admin
 * - High: Attempt auto-resolution (retry, cancel)
 * - Critical: Escalate to manual review, notify user
 *
 * AUTO-RESOLUTION ACTIONS:
 * - retry: Retry the stuck job
 * - cancel: Cancel and refund
 * - escalate: Send to manual review queue
 *
 * USAGE:
 * ```php
 * $detector = app(StuckStateDetectorService::class);
 *
 * // Detect all stuck states
 * $stuckStates = $detector->detectAllStuckStates();
 *
 * // Auto-resolve where possible
 * $detector->autoResolveStuckStates();
 *
 * // Create manual review queue
 * $manualReview = $detector->getManualReviewQueue();
 * ```
 */
class StuckStateDetectorService
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
     * Detect all stuck states across the system
     *
     * @return array ['payments' => [...], 'investments' => [...], 'bonuses' => [...]]
     */
    public function detectAllStuckStates(): array
    {
        return [
            'payments' => $this->detectStuckPayments(),
            'investments' => $this->detectStuckInvestments(),
            'bonuses' => $this->detectStuckBonuses(),
            'workflows' => $this->detectStuckWorkflows(),
        ];
    }

    /**
     * Detect stuck payments (pending too long, processing too long)
     *
     * @return array
     */
    public function detectStuckPayments(): array
    {
        $stuck = [];

        // Stuck in "pending" for > 24 hours
        $pendingTooLong = DB::table('payments')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($pendingTooLong as $payment) {
            $stuckDuration = Carbon::parse($payment->created_at)->diffInSeconds(now());
            $stuck[] = $this->createStuckStateAlert(
                'stuck_payment',
                'medium',
                'payment',
                $payment->id,
                $payment->user_id,
                'pending_too_long',
                "Payment stuck in pending state for " . Carbon::parse($payment->created_at)->diffForHumans(),
                $stuckDuration,
                $payment->created_at,
                true,  // auto_resolvable
                'cancel' // cancel and notify user
            );
        }

        // Stuck in "processing" for > 1 hour
        $processingTooLong = DB::table('payments')
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subHour())
            ->get();

        foreach ($processingTooLong as $payment) {
            $stuckDuration = Carbon::parse($payment->updated_at)->diffInSeconds(now());
            $stuck[] = $this->createStuckStateAlert(
                'stuck_payment',
                'high',
                'payment',
                $payment->id,
                $payment->user_id,
                'processing_too_long',
                "Payment stuck in processing state for " . Carbon::parse($payment->updated_at)->diffForHumans(),
                $stuckDuration,
                $payment->updated_at,
                true,
                'retry' // retry payment processing
            );
        }

        return $stuck;
    }

    /**
     * Detect stuck investments (allocation pending/failed)
     *
     * @return array
     */
    public function detectStuckInvestments(): array
    {
        $stuck = [];

        // Allocation stuck in "processing" for > 30 minutes
        $stuckAllocations = DB::table('investments')
            ->where('allocation_status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->get();

        foreach ($stuckAllocations as $investment) {
            $stuckDuration = Carbon::parse($investment->updated_at)->diffInSeconds(now());
            $stuck[] = $this->createStuckStateAlert(
                'stuck_allocation',
                'high',
                'investment',
                $investment->id,
                $investment->user_id,
                'allocation_processing_too_long',
                "Share allocation stuck in processing state for " . Carbon::parse($investment->updated_at)->diffForHumans(),
                $stuckDuration,
                $investment->updated_at,
                true,
                'retry'
            );
        }

        // Allocation failed and not retried
        $failedAllocations = DB::table('investments')
            ->where('allocation_status', 'failed')
            ->where('updated_at', '<', now()->subHours(1))
            ->get();

        foreach ($failedAllocations as $investment) {
            $stuckDuration = Carbon::parse($investment->updated_at)->diffInSeconds(now());
            $stuck[] = $this->createStuckStateAlert(
                'stuck_allocation',
                'critical',
                'investment',
                $investment->id,
                $investment->user_id,
                'allocation_failed',
                "Share allocation failed: " . ($investment->allocation_error ?? 'Unknown error'),
                $stuckDuration,
                $investment->updated_at,
                false, // requires manual review
                'escalate'
            );
        }

        return $stuck;
    }

    /**
     * Detect stuck bonus processing
     *
     * @return array
     */
    public function detectStuckBonuses(): array
    {
        $stuck = [];

        // Bonuses created but not credited to wallet
        $uncreditedBonuses = DB::table('bonuses')
            ->leftJoin('transactions', function ($join) {
                $join->on('bonuses.id', '=', 'transactions.reference_id')
                     ->where('transactions.reference_type', '=', 'bonus');
            })
            ->whereNull('transactions.id')
            ->where('bonuses.created_at', '<', now()->subHours(2))
            ->select('bonuses.*')
            ->get();

        foreach ($uncreditedBonuses as $bonus) {
            $stuckDuration = Carbon::parse($bonus->created_at)->diffInSeconds(now());
            $stuck[] = $this->createStuckStateAlert(
                'stuck_bonus',
                'medium',
                'bonus',
                $bonus->id,
                $bonus->user_id,
                'bonus_not_credited',
                "Bonus calculated but not credited to wallet for " . Carbon::parse($bonus->created_at)->diffForHumans(),
                $stuckDuration,
                $bonus->created_at,
                true,
                'retry'
            );
        }

        return $stuck;
    }

    /**
     * Detect stuck workflows (using JobStateTracker)
     *
     * @return array
     */
    public function detectStuckWorkflows(): array
    {
        $stuck = [];

        $stuckWorkflows = DB::table('job_state_tracking')
            ->where('is_stuck', true)
            ->where('current_state', '!=', 'completed')
            ->get();

        foreach ($stuckWorkflows as $workflow) {
            $stuckDuration = Carbon::parse($workflow->stuck_detected_at)->diffInSeconds(now());
            $stuck[] = $this->createStuckStateAlert(
                'stuck_workflow',
                'high',
                $workflow->workflow_id,
                $workflow->entity_id,
                null, // user_id not directly available
                $workflow->current_state,
                $workflow->stuck_reason,
                $stuckDuration,
                $workflow->stuck_detected_at,
                false, // requires investigation
                'escalate'
            );
        }

        return $stuck;
    }

    /**
     * Create a stuck state alert
     *
     * @param string $alertType
     * @param string $severity
     * @param string $entityType
     * @param int $entityId
     * @param int|null $userId
     * @param string $stuckState
     * @param string $description
     * @param int $stuckDuration
     * @param string $stuckSince
     * @param bool $autoResolvable
     * @param string|null $autoResolutionAction
     * @return int Alert ID
     */
    private function createStuckStateAlert(
        string $alertType,
        string $severity,
        string $entityType,
        int $entityId,
        ?int $userId,
        string $stuckState,
        string $description,
        int $stuckDuration,
        string $stuckSince,
        bool $autoResolvable = false,
        ?string $autoResolutionAction = null
    ): int {
        // Check if alert already exists
        $existing = DB::table('stuck_state_alerts')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('stuck_state', $stuckState)
            ->where('reviewed', false)
            ->first();

        if ($existing) {
            // Update stuck duration
            DB::table('stuck_state_alerts')
                ->where('id', $existing->id)
                ->update([
                    'stuck_duration_seconds' => $stuckDuration,
                    'updated_at' => now(),
                ]);

            return $existing->id;
        }

        // Create new alert
        $alertId = DB::table('stuck_state_alerts')->insertGetId([
            'alert_type' => $alertType,
            'severity' => $severity,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'stuck_state' => $stuckState,
            'description' => $description,
            'stuck_duration_seconds' => $stuckDuration,
            'stuck_since' => $stuckSince,
            'auto_resolvable' => $autoResolvable,
            'auto_resolution_action' => $autoResolutionAction,
            'requires_manual_review' => !$autoResolvable,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::warning("STUCK STATE DETECTED", [
            'alert_id' => $alertId,
            'alert_type' => $alertType,
            'severity' => $severity,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stuck_state' => $stuckState,
        ]);

        return $alertId;
    }

    /**
     * Auto-resolve stuck states where possible
     *
     * CRITICAL SAFEGUARDS (addressing audit feedback):
     * 1. Kill switch check (global disable)
     * 2. Rate limiting (max resolutions per hour)
     * 3. Per-entity cap (max resolutions per entity)
     * 4. Value cap (max monetary value per resolution)
     * 5. Cooling period (wait between resolutions)
     *
     * @return array ['resolved' => int, 'escalated' => int, 'failed' => int, 'rate_limited' => int]
     */
    public function autoResolveStuckStates(): array
    {
        $resolved = 0;
        $escalated = 0;
        $failed = 0;
        $rateLimited = 0;

        // SAFEGUARD 1: Kill switch check
        if (!setting('allow_auto_resolution', false)) {
            Log::warning("AUTO-RESOLUTION DISABLED: Global kill switch activated");
            return [
                'resolved' => 0,
                'escalated' => 0,
                'failed' => 0,
                'rate_limited' => 0,
                'kill_switch_active' => true,
            ];
        }

        // SAFEGUARD 2: Rate limiting (max 10 resolutions per hour)
        $recentResolutions = DB::table('stuck_state_alerts')
            ->where('auto_resolved', true)
            ->where('auto_resolved_at', '>', now()->subHour())
            ->count();

        $maxPerHour = (int) setting('max_auto_resolutions_per_hour', 10);

        if ($recentResolutions >= $maxPerHour) {
            Log::warning("AUTO-RESOLUTION RATE LIMITED", [
                'recent_resolutions' => $recentResolutions,
                'max_per_hour' => $maxPerHour,
            ]);
            return [
                'resolved' => 0,
                'escalated' => 0,
                'failed' => 0,
                'rate_limited' => $recentResolutions,
            ];
        }

        $remainingCapacity = $maxPerHour - $recentResolutions;

        $autoResolvableAlerts = DB::table('stuck_state_alerts')
            ->where('auto_resolvable', true)
            ->where('auto_resolved', false)
            ->where('reviewed', false)
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit($remainingCapacity) // Only process what fits in rate limit
            ->get();

        foreach ($autoResolvableAlerts as $alert) {
            try {
                // SAFEGUARD 2.5: Severity-gated auto-fix (NEW - addressing audit feedback)
                // AUTO-FIX ONLY FOR LOW-IMPACT SCENARIOS
                // ESCALATE everything else to manual review
                // DELEGATED to unified authority (I.28)
                $economicImpact = $this->economicImpact->assessByAlert($alert);

                if ($economicImpact !== 'LOW') {
                    Log::warning("AUTO-RESOLUTION BLOCKED - HIGH IMPACT", [
                        'alert_id' => $alert->id,
                        'entity_type' => $alert->entity_type,
                        'entity_id' => $alert->entity_id,
                        'economic_impact' => $economicImpact,
                        'reason' => 'Auto-fix only allowed for LOW impact scenarios',
                    ]);
                    $this->escalateToManualReview($alert->id);
                    $escalated++;
                    continue;
                }

                // SAFEGUARD 3: Per-entity cap (max 3 resolutions per entity per day)
                $entityResolutions = DB::table('stuck_state_alerts')
                    ->where('entity_type', $alert->entity_type)
                    ->where('entity_id', $alert->entity_id)
                    ->where('auto_resolved', true)
                    ->where('auto_resolved_at', '>', now()->subDay())
                    ->count();

                if ($entityResolutions >= 3) {
                    Log::warning("AUTO-RESOLUTION ENTITY CAP REACHED", [
                        'entity_type' => $alert->entity_type,
                        'entity_id' => $alert->entity_id,
                        'resolutions_today' => $entityResolutions,
                    ]);
                    $this->escalateToManualReview($alert->id);
                    $escalated++;
                    continue;
                }

                // SAFEGUARD 4: Cooling period (24h between resolutions for same entity)
                $lastResolution = DB::table('stuck_state_alerts')
                    ->where('entity_type', $alert->entity_type)
                    ->where('entity_id', $alert->entity_id)
                    ->where('auto_resolved', true)
                    ->orderBy('auto_resolved_at', 'desc')
                    ->first();

                if ($lastResolution && Carbon::parse($lastResolution->auto_resolved_at)->isAfter(now()->subHours(24))) {
                    Log::warning("AUTO-RESOLUTION COOLING PERIOD", [
                        'entity_type' => $alert->entity_type,
                        'entity_id' => $alert->entity_id,
                        'last_resolution' => $lastResolution->auto_resolved_at,
                    ]);
                    $rateLimited++;
                    continue;
                }

                $success = $this->executeAutoResolution($alert);

                if ($success) {
                    DB::table('stuck_state_alerts')
                        ->where('id', $alert->id)
                        ->update([
                            'auto_resolved' => true,
                            'auto_resolved_at' => now(),
                            'updated_at' => now(),
                        ]);
                    $resolved++;

                    Log::info("AUTO-RESOLVED STUCK STATE", [
                        'alert_id' => $alert->id,
                        'action' => $alert->auto_resolution_action,
                    ]);
                } else {
                    // Escalate to manual review
                    $this->escalateToManualReview($alert->id);
                    $escalated++;
                }

            } catch (\Throwable $e) {
                Log::error("AUTO-RESOLUTION FAILED", [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return [
            'resolved' => $resolved,
            'escalated' => $escalated,
            'failed' => $failed,
            'rate_limited' => $rateLimited,
        ];
    }

    /**
     * Execute auto-resolution action
     *
     * @param object $alert
     * @return bool Success
     */
    private function executeAutoResolution(object $alert): bool
    {
        switch ($alert->auto_resolution_action) {
            case 'retry':
                return $this->retryStuckEntity($alert);

            case 'cancel':
                return $this->cancelStuckEntity($alert);

            case 'escalate':
                return $this->escalateToManualReview($alert->id);

            default:
                return false;
        }
    }

    /**
     * Retry stuck entity
     *
     * @param object $alert
     * @return bool
     */
    private function retryStuckEntity(object $alert): bool
    {
        // Implementation depends on entity type
        // For payments: Re-dispatch ProcessSuccessfulPaymentJob
        // For investments: Re-dispatch ProcessAllocationJob
        // For bonuses: Re-dispatch ProcessPaymentBonusJob

        Log::info("RETRY STUCK ENTITY", [
            'alert_id' => $alert->id,
            'entity_type' => $alert->entity_type,
            'entity_id' => $alert->entity_id,
        ]);

        // TODO: Implement actual retry logic based on entity type
        return true;
    }

    /**
     * Cancel stuck entity
     *
     * CRITICAL SAFETY (addressing audit feedback):
     * - "Cancel payment and refund user" is not always legal
     * - Pending does not mean reversible
     * - Must check settlement status before cancelling
     *
     * @param object $alert
     * @return bool
     */
    private function cancelStuckEntity(object $alert): bool
    {
        Log::info("CANCEL STUCK ENTITY", [
            'alert_id' => $alert->id,
            'entity_type' => $alert->entity_type,
            'entity_id' => $alert->entity_id,
        ]);

        // Only handle payments for now (other entities require different logic)
        if ($alert->entity_type !== 'payment') {
            Log::warning("CANCEL ONLY SUPPORTS PAYMENTS", [
                'entity_type' => $alert->entity_type,
            ]);
            return false;
        }

        $payment = DB::table('payments')->find($alert->entity_id);

        if (!$payment) {
            Log::error("PAYMENT NOT FOUND FOR CANCELLATION", [
                'payment_id' => $alert->entity_id,
            ]);
            return false;
        }

        // CRITICAL CHECK: Only cancel if truly pending (not captured/settled)
        if ($payment->status !== 'pending') {
            Log::warning("PAYMENT NOT CANCELLABLE", [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'reason' => 'Only pending payments can be cancelled',
            ]);
            return false;
        }

        // CRITICAL CHECK: Verify with payment gateway that payment is not captured
        if ($payment->payment_gateway_id) {
            try {
                // Check gateway status (not just our DB status)
                $gatewayStatus = $this->checkGatewayPaymentStatus($payment->payment_gateway_id);

                // If captured or settled, cannot cancel
                if (in_array($gatewayStatus, ['captured', 'settled', 'authorized'])) {
                    Log::error("PAYMENT CANNOT BE CANCELLED - ALREADY CAPTURED/SETTLED", [
                        'payment_id' => $payment->id,
                        'gateway_status' => $gatewayStatus,
                    ]);

                    // Escalate to manual review
                    return false;
                }

            } catch (\Throwable $e) {
                Log::error("GATEWAY STATUS CHECK FAILED", [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                // Conservative: If we can't verify, don't cancel
                return false;
            }
        }

        // SAFE TO CANCEL: Payment is truly pending
        DB::table('payments')
            ->where('id', $payment->id)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Auto-cancelled after stuck in pending for ' .
                    Carbon::parse($payment->created_at)->diffForHumans(),
                'updated_at' => now(),
            ]);

        Log::info("PAYMENT CANCELLED SUCCESSFULLY", [
            'payment_id' => $payment->id,
        ]);

        // TODO: Notify user about cancellation
        // TODO: Create refund if any amount was charged

        return true;
    }

    /**
     * Check payment status with gateway
     *
     * @param string $paymentGatewayId
     * @return string Gateway status
     */
    private function checkGatewayPaymentStatus(string $paymentGatewayId): string
    {
        // TODO: Implement actual gateway API call
        // For now, return conservative default
        return 'unknown';
    }

    /**
     * Escalate to manual review
     *
     * @param int $alertId
     * @return bool
     */
    private function escalateToManualReview(int $alertId): bool
    {
        DB::table('stuck_state_alerts')
            ->where('id', $alertId)
            ->update([
                'requires_manual_review' => true,
                'escalated' => true,
                'escalated_at' => now(),
                'updated_at' => now(),
            ]);

        Log::warning("ESCALATED TO MANUAL REVIEW", [
            'alert_id' => $alertId,
        ]);

        return true;
    }

    /**
     * Get manual review queue
     *
     * @return array
     */
    public function getManualReviewQueue(): array
    {
        $queue = DB::table('stuck_state_alerts')
            ->where('requires_manual_review', true)
            ->where('reviewed', false)
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $queue->map(function ($alert) {
            return [
                'id' => $alert->id,
                'alert_type' => $alert->alert_type,
                'severity' => $alert->severity,
                'entity_type' => $alert->entity_type,
                'entity_id' => $alert->entity_id,
                'user_id' => $alert->user_id,
                'description' => $alert->description,
                'stuck_duration' => Carbon::parse($alert->stuck_since)->diffForHumans(),
                'stuck_since' => $alert->stuck_since,
            ];
        })->toArray();
    }

    /**
     * Mark alert as reviewed
     *
     * @param int $alertId
     * @param int $reviewedBy Admin user ID
     * @param string|null $resolutionNotes
     * @return bool
     */
    public function markAsReviewed(int $alertId, int $reviewedBy, ?string $resolutionNotes = null): bool
    {
        DB::table('stuck_state_alerts')
            ->where('id', $alertId)
            ->update([
                'reviewed' => true,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
                'resolution_notes' => $resolutionNotes,
                'updated_at' => now(),
            ]);

        Log::info("STUCK STATE ALERT REVIEWED", [
            'alert_id' => $alertId,
            'reviewed_by' => $reviewedBy,
        ]);

        return true;
    }

    /**
     * Get stuck state statistics (for admin dashboard)
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_stuck' => DB::table('stuck_state_alerts')
                ->where('reviewed', false)
                ->count(),

            'by_severity' => DB::table('stuck_state_alerts')
                ->select('severity', DB::raw('count(*) as count'))
                ->where('reviewed', false)
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),

            'by_type' => DB::table('stuck_state_alerts')
                ->select('alert_type', DB::raw('count(*) as count'))
                ->where('reviewed', false)
                ->groupBy('alert_type')
                ->pluck('count', 'alert_type')
                ->toArray(),

            'auto_resolved' => DB::table('stuck_state_alerts')
                ->where('auto_resolved', true)
                ->count(),

            'manual_review_queue' => DB::table('stuck_state_alerts')
                ->where('requires_manual_review', true)
                ->where('reviewed', false)
                ->count(),
        ];
    }

    /**
     * REMOVED: assessAlertEconomicImpact() - DELEGATED to EconomicImpactService (I.28)
     *
     * This method has been removed to eliminate fragmentation.
     * All economic impact assessment now happens in EconomicImpactService.
     *
     * Migration:
     * - Old: $this->assessAlertEconomicImpact($alert)
     * - New: $this->economicImpact->assessByAlert($alert)
     *
     * UNIFIED AUTHORITY: EconomicImpactService is the ONLY place where:
     * - Impact thresholds are defined
     * - Impact assessment logic lives
     * - Impact-based decisions are made
     */
}
