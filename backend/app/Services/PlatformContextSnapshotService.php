<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PHASE 4 STABILIZATION - Issue 1: Canonical Platform Context Snapshot
 *
 * PURPOSE:
 * Manage single, authoritative snapshots of investor-visible platform context.
 * Time-bound, immutable, and auditable.
 *
 * SNAPSHOT TRIGGERS:
 * - Company data update (governance, tier approval)
 * - Admin action (suspension, freeze, investigation)
 * - Scheduled refresh (daily at midnight)
 * - Before investment (ensure current snapshot exists)
 *
 * IMMUTABILITY GUARANTEE:
 * - Snapshots are LOCKED immediately after creation
 * - Recalculations create NEW snapshots with supersedes_snapshot_id
 * - Old snapshots remain intact for audit trail
 * - Each investment references specific snapshot ID
 */
class PlatformContextSnapshotService
{
    /**
     * Capture platform context snapshot for company
     *
     * CRITICAL: Creates NEW immutable snapshot, never mutates existing.
     *
     * @param Company $company
     * @param string $trigger What triggered snapshot
     * @param int|null $triggeredBy User ID if triggered by user
     * @param string $actorType Actor type: system, admin, automated_job
     * @return int Snapshot ID
     */
    public function captureSnapshot(
        Company $company,
        string $trigger = 'manual',
        ?int $triggeredBy = null,
        string $actorType = 'system'
    ): int {
        DB::beginTransaction();

        try {
            // V-AUDIT-FIX-2026: Capture timestamp ONCE to ensure no time gap
            // new.valid_from == old.valid_until (exact same instant)
            $transitionTimestamp = now();

            // Mark previous current snapshot as superseded
            $previousSnapshot = $this->getCurrentSnapshot($company->id);
            if ($previousSnapshot) {
                DB::table('platform_context_snapshots')
                    ->where('id', $previousSnapshot->id)
                    ->update([
                        'is_current' => false,
                        'valid_until' => $transitionTimestamp, // V-AUDIT-FIX-2026
                    ]);
            }

            // Gather platform context data
            $contextData = $this->gatherPlatformContext($company);

            // Calculate risk and compliance scores
            $riskAssessment = $this->calculateRiskScore($company, $contextData);
            $complianceScore = $this->calculateComplianceScore($company, $contextData);

            // Check for material changes
            $materialChanges = $this->detectMaterialChanges($company, $previousSnapshot);

            // Create snapshot
            $snapshotId = DB::table('platform_context_snapshots')->insertGetId([
                'company_id' => $company->id,
                'snapshot_at' => now(),
                'snapshot_trigger' => $trigger,
                'triggered_by_user_id' => $triggeredBy,
                'actor_type' => $actorType,

                // Governance state
                'lifecycle_state' => $company->lifecycle_state,
                'buying_enabled' => $company->buying_enabled ?? true,
                'governance_state_version' => $company->governance_state_version ?? 1,
                'is_suspended' => $company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false),
                'suspension_reason' => $company->suspension_reason,

                // Tier approvals
                'tier_1_approved' => $company->tier_1_approved_at !== null,
                'tier_1_approved_at' => $company->tier_1_approved_at,
                'tier_2_approved' => $company->tier_2_approved_at !== null,
                'tier_2_approved_at' => $company->tier_2_approved_at,
                'tier_3_approved' => $company->tier_3_approved_at !== null,
                'tier_3_approved_at' => $company->tier_3_approved_at,

                // Platform restrictions
                'is_frozen' => $company->disclosure_freeze ?? false,
                'freeze_reason' => $company->freeze_reason ?? null,
                'is_under_investigation' => $company->under_investigation ?? false,
                'investigation_reason' => $company->investigation_reason ?? null,

                // Risk and compliance
                'platform_risk_score' => $riskAssessment['score'],
                'risk_level' => $riskAssessment['level'],
                'compliance_score' => $complianceScore,
                'risk_flags' => json_encode($riskAssessment['flags']),

                // Material changes
                'has_material_changes' => $materialChanges['has_changes'],
                'material_changes_summary' => json_encode($materialChanges['summary']),
                'last_material_change_at' => $materialChanges['last_change_at'],

                // Admin notes (if any)
                'admin_notes' => $company->admin_notes ?? null,
                'admin_judgments' => json_encode($this->gatherAdminJudgments($company)),

                // Full context
                'full_context_data' => json_encode($contextData),

                // Immutability
                'is_locked' => true,
                'locked_at' => $transitionTimestamp,
                'supersedes_snapshot_id' => $previousSnapshot?->id,

                // Validity
                // V-AUDIT-FIX-2026: Use same timestamp as old snapshot's valid_until
                // This guarantees NO time gap between snapshots
                'valid_from' => $transitionTimestamp,
                'valid_until' => null,
                'is_current' => true,

                // Audit
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'audit_metadata' => json_encode([
                    'trigger' => $trigger,
                    'actor_type' => $actorType,
                    'governance_version' => $company->governance_state_version ?? 1,
                ]),

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('PLATFORM CONTEXT SNAPSHOT CAPTURED', [
                'snapshot_id' => $snapshotId,
                'company_id' => $company->id,
                'trigger' => $trigger,
                'actor_type' => $actorType,
                'supersedes' => $previousSnapshot?->id,
                'risk_score' => $riskAssessment['score'],
                'compliance_score' => $complianceScore,
            ]);

            return $snapshotId;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to capture platform context snapshot', [
                'company_id' => $company->id,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get current snapshot for company
     *
     * @param int $companyId
     * @return object|null Current snapshot
     */
    public function getCurrentSnapshot(int $companyId): ?object
    {
        return DB::table('platform_context_snapshots')
            ->where('company_id', $companyId)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Get snapshot by ID
     *
     * @param int $snapshotId
     * @return object|null Snapshot
     */
    public function getSnapshot(int $snapshotId): ?object
    {
        return DB::table('platform_context_snapshots')
            ->where('id', $snapshotId)
            ->first();
    }

    /**
     * Get snapshot at specific time
     *
     * @param int $companyId
     * @param Carbon $timestamp
     * @return object|null Snapshot valid at that time
     */
    public function getSnapshotAtTime(int $companyId, Carbon $timestamp): ?object
    {
        return DB::table('platform_context_snapshots')
            ->where('company_id', $companyId)
            ->where('valid_from', '<=', $timestamp)
            ->where(function ($query) use ($timestamp) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', $timestamp);
            })
            ->orderBy('valid_from', 'desc')
            ->first();
    }

    /**
     * Ensure current snapshot exists (create if needed)
     *
     * Called before investment to ensure snapshot is fresh.
     *
     * @param Company $company
     * @param int $maxAgeMinutes Maximum age before refresh (default 60)
     * @return int Snapshot ID
     */
    public function ensureCurrentSnapshot(Company $company, int $maxAgeMinutes = 60): int
    {
        $currentSnapshot = $this->getCurrentSnapshot($company->id);

        if (!$currentSnapshot) {
            // No snapshot exists, create one
            return $this->captureSnapshot($company, 'initial_snapshot', null, 'system');
        }

        // Check if snapshot is stale
        $snapshotAge = Carbon::parse($currentSnapshot->snapshot_at)->diffInMinutes(now());

        if ($snapshotAge > $maxAgeMinutes) {
            // Snapshot is stale, create fresh one
            return $this->captureSnapshot($company, 'scheduled_refresh', null, 'automated_job');
        }

        return $currentSnapshot->id;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Gather platform context from company
     */
    protected function gatherPlatformContext(Company $company): array
    {
        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'lifecycle_state' => $company->lifecycle_state,
            'buying_enabled' => $company->buying_enabled ?? true,
            'governance_state_version' => $company->governance_state_version ?? 1,

            'tier_status' => [
                'tier_1_approved' => $company->tier_1_approved_at !== null,
                'tier_2_approved' => $company->tier_2_approved_at !== null,
                'tier_3_approved' => $company->tier_3_approved_at !== null,
            ],

            'restrictions' => [
                'is_suspended' => $company->lifecycle_state === 'suspended',
                'is_frozen' => $company->disclosure_freeze ?? false,
                'is_under_investigation' => $company->under_investigation ?? false,
                'buying_paused' => !($company->buying_enabled ?? true),
            ],

            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate platform risk score
     */
    protected function calculateRiskScore(Company $company, array $context): array
    {
        $score = 0;
        $flags = [];

        // Risk factors
        if ($context['restrictions']['is_suspended']) {
            $score += 50;
            $flags[] = 'suspended';
        }

        if ($context['restrictions']['is_frozen']) {
            $score += 30;
            $flags[] = 'frozen';
        }

        if ($context['restrictions']['is_under_investigation']) {
            $score += 20;
            $flags[] = 'under_investigation';
        }

        if (!$context['tier_status']['tier_2_approved']) {
            $score += 15;
            $flags[] = 'tier_2_not_approved';
        }

        // Risk level
        $level = match(true) {
            $score >= 50 => 'critical',
            $score >= 30 => 'high',
            $score >= 15 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'flags' => $flags,
        ];
    }

    /**
     * Calculate compliance score
     */
    protected function calculateComplianceScore(Company $company, array $context): float
    {
        $score = 100.0;

        // Deduct for compliance issues
        if (!$context['tier_status']['tier_1_approved']) {
            $score -= 40;
        }

        if (!$context['tier_status']['tier_2_approved']) {
            $score -= 30;
        }

        if ($context['restrictions']['is_suspended']) {
            $score -= 30;
        }

        return max(0, $score);
    }

    /**
     * Detect material changes since last snapshot
     */
    protected function detectMaterialChanges(Company $company, ?object $previousSnapshot): array
    {
        if (!$previousSnapshot) {
            return [
                'has_changes' => false,
                'summary' => [],
                'last_change_at' => null,
            ];
        }

        $changes = [];

        // Check governance state changes
        if ($company->lifecycle_state !== $previousSnapshot->lifecycle_state) {
            $changes[] = [
                'field' => 'lifecycle_state',
                'old' => $previousSnapshot->lifecycle_state,
                'new' => $company->lifecycle_state,
                'is_material' => true,
            ];
        }

        // Check buying enabled changes
        $currentBuying = $company->buying_enabled ?? true;
        if ($currentBuying !== $previousSnapshot->buying_enabled) {
            $changes[] = [
                'field' => 'buying_enabled',
                'old' => $previousSnapshot->buying_enabled,
                'new' => $currentBuying,
                'is_material' => true,
            ];
        }

        // Check tier approvals
        $tier2Current = $company->tier_2_approved_at !== null;
        if ($tier2Current !== $previousSnapshot->tier_2_approved) {
            $changes[] = [
                'field' => 'tier_2_approved',
                'old' => $previousSnapshot->tier_2_approved,
                'new' => $tier2Current,
                'is_material' => true,
            ];
        }

        $hasChanges = count($changes) > 0;
        $lastChangeAt = $hasChanges ? now() : null;

        return [
            'has_changes' => $hasChanges,
            'summary' => $changes,
            'last_change_at' => $lastChangeAt,
        ];
    }

    /**
     * Gather admin judgments (Issue 5: Admin vs Platform Attribution)
     */
    protected function gatherAdminJudgments(Company $company): array
    {
        // Get recent admin actions from platform_governance_log
        $adminActions = DB::table('platform_governance_log')
            ->where('company_id', $company->id)
            ->where('is_automated', false) // Only admin actions, not automated
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $adminActions->map(function ($action) {
            return [
                'action_type' => $action->action_type,
                'from_state' => $action->from_state,
                'to_state' => $action->to_state,
                'decision_reason' => $action->decision_reason,
                'admin_user_id' => $action->admin_user_id,
                'created_at' => $action->created_at,
            ];
        })->toArray();
    }

    /**
     * V-AUDIT-FIX-2026: Compare two snapshots and return diff
     *
     * Used by admin for audit purposes to understand what changed between snapshots.
     *
     * @param int $snapshotId1 First (older) snapshot ID
     * @param int $snapshotId2 Second (newer) snapshot ID
     * @return array Diff result with changes
     */
    public function compareSnapshots(int $snapshotId1, int $snapshotId2): array
    {
        $snapshot1 = $this->getSnapshot($snapshotId1);
        $snapshot2 = $this->getSnapshot($snapshotId2);

        if (!$snapshot1 || !$snapshot2) {
            return [
                'error' => 'One or both snapshots not found',
                'snapshot1_exists' => $snapshot1 !== null,
                'snapshot2_exists' => $snapshot2 !== null,
            ];
        }

        // Verify same company
        if ($snapshot1->company_id !== $snapshot2->company_id) {
            return [
                'error' => 'Cannot compare snapshots from different companies',
                'company_id_1' => $snapshot1->company_id,
                'company_id_2' => $snapshot2->company_id,
            ];
        }

        $changes = [];

        // Compare governance fields
        $governanceFields = [
            'lifecycle_state',
            'buying_enabled',
            'is_suspended',
            'is_frozen',
            'is_under_investigation',
        ];

        foreach ($governanceFields as $field) {
            $val1 = $snapshot1->$field ?? null;
            $val2 = $snapshot2->$field ?? null;

            if ($val1 !== $val2) {
                $changes[] = [
                    'field' => $field,
                    'category' => 'governance',
                    'old_value' => $val1,
                    'new_value' => $val2,
                    'is_material' => in_array($field, ['lifecycle_state', 'buying_enabled', 'is_suspended']),
                ];
            }
        }

        // Compare tier approvals
        $tierFields = [
            'tier_1_approved',
            'tier_2_approved',
            'tier_3_approved',
        ];

        foreach ($tierFields as $field) {
            $val1 = $snapshot1->$field ?? null;
            $val2 = $snapshot2->$field ?? null;

            if ($val1 !== $val2) {
                $changes[] = [
                    'field' => $field,
                    'category' => 'tier_approval',
                    'old_value' => $val1,
                    'new_value' => $val2,
                    'is_material' => true,
                ];
            }
        }

        // Compare risk assessment
        $riskFields = [
            'platform_risk_score',
            'risk_level',
            'compliance_score',
        ];

        foreach ($riskFields as $field) {
            $val1 = $snapshot1->$field ?? null;
            $val2 = $snapshot2->$field ?? null;

            if ($val1 !== $val2) {
                $changes[] = [
                    'field' => $field,
                    'category' => 'risk_assessment',
                    'old_value' => $val1,
                    'new_value' => $val2,
                    'is_material' => $field === 'risk_level',
                ];
            }
        }

        // Check for material changes flag
        $materialChangeDetected = $snapshot2->has_material_changes ?? false;

        return [
            'snapshot_1' => [
                'id' => $snapshotId1,
                'snapshot_at' => $snapshot1->snapshot_at,
                'valid_from' => $snapshot1->valid_from,
                'valid_until' => $snapshot1->valid_until,
            ],
            'snapshot_2' => [
                'id' => $snapshotId2,
                'snapshot_at' => $snapshot2->snapshot_at,
                'valid_from' => $snapshot2->valid_from,
                'valid_until' => $snapshot2->valid_until,
            ],
            'company_id' => $snapshot1->company_id,
            'total_changes' => count($changes),
            'material_changes' => count(array_filter($changes, fn($c) => $c['is_material'])),
            'material_change_flag' => $materialChangeDetected,
            'changes' => $changes,
            'compared_at' => now()->toIso8601String(),
        ];
    }

    /**
     * V-AUDIT-FIX-2026: Get snapshot history for a company
     *
     * Returns chronological list of all snapshots for audit trail.
     *
     * @param int $companyId
     * @param int $limit
     * @return array
     */
    public function getSnapshotHistory(int $companyId, int $limit = 50): array
    {
        $snapshots = DB::table('platform_context_snapshots')
            ->where('company_id', $companyId)
            ->orderBy('valid_from', 'desc')
            ->limit($limit)
            ->get();

        return $snapshots->map(function ($snapshot) {
            return [
                'id' => $snapshot->id,
                'snapshot_at' => $snapshot->snapshot_at,
                'snapshot_trigger' => $snapshot->snapshot_trigger,
                'valid_from' => $snapshot->valid_from,
                'valid_until' => $snapshot->valid_until,
                'is_current' => $snapshot->is_current,
                'lifecycle_state' => $snapshot->lifecycle_state,
                'buying_enabled' => $snapshot->buying_enabled,
                'risk_level' => $snapshot->risk_level,
                'has_material_changes' => $snapshot->has_material_changes,
                'supersedes_snapshot_id' => $snapshot->supersedes_snapshot_id,
            ];
        })->toArray();
    }
}
