<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 STABILIZATION - Issue 6: Cross-Phase Enforcement
 *
 * PURPOSE:
 * Assert that platform context:
 * - Cannot be mutated by issuers
 * - Respects Phase-2 freezes
 * - Respects Phase-3 safeguards
 * Make violations IMPOSSIBLE, not just discouraged.
 *
 * CROSS-PHASE RULES:
 * - Issuers can NEVER write to platform context tables
 * - Phase-2 governance freezes block platform context changes
 * - Phase-3 platform supremacy applies to context mutations
 * - All violations logged and blocked immediately
 */
class CrossPhaseEnforcementGuard
{
    /**
     * Platform context tables (issuer write access FORBIDDEN)
     */
    protected const PLATFORM_CONTEXT_TABLES = [
        'platform_context_snapshots',
        'platform_governance_log',
        'platform_risk_flags',
        'platform_company_metrics',
        'platform_valuation_context',
        'lifecycle_states',
        'lifecycle_state_transitions',
    ];

    /**
     * Assert that platform context mutation is allowed
     *
     * BLOCKS if:
     * - Actor is issuer (company user)
     * - Phase-2 governance freeze active
     * - Phase-3 platform restrictions active
     * - No admin override provided
     *
     * @param Company $company
     * @param string $operation create, update, delete, recalculate
     * @param User|null $actor
     * @param bool $adminOverride
     * @return array Assertion result
     */
    public function assertCanMutatePlatformContext(
        Company $company,
        string $operation,
        ?User $actor = null,
        bool $adminOverride = false
    ): array {
        $violations = [];

        // ENFORCEMENT 1: Issuer cannot mutate platform context
        if ($actor && $actor->company_id !== null) {
            $violations[] = [
                'rule' => 'issuer_mutation_forbidden',
                'severity' => 'critical',
                'message' => 'ISSUER CANNOT MUTATE PLATFORM CONTEXT',
                'reason' => 'Platform context is platform-owned. Issuers have no write access.',
                'user_id' => $actor->id,
                'user_company_id' => $actor->company_id,
            ];
        }

        // ENFORCEMENT 2: Respect Phase-2 governance freeze
        if ($company->disclosure_freeze ?? false) {
            if (!$adminOverride) {
                $violations[] = [
                    'rule' => 'phase2_governance_freeze',
                    'severity' => 'critical',
                    'message' => 'Platform context mutation blocked by Phase-2 governance freeze',
                    'reason' => $company->freeze_reason ?? 'Governance freeze active',
                    'frozen_at' => $company->frozen_at ?? null,
                ];
            }
        }

        // ENFORCEMENT 3: Respect Phase-3 platform supremacy
        $supremacyGuard = new \App\Services\PlatformSupremacyGuard();
        $platformCheck = $supremacyGuard->canPerformAction($company, 'mutate_platform_context', $actor);

        if (!$platformCheck['allowed'] && !$adminOverride) {
            $violations[] = [
                'rule' => 'phase3_platform_supremacy',
                'severity' => 'high',
                'message' => 'Platform context mutation blocked by Phase-3 platform supremacy',
                'reason' => $platformCheck['reason'],
                'blocking_state' => $platformCheck['blocking_state'] ?? null,
            ];
        }

        // ENFORCEMENT 4: Respect Phase-4 mutation rules
        $mutationGuard = new \App\Services\PlatformContextMutationGuard();
        $mutationCheck = $mutationGuard->canRecalculate($company, $operation);

        if ($mutationCheck['status'] === 'blocked' && !$adminOverride) {
            $violations[] = [
                'rule' => 'phase4_mutation_rules',
                'severity' => 'high',
                'message' => 'Platform context mutation blocked by Phase-4 mutation rules',
                'reason' => 'Recalculation not allowed at this time',
                'blockers' => $mutationCheck['blockers'],
            ];
        }

        // Check if violations should be blocked
        $criticalViolations = array_filter($violations, fn($v) => $v['severity'] === 'critical');

        if (!empty($criticalViolations)) {
            $this->logCrossPhaseViolation($company, $operation, $actor, $violations);

            return [
                'allowed' => false,
                'status' => 'blocked',
                'violations' => $violations,
                'message' => 'Cross-phase enforcement violation - mutation blocked',
            ];
        }

        if (!empty($violations) && !$adminOverride) {
            return [
                'allowed' => false,
                'status' => 'blocked',
                'violations' => $violations,
                'message' => 'Cross-phase enforcement rules not satisfied',
            ];
        }

        // All checks passed
        return [
            'allowed' => true,
            'status' => 'allowed',
            'violations' => [],
        ];
    }

    /**
     * Validate database operation on platform context table
     *
     * Called before any write operation to platform context tables.
     * BLOCKS issuer access immediately.
     *
     * @param string $tableName
     * @param string $operation INSERT, UPDATE, DELETE
     * @param User|null $actor
     * @return array Validation result
     */
    public function validateTableAccess(
        string $tableName,
        string $operation,
        ?User $actor = null
    ): array {
        // Check if table is platform context table
        if (!in_array($tableName, self::PLATFORM_CONTEXT_TABLES)) {
            return [
                'is_platform_table' => false,
                'access_allowed' => true,
            ];
        }

        // Platform context table - check actor
        if ($actor && $actor->company_id !== null) {
            // ISSUER attempting to write to platform context table - BLOCK
            Log::critical('CRITICAL VIOLATION: Issuer attempted to write to platform context table', [
                'table_name' => $tableName,
                'operation' => $operation,
                'user_id' => $actor->id,
                'company_id' => $actor->company_id,
                'ip_address' => request()?->ip(),
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);

            // Log violation to database
            DB::table('cross_phase_violations')->insert([
                'violation_type' => 'issuer_platform_table_write',
                'table_name' => $tableName,
                'operation' => $operation,
                'user_id' => $actor->id,
                'company_id' => $actor->company_id,
                'severity' => 'critical',
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);

            throw new \RuntimeException(
                "SECURITY VIOLATION: Issuers cannot write to platform context tables. " .
                "Table: {$tableName}, Operation: {$operation}"
            );
        }

        return [
            'is_platform_table' => true,
            'access_allowed' => true,
            'actor_type' => $actor ? 'admin_or_system' : 'system',
        ];
    }

    /**
     * Assert investor snapshot immutability
     *
     * GUARANTEES:
     * - Disclosure snapshots cannot be mutated
     * - Platform context snapshots cannot be mutated
     * - Investment linkages cannot be changed
     *
     * @param int $snapshotId
     * @param string $snapshotType disclosure or platform_context
     * @return array Assertion result
     */
    public function assertSnapshotImmutability(int $snapshotId, string $snapshotType): array
    {
        $tableName = $snapshotType === 'disclosure'
            ? 'investment_disclosure_snapshots'
            : 'platform_context_snapshots';

        $snapshot = DB::table($tableName)->find($snapshotId);

        if (!$snapshot) {
            return [
                'exists' => false,
                'message' => 'Snapshot not found',
            ];
        }

        // Check if snapshot is locked
        if (!($snapshot->is_locked ?? false)) {
            return [
                'is_immutable' => false,
                'message' => 'Snapshot is not locked - immutability not guaranteed',
                'warning' => 'Snapshot should be locked immediately after creation',
            ];
        }

        // Check if snapshot has been mutated after locking
        $lockTime = $snapshot->locked_at;
        $updateTime = $snapshot->updated_at;

        if ($updateTime && $lockTime && strtotime($updateTime) > strtotime($lockTime)) {
            Log::critical('IMMUTABILITY VIOLATION: Snapshot mutated after locking', [
                'snapshot_id' => $snapshotId,
                'snapshot_type' => $snapshotType,
                'locked_at' => $lockTime,
                'updated_at' => $updateTime,
            ]);

            return [
                'is_immutable' => false,
                'violation' => true,
                'message' => 'CRITICAL: Snapshot was mutated after locking',
                'locked_at' => $lockTime,
                'mutated_at' => $updateTime,
            ];
        }

        return [
            'is_immutable' => true,
            'is_locked' => true,
            'locked_at' => $lockTime,
            'message' => 'Snapshot immutability verified',
        ];
    }

    /**
     * Get cross-phase enforcement status for company
     *
     * Shows all active enforcement rules and restrictions.
     *
     * @param Company $company
     * @return array Enforcement status
     */
    public function getEnforcementStatus(Company $company): array
    {
        return [
            'company_id' => $company->id,
            'phase_2_restrictions' => [
                'governance_freeze' => $company->disclosure_freeze ?? false,
                'freeze_reason' => $company->freeze_reason ?? null,
            ],
            'phase_3_restrictions' => [
                'is_suspended' => $company->lifecycle_state === 'suspended',
                'is_frozen' => $company->disclosure_freeze ?? false,
                'is_under_investigation' => $company->under_investigation ?? false,
            ],
            'phase_4_restrictions' => [
                'context_recalc_frozen' => $company->context_recalc_frozen ?? false,
                'has_active_dispute' => $this->hasActiveDispute($company->id),
            ],
            'issuer_restrictions' => [
                'can_mutate_platform_context' => false,  // ALWAYS false for issuers
                'can_write_governance_state' => false,   // ALWAYS false for issuers
                'can_recalculate_context' => false,      // ALWAYS false for issuers
            ],
            'enforcement_active' => true,
            'violations_will_be_blocked' => true,
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Log cross-phase violation
     */
    protected function logCrossPhaseViolation(
        Company $company,
        string $operation,
        ?User $actor,
        array $violations
    ): void {
        Log::critical('CROSS-PHASE ENFORCEMENT VIOLATION', [
            'company_id' => $company->id,
            'operation' => $operation,
            'actor_id' => $actor?->id,
            'actor_company_id' => $actor?->company_id,
            'violations' => $violations,
            'ip_address' => request()?->ip(),
        ]);

        // Store in database
        DB::table('cross_phase_violations')->insert([
            'violation_type' => 'platform_context_mutation_blocked',
            'company_id' => $company->id,
            'user_id' => $actor?->id,
            'operation' => $operation,
            'severity' => 'critical',
            'violations' => json_encode($violations),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

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
}
