<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 STABILIZATION - Issue 3: Platform Context Mutation Rules
 *
 * PURPOSE:
 * Define when platform context recalculation is allowed, blocked, or deferred.
 * Prevent reinterpretation during disputes or suspensions.
 *
 * MUTATION RULES:
 * - ALLOWED: Normal operations, scheduled refresh, admin-approved update
 * - BLOCKED: During dispute, during suspension, when frozen by admin
 * - DEFERRED: During high-traffic periods, when manual review pending
 *
 * IMMUTABILITY GUARANTEE:
 * - Past snapshots are NEVER mutated
 * - Recalculation creates NEW snapshot
 * - Existing investments remain linked to original snapshots
 */
class PlatformContextMutationGuard
{
    /**
     * Check if platform context recalculation is allowed
     *
     * @param Company $company
     * @param string $trigger What triggered recalculation request
     * @param int|null $requestedBy User ID if user-requested
     * @return array Result with allowed status and reason
     */
    public function canRecalculate(
        Company $company,
        string $trigger = 'manual',
        ?int $requestedBy = null
    ): array {
        $blockers = [];

        // CHECK 1: Company is suspended
        if ($company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false)) {
            $blockers[] = [
                'rule' => 'no_recalc_during_suspension',
                'severity' => 'critical',
                'message' => 'Platform context recalculation is BLOCKED during suspension',
                'reason' => 'Suspended companies cannot have context recalculated to prevent reinterpretation of circumstances',
            ];
        }

        // CHECK 2: Active dispute exists
        if ($this->hasActiveDispute($company->id)) {
            $blockers[] = [
                'rule' => 'no_recalc_during_dispute',
                'severity' => 'critical',
                'message' => 'Platform context recalculation is BLOCKED during active dispute',
                'reason' => 'Context must remain frozen while dispute is being resolved',
            ];
        }

        // CHECK 3: Admin freeze on context recalculation
        if ($company->context_recalc_frozen ?? false) {
            $blockers[] = [
                'rule' => 'admin_freeze_on_recalc',
                'severity' => 'high',
                'message' => 'Platform context recalculation is BLOCKED by admin freeze',
                'reason' => $company->context_freeze_reason ?? 'Manual investigation in progress',
                'frozen_at' => $company->context_frozen_at ?? null,
                'frozen_by' => $company->context_frozen_by ?? null,
            ];
        }

        // CHECK 4: Recent recalculation (rate limiting)
        $lastRecalc = $this->getLastRecalculation($company->id);
        if ($lastRecalc && $trigger !== 'admin_override') {
            $minutesSinceLastRecalc = now()->diffInMinutes($lastRecalc->snapshot_at);
            $minInterval = 15; // Minimum 15 minutes between recalculations

            if ($minutesSinceLastRecalc < $minInterval) {
                $blockers[] = [
                    'rule' => 'rate_limit_exceeded',
                    'severity' => 'medium',
                    'message' => 'Platform context recalculation rate limit exceeded',
                    'reason' => "Minimum {$minInterval} minutes required between recalculations",
                    'minutes_remaining' => $minInterval - $minutesSinceLastRecalc,
                    'can_retry_at' => now()->addMinutes($minInterval - $minutesSinceLastRecalc),
                ];
            }
        }

        // CHECK 5: Manual review pending (defer)
        if ($this->hasPendingManualReview($company->id)) {
            $blockers[] = [
                'rule' => 'manual_review_pending',
                'severity' => 'low',
                'message' => 'Platform context recalculation is DEFERRED pending manual review',
                'reason' => 'Admin review in progress - recalculation will proceed after review',
                'defer_until' => 'manual_review_complete',
            ];
        }

        // Determine overall status
        $criticalBlockers = array_filter($blockers, fn($b) => $b['severity'] === 'critical');
        $highBlockers = array_filter($blockers, fn($b) => $b['severity'] === 'high');

        if (!empty($criticalBlockers) || !empty($highBlockers)) {
            $status = 'blocked';
        } elseif (!empty($blockers)) {
            $status = 'deferred';
        } else {
            $status = 'allowed';
        }

        return [
            'status' => $status,
            'allowed' => $status === 'allowed',
            'blockers' => $blockers,
            'company_id' => $company->id,
            'trigger' => $trigger,
            'requested_by' => $requestedBy,
            'checked_at' => now(),
        ];
    }

    /**
     * Request platform context recalculation (with guard enforcement)
     *
     * @param Company $company
     * @param string $trigger
     * @param int|null $requestedBy
     * @return array Result with snapshot ID or error
     */
    public function requestRecalculation(
        Company $company,
        string $trigger = 'manual',
        ?int $requestedBy = null
    ): array {
        // Check if recalculation is allowed
        $guard = $this->canRecalculate($company, $trigger, $requestedBy);

        if ($guard['status'] === 'blocked') {
            Log::warning('PLATFORM CONTEXT RECALCULATION BLOCKED', [
                'company_id' => $company->id,
                'trigger' => $trigger,
                'requested_by' => $requestedBy,
                'blockers' => $guard['blockers'],
            ]);

            return [
                'success' => false,
                'status' => 'blocked',
                'message' => 'Platform context recalculation is not allowed',
                'blockers' => $guard['blockers'],
            ];
        }

        if ($guard['status'] === 'deferred') {
            Log::info('PLATFORM CONTEXT RECALCULATION DEFERRED', [
                'company_id' => $company->id,
                'trigger' => $trigger,
                'requested_by' => $requestedBy,
                'blockers' => $guard['blockers'],
            ]);

            return [
                'success' => false,
                'status' => 'deferred',
                'message' => 'Platform context recalculation is deferred',
                'blockers' => $guard['blockers'],
            ];
        }

        // Recalculation allowed - proceed
        try {
            $snapshotService = new PlatformContextSnapshotService();
            $snapshotId = $snapshotService->captureSnapshot($company, $trigger, $requestedBy);

            Log::info('PLATFORM CONTEXT RECALCULATION SUCCEEDED', [
                'company_id' => $company->id,
                'snapshot_id' => $snapshotId,
                'trigger' => $trigger,
                'requested_by' => $requestedBy,
            ]);

            return [
                'success' => true,
                'status' => 'completed',
                'snapshot_id' => $snapshotId,
                'message' => 'Platform context recalculated successfully',
            ];

        } catch (\Exception $e) {
            Log::error('PLATFORM CONTEXT RECALCULATION FAILED', [
                'company_id' => $company->id,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Platform context recalculation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Admin override: Force recalculation even if blocked
     *
     * Requires explicit admin approval with reason.
     * Logs as admin override for audit trail.
     *
     * @param Company $company
     * @param int $adminId
     * @param string $overrideReason
     * @return array Result
     */
    public function adminOverrideRecalculation(
        Company $company,
        int $adminId,
        string $overrideReason
    ): array {
        Log::warning('ADMIN OVERRIDE: Platform context recalculation forced', [
            'company_id' => $company->id,
            'admin_id' => $adminId,
            'override_reason' => $overrideReason,
        ]);

        try {
            $snapshotService = new PlatformContextSnapshotService();
            $snapshotId = $snapshotService->captureSnapshot($company, 'admin_override', $adminId, 'admin');

            // Log admin override action
            DB::table('platform_governance_log')->insert([
                'company_id' => $company->id,
                'action_type' => 'context_recalc_override',
                'from_state' => null,
                'to_state' => null,
                'decision_reason' => $overrideReason,
                'admin_user_id' => $adminId,
                'is_automated' => false,
                'is_immutable' => true,
                'metadata' => json_encode([
                    'snapshot_id' => $snapshotId,
                    'override_reason' => $overrideReason,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => 'admin_override_completed',
                'snapshot_id' => $snapshotId,
                'message' => 'Platform context recalculated with admin override',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Admin override recalculation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if company has active dispute
     */
    protected function hasActiveDispute(int $companyId): bool
    {
        return DB::table('disputes')
            ->where('company_id', $companyId)
            ->whereIn('status', ['open', 'under_investigation', 'pending_resolution'])
            ->exists();
    }

    /**
     * Get last recalculation snapshot
     */
    protected function getLastRecalculation(int $companyId): ?object
    {
        return DB::table('platform_context_snapshots')
            ->where('company_id', $companyId)
            ->orderBy('snapshot_at', 'desc')
            ->first();
    }

    /**
     * Check if manual review pending
     */
    protected function hasPendingManualReview(int $companyId): bool
    {
        return DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('action_type', 'manual_review_requested')
            ->whereNull('resolved_at')
            ->exists();
    }
}
