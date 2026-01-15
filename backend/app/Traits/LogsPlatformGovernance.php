<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 2 HARDENING - Issue 1: Platform Governance Logging
 *
 * PURPOSE:
 * Logs all platform governance decisions to immutable audit trail.
 * Enforces platform ownership of governance state.
 *
 * USAGE:
 * Add to CompanyLifecycleService: use LogsPlatformGovernance;
 * Call $this->logGovernanceDecision() after any governance change.
 */
trait LogsPlatformGovernance
{
    /**
     * Log platform governance decision
     *
     * @param int $companyId
     * @param string $actionType
     * @param array $decisionData
     * @return int Log ID
     */
    protected function logGovernanceDecision(
        int $companyId,
        string $actionType,
        array $decisionData
    ): int {
        // Verify this is platform action (not issuer)
        $user = auth()->user();
        if ($user && $user->company_id !== null) {
            Log::critical('GOVERNANCE VIOLATION: Company user attempted platform action', [
                'company_id' => $companyId,
                'user_id' => $user->id,
                'action_type' => $actionType,
            ]);

            throw new \RuntimeException(
                'Governance actions are platform-only. Companies cannot modify lifecycle state, buying enablement, or suspensions.'
            );
        }

        // Increment company's governance_state_version
        DB::table('companies')
            ->where('id', $companyId)
            ->increment('governance_state_version');

        // Log decision
        $logId = DB::table('platform_governance_log')->insertGetId([
            'company_id' => $companyId,
            'action_type' => $actionType,
            'from_state' => $decisionData['from_state'] ?? null,
            'to_state' => $decisionData['to_state'] ?? null,
            'buying_enabled_before' => $decisionData['buying_enabled_before'] ?? null,
            'buying_enabled_after' => $decisionData['buying_enabled_after'] ?? null,
            'decision_reason' => $decisionData['reason'] ?? null,
            'decision_criteria' => isset($decisionData['criteria']) ? json_encode($decisionData['criteria']) : null,
            'decided_by' => $user?->id,
            'is_automated' => $decisionData['is_automated'] ?? false,
            'decided_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'is_immutable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Platform governance decision logged', [
            'log_id' => $logId,
            'company_id' => $companyId,
            'action_type' => $actionType,
            'from_state' => $decisionData['from_state'] ?? null,
            'to_state' => $decisionData['to_state'] ?? null,
        ]);

        return $logId;
    }

    /**
     * Get governance history for company
     *
     * @param int $companyId
     * @param int $limit
     * @return array
     */
    protected function getGovernanceHistory(int $companyId, int $limit = 50): array
    {
        return DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->orderBy('decided_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get governance state at specific time (for snapshots)
     *
     * @param int $companyId
     * @param \Carbon\Carbon $timestamp
     * @return object|null
     */
    protected function getGovernanceStateAtTime(int $companyId, $timestamp): ?object
    {
        $log = DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('decided_at', '<=', $timestamp)
            ->orderBy('decided_at', 'desc')
            ->first();

        if (!$log) {
            return null;
        }

        return (object) [
            'lifecycle_state' => $log->to_state,
            'buying_enabled' => $log->buying_enabled_after,
            'governance_state_version' => DB::table('companies')
                ->where('id', $companyId)
                ->value('governance_state_version'),
        ];
    }
}
