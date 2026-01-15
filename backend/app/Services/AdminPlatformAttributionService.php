<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 STABILIZATION - Issue 5: Admin vs Platform Attribution
 *
 * PURPOSE:
 * Explicitly distinguish automated platform analysis from admin overrides/judgments.
 * Make attribution auditable and visible internally.
 *
 * ACTOR TYPES:
 * - automated_platform: System-generated analysis (risk scores, compliance checks, auto-flags)
 * - admin_judgment: Explicit admin decision (approve, reject, override)
 * - admin_override: Admin overriding platform decision (with reason required)
 * - system_enforcement: Automated enforcement of rules (suspension, pause buying)
 *
 * ATTRIBUTION RULES:
 * - All actions must declare actor_type
 * - Admin actions require admin_user_id
 * - Automated actions must have is_automated = true
 * - Admin overrides must have override_reason
 * - Attribution is immutable once recorded
 */
class AdminPlatformAttributionService
{
    /**
     * Record platform action with attribution
     *
     * @param int $companyId
     * @param string $actionType
     * @param string $actorType automated_platform, admin_judgment, admin_override, system_enforcement
     * @param array $actionData
     * @return int Action ID
     */
    public function recordAction(
        int $companyId,
        string $actionType,
        string $actorType,
        array $actionData
    ): int {
        // Validate actor type
        $validActorTypes = ['automated_platform', 'admin_judgment', 'admin_override', 'system_enforcement'];
        if (!in_array($actorType, $validActorTypes)) {
            throw new \InvalidArgumentException("Invalid actor type: {$actorType}");
        }

        // Validate admin actions have admin_user_id
        if (in_array($actorType, ['admin_judgment', 'admin_override'])) {
            if (!isset($actionData['admin_user_id'])) {
                throw new \RuntimeException('Admin actions require admin_user_id');
            }
        }

        // Validate admin overrides have reason
        if ($actorType === 'admin_override') {
            if (empty($actionData['override_reason'])) {
                throw new \RuntimeException('Admin overrides require override_reason');
            }
        }

        // Record action
        $actionId = DB::table('platform_governance_log')->insertGetId([
            'company_id' => $companyId,
            'action_type' => $actionType,
            'from_state' => $actionData['from_state'] ?? null,
            'to_state' => $actionData['to_state'] ?? null,
            'decision_reason' => $actionData['decision_reason'] ?? null,
            'admin_user_id' => $actionData['admin_user_id'] ?? null,
            'is_automated' => in_array($actorType, ['automated_platform', 'system_enforcement']),
            'is_immutable' => true,
            'metadata' => json_encode([
                'actor_type' => $actorType,
                'override_reason' => $actionData['override_reason'] ?? null,
                'platform_analysis' => $actionData['platform_analysis'] ?? null,
                'admin_notes' => $actionData['admin_notes'] ?? null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('PLATFORM ACTION RECORDED WITH ATTRIBUTION', [
            'action_id' => $actionId,
            'company_id' => $companyId,
            'action_type' => $actionType,
            'actor_type' => $actorType,
            'is_automated' => in_array($actorType, ['automated_platform', 'system_enforcement']),
        ]);

        return $actionId;
    }

    /**
     * Record automated platform analysis
     *
     * @param int $companyId
     * @param string $analysisType risk_assessment, compliance_check, etc.
     * @param array $analysisResult
     * @return int Action ID
     */
    public function recordPlatformAnalysis(
        int $companyId,
        string $analysisType,
        array $analysisResult
    ): int {
        return $this->recordAction($companyId, $analysisType, 'automated_platform', [
            'platform_analysis' => $analysisResult,
            'decision_reason' => 'Automated platform analysis',
        ]);
    }

    /**
     * Record admin judgment
     *
     * @param int $companyId
     * @param string $judgmentType
     * @param int $adminId
     * @param string $reason
     * @param array $additionalData
     * @return int Action ID
     */
    public function recordAdminJudgment(
        int $companyId,
        string $judgmentType,
        int $adminId,
        string $reason,
        array $additionalData = []
    ): int {
        return $this->recordAction($companyId, $judgmentType, 'admin_judgment', array_merge([
            'admin_user_id' => $adminId,
            'decision_reason' => $reason,
        ], $additionalData));
    }

    /**
     * Record admin override of platform decision
     *
     * @param int $companyId
     * @param string $overrideType
     * @param int $adminId
     * @param string $overrideReason
     * @param array $platformDecision Original platform decision being overridden
     * @return int Action ID
     */
    public function recordAdminOverride(
        int $companyId,
        string $overrideType,
        int $adminId,
        string $overrideReason,
        array $platformDecision
    ): int {
        Log::warning('ADMIN OVERRIDE: Platform decision overridden', [
            'company_id' => $companyId,
            'override_type' => $overrideType,
            'admin_id' => $adminId,
            'override_reason' => $overrideReason,
            'platform_decision' => $platformDecision,
        ]);

        return $this->recordAction($companyId, $overrideType, 'admin_override', [
            'admin_user_id' => $adminId,
            'override_reason' => $overrideReason,
            'decision_reason' => "Admin override: {$overrideReason}",
            'platform_analysis' => $platformDecision,
        ]);
    }

    /**
     * Get actions by actor type
     *
     * @param int $companyId
     * @param string $actorType
     * @param int $limit
     * @return array Actions
     */
    public function getActionsByActorType(int $companyId, string $actorType, int $limit = 50): array
    {
        $query = DB::table('platform_governance_log')
            ->where('company_id', $companyId);

        if ($actorType === 'automated_platform' || $actorType === 'system_enforcement') {
            $query->where('is_automated', true);
        } else {
            $query->where('is_automated', false);
        }

        $actions = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $actions->map(function ($action) {
            $metadata = json_decode($action->metadata, true);
            return [
                'id' => $action->id,
                'action_type' => $action->action_type,
                'actor_type' => $metadata['actor_type'] ?? 'unknown',
                'is_automated' => $action->is_automated,
                'admin_user_id' => $action->admin_user_id,
                'decision_reason' => $action->decision_reason,
                'from_state' => $action->from_state,
                'to_state' => $action->to_state,
                'created_at' => $action->created_at,
                'metadata' => $metadata,
            ];
        })->toArray();
    }

    /**
     * Get admin overrides (audit trail)
     *
     * @param int|null $companyId Filter by company (null = all)
     * @param int $limit
     * @return array Overrides
     */
    public function getAdminOverrides(?int $companyId = null, int $limit = 100): array
    {
        $query = DB::table('platform_governance_log')
            ->where('is_automated', false)
            ->whereNotNull('admin_user_id');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $overrides = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $overrides->map(function ($override) {
            $metadata = json_decode($override->metadata, true);
            $actorType = $metadata['actor_type'] ?? 'unknown';

            return [
                'id' => $override->id,
                'company_id' => $override->company_id,
                'action_type' => $override->action_type,
                'actor_type' => $actorType,
                'is_override' => $actorType === 'admin_override',
                'admin_user_id' => $override->admin_user_id,
                'override_reason' => $metadata['override_reason'] ?? null,
                'decision_reason' => $override->decision_reason,
                'platform_decision_overridden' => $metadata['platform_analysis'] ?? null,
                'created_at' => $override->created_at,
            ];
        })->toArray();
    }

    /**
     * Compare platform analysis vs admin judgment
     *
     * For audit and dispute resolution.
     * Shows when admin judgment differed from platform recommendation.
     *
     * @param int $companyId
     * @param string $decisionType
     * @return array Comparison
     */
    public function compareAnalysisVsJudgment(int $companyId, string $decisionType): array
    {
        // Get platform analysis for this decision
        $platformAnalysis = DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('action_type', "{$decisionType}_analysis")
            ->where('is_automated', true)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get admin judgment
        $adminJudgment = DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('action_type', "{$decisionType}_judgment")
            ->where('is_automated', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$platformAnalysis || !$adminJudgment) {
            return [
                'available' => false,
                'message' => 'Platform analysis or admin judgment not found',
            ];
        }

        $platformMetadata = json_decode($platformAnalysis->metadata, true);
        $adminMetadata = json_decode($adminJudgment->metadata, true);

        $platformRecommendation = $platformMetadata['platform_analysis']['recommendation'] ?? null;
        $adminDecision = $adminJudgment->to_state;

        $agreed = $platformRecommendation === $adminDecision;

        return [
            'available' => true,
            'company_id' => $companyId,
            'decision_type' => $decisionType,
            'platform_analysis' => [
                'timestamp' => $platformAnalysis->created_at,
                'recommendation' => $platformRecommendation,
                'reasoning' => $platformMetadata['platform_analysis']['reasoning'] ?? null,
            ],
            'admin_judgment' => [
                'timestamp' => $adminJudgment->created_at,
                'decision' => $adminDecision,
                'admin_user_id' => $adminJudgment->admin_user_id,
                'reason' => $adminJudgment->decision_reason,
            ],
            'agreed' => $agreed,
            'disagreement_note' => !$agreed
                ? "Admin decision ({$adminDecision}) differed from platform recommendation ({$platformRecommendation})"
                : null,
        ];
    }

    /**
     * Get attribution summary for company
     *
     * Shows breakdown of automated vs admin actions.
     *
     * @param int $companyId
     * @return array Attribution summary
     */
    public function getAttributionSummary(int $companyId): array
    {
        $stats = DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->selectRaw('
                COUNT(*) as total_actions,
                SUM(CASE WHEN is_automated = TRUE THEN 1 ELSE 0 END) as automated_actions,
                SUM(CASE WHEN is_automated = FALSE THEN 1 ELSE 0 END) as admin_actions,
                COUNT(DISTINCT admin_user_id) as unique_admins
            ')
            ->first();

        $recentOverrides = DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('is_automated', false)
            ->whereNotNull('admin_user_id')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $overrideCount = $recentOverrides->filter(function ($action) {
            $metadata = json_decode($action->metadata, true);
            return ($metadata['actor_type'] ?? '') === 'admin_override';
        })->count();

        return [
            'company_id' => $companyId,
            'total_actions' => $stats->total_actions,
            'automated_actions' => $stats->automated_actions,
            'admin_actions' => $stats->admin_actions,
            'admin_override_count' => $overrideCount,
            'unique_admins' => $stats->unique_admins,
            'automation_percentage' => $stats->total_actions > 0
                ? round(($stats->automated_actions / $stats->total_actions) * 100, 1)
                : 0,
            'recent_overrides' => $recentOverrides->map(function ($action) {
                $metadata = json_decode($action->metadata, true);
                return [
                    'action_type' => $action->action_type,
                    'actor_type' => $metadata['actor_type'] ?? 'unknown',
                    'admin_user_id' => $action->admin_user_id,
                    'override_reason' => $metadata['override_reason'] ?? null,
                    'created_at' => $action->created_at,
                ];
            })->toArray(),
        ];
    }
}
