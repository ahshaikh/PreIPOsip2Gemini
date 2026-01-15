<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 STABILIZATION - Issue 4: Material Change → Buy Impact Rule
 *
 * PURPOSE:
 * Make is_material_change operational with actual platform actions.
 * Pause buying if required, force investor re-acknowledgement.
 *
 * MATERIAL CHANGE RULES:
 * - Material changes PAUSE buying automatically
 * - Existing investors see warning but not affected
 * - NEW investors must acknowledge material changes before investing
 * - Admin can override pause if changes are explanatory (not corrective)
 *
 * WHAT IS MATERIAL:
 * - Lifecycle state change (e.g., live → suspended)
 * - Buying enablement change
 * - Tier approval changes (especially Tier 2)
 * - Major disclosure corrections (from Phase 3 error reporting)
 * - Risk level changes (low → high)
 */
class MaterialChangeBuyImpactService
{
    /**
     * Detect material changes and apply buy impact
     *
     * Called when platform context snapshot is created.
     * Compares with previous snapshot to detect material changes.
     *
     * @param int $newSnapshotId New snapshot ID
     * @param int|null $previousSnapshotId Previous snapshot ID
     * @return array Impact result
     */
    public function detectAndApplyImpact(int $newSnapshotId, ?int $previousSnapshotId = null): array
    {
        $newSnapshot = DB::table('platform_context_snapshots')->find($newSnapshotId);
        if (!$newSnapshot) {
            return ['error' => 'Snapshot not found'];
        }

        if (!$previousSnapshotId) {
            // No previous snapshot, no material changes
            return [
                'has_material_changes' => false,
                'message' => 'Initial snapshot - no comparison available',
            ];
        }

        $previousSnapshot = DB::table('platform_context_snapshots')->find($previousSnapshotId);
        if (!$previousSnapshot) {
            return ['error' => 'Previous snapshot not found'];
        }

        // Detect material changes
        $materialChanges = $this->detectMaterialChanges($newSnapshot, $previousSnapshot);

        if (empty($materialChanges)) {
            return [
                'has_material_changes' => false,
                'message' => 'No material changes detected',
            ];
        }

        // Apply buy impact
        $impact = $this->applyBuyImpact($newSnapshot->company_id, $materialChanges);

        Log::warning('MATERIAL CHANGE DETECTED: Buy impact applied', [
            'company_id' => $newSnapshot->company_id,
            'new_snapshot_id' => $newSnapshotId,
            'previous_snapshot_id' => $previousSnapshotId,
            'material_changes' => $materialChanges,
            'impact' => $impact,
        ]);

        return [
            'has_material_changes' => true,
            'material_changes' => $materialChanges,
            'impact' => $impact,
        ];
    }

    /**
     * Check if investor can proceed with investment
     *
     * Called before investment creation.
     * Checks for material changes and ensures acknowledgement.
     *
     * @param int $companyId
     * @param int $userId
     * @param array $acknowledgements User's acknowledgements
     * @return array Can proceed status
     */
    public function canProceedWithInvestment(
        int $companyId,
        int $userId,
        array $acknowledgements = []
    ): array {
        // Get current snapshot
        $snapshotService = new PlatformContextSnapshotService();
        $currentSnapshot = $snapshotService->getCurrentSnapshot($companyId);

        if (!$currentSnapshot) {
            return [
                'can_proceed' => false,
                'reason' => 'Platform context snapshot not available',
            ];
        }

        // Check if buying is enabled
        if (!$currentSnapshot->buying_enabled) {
            return [
                'can_proceed' => false,
                'reason' => 'Buying is currently disabled for this company',
                'buying_paused_reason' => 'Platform restriction',
            ];
        }

        // Check for material changes
        if ($currentSnapshot->has_material_changes) {
            // Material changes exist - check acknowledgement
            $materialChangesSummary = json_decode($currentSnapshot->material_changes_summary, true);

            $requiresAcknowledgement = $this->requiresInvestorAcknowledgement($materialChangesSummary);

            if ($requiresAcknowledgement) {
                // Check if user has acknowledged
                $hasAcknowledged = isset($acknowledgements['material_changes'])
                    && $acknowledgements['material_changes'] === true
                    && isset($acknowledgements['snapshot_id'])
                    && $acknowledgements['snapshot_id'] == $currentSnapshot->id;

                if (!$hasAcknowledged) {
                    return [
                        'can_proceed' => false,
                        'reason' => 'Material changes require acknowledgement',
                        'requires_acknowledgement' => true,
                        'material_changes' => $materialChangesSummary,
                        'snapshot_id' => $currentSnapshot->id,
                        'acknowledgement_text' => $this->getAcknowledgementText($materialChangesSummary),
                    ];
                }
            }
        }

        // All checks passed
        return [
            'can_proceed' => true,
            'snapshot_id' => $currentSnapshot->id,
        ];
    }

    /**
     * Record investor acknowledgement of material changes
     *
     * @param int $companyId
     * @param int $userId
     * @param int $snapshotId
     * @return int Acknowledgement ID
     */
    public function recordInvestorAcknowledgement(
        int $companyId,
        int $userId,
        int $snapshotId
    ): int {
        return DB::table('investor_material_change_acknowledgements')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $userId,
            'platform_context_snapshot_id' => $snapshotId,
            'acknowledged_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Detect material changes between snapshots
     */
    protected function detectMaterialChanges(object $newSnapshot, object $previousSnapshot): array
    {
        $changes = [];

        // Lifecycle state change
        if ($newSnapshot->lifecycle_state !== $previousSnapshot->lifecycle_state) {
            $changes[] = [
                'type' => 'lifecycle_state',
                'field' => 'lifecycle_state',
                'old_value' => $previousSnapshot->lifecycle_state,
                'new_value' => $newSnapshot->lifecycle_state,
                'is_material' => true,
                'severity' => 'high',
                'description' => "Company lifecycle changed from {$previousSnapshot->lifecycle_state} to {$newSnapshot->lifecycle_state}",
            ];
        }

        // Buying enabled change
        if ($newSnapshot->buying_enabled !== $previousSnapshot->buying_enabled) {
            $changes[] = [
                'type' => 'buying_enabled',
                'field' => 'buying_enabled',
                'old_value' => $previousSnapshot->buying_enabled,
                'new_value' => $newSnapshot->buying_enabled,
                'is_material' => true,
                'severity' => 'high',
                'description' => $newSnapshot->buying_enabled
                    ? 'Buying has been enabled'
                    : 'Buying has been disabled',
            ];
        }

        // Tier 2 approval change (critical for buying)
        if ($newSnapshot->tier_2_approved !== $previousSnapshot->tier_2_approved) {
            $changes[] = [
                'type' => 'tier_approval',
                'field' => 'tier_2_approved',
                'old_value' => $previousSnapshot->tier_2_approved,
                'new_value' => $newSnapshot->tier_2_approved,
                'is_material' => true,
                'severity' => 'high',
                'description' => $newSnapshot->tier_2_approved
                    ? 'Tier 2 has been approved'
                    : 'Tier 2 approval has been revoked',
            ];
        }

        // Risk level change
        if ($newSnapshot->risk_level !== $previousSnapshot->risk_level) {
            $isUpgrade = $this->compareRiskLevels($newSnapshot->risk_level, $previousSnapshot->risk_level) > 0;

            $changes[] = [
                'type' => 'risk_level',
                'field' => 'risk_level',
                'old_value' => $previousSnapshot->risk_level,
                'new_value' => $newSnapshot->risk_level,
                'is_material' => $isUpgrade, // Only material if risk increased
                'severity' => $isUpgrade ? 'high' : 'medium',
                'description' => "Platform risk level changed from {$previousSnapshot->risk_level} to {$newSnapshot->risk_level}",
            ];
        }

        // Suspension status change
        if ($newSnapshot->is_suspended !== $previousSnapshot->is_suspended) {
            $changes[] = [
                'type' => 'suspension',
                'field' => 'is_suspended',
                'old_value' => $previousSnapshot->is_suspended,
                'new_value' => $newSnapshot->is_suspended,
                'is_material' => true,
                'severity' => 'critical',
                'description' => $newSnapshot->is_suspended
                    ? 'Company has been suspended'
                    : 'Company suspension has been lifted',
            ];
        }

        return array_filter($changes, fn($c) => $c['is_material']);
    }

    /**
     * Apply buy impact based on material changes
     */
    protected function applyBuyImpact(int $companyId, array $materialChanges): array
    {
        $highestSeverity = $this->getHighestSeverity($materialChanges);

        $impact = [
            'buying_paused' => false,
            'requires_acknowledgement' => false,
            'warning_message' => null,
        ];

        // Critical or high severity changes → pause buying
        if (in_array($highestSeverity, ['critical', 'high'])) {
            DB::table('companies')
                ->where('id', $companyId)
                ->update([
                    'buying_enabled' => false,
                    'buying_pause_reason' => 'Material changes detected - requires platform review',
                    'buying_paused_at' => now(),
                    'buying_pause_trigger' => 'material_change_detected',
                ]);

            $impact['buying_paused'] = true;
            $impact['pause_reason'] = 'Material changes detected';
        }

        // All material changes require acknowledgement (even if buying not paused)
        $impact['requires_acknowledgement'] = true;
        $impact['warning_message'] = $this->generateWarningMessage($materialChanges);

        return $impact;
    }

    /**
     * Check if material changes require investor acknowledgement
     */
    protected function requiresInvestorAcknowledgement(array $materialChanges): bool
    {
        // All material changes require acknowledgement
        return !empty($materialChanges);
    }

    /**
     * Get acknowledgement text for investor
     */
    protected function getAcknowledgementText(array $materialChanges): string
    {
        $changeDescriptions = array_map(fn($c) => $c['description'], $materialChanges);

        $text = "Material changes have been detected in this company's platform context:\n\n";
        foreach ($changeDescriptions as $desc) {
            $text .= "• {$desc}\n";
        }
        $text .= "\nBy proceeding, you acknowledge that you have reviewed these material changes and understand that ";
        $text .= "they may affect the company's investment profile. Your investment will be bound to the current ";
        $text .= "platform context snapshot, which includes these changes.";

        return $text;
    }

    /**
     * Generate warning message for material changes
     */
    protected function generateWarningMessage(array $materialChanges): string
    {
        $count = count($materialChanges);
        $highestSeverity = $this->getHighestSeverity($materialChanges);

        return "Material changes detected ({$count} change" . ($count > 1 ? 's' : '') . ", severity: {$highestSeverity}). " .
               "New investors must acknowledge these changes before investing.";
    }

    /**
     * Get highest severity from changes
     */
    protected function getHighestSeverity(array $changes): string
    {
        $severities = array_column($changes, 'severity');
        $order = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];

        $maxSeverity = 'low';
        $maxLevel = 0;

        foreach ($severities as $severity) {
            $level = $order[$severity] ?? 0;
            if ($level > $maxLevel) {
                $maxLevel = $level;
                $maxSeverity = $severity;
            }
        }

        return $maxSeverity;
    }

    /**
     * Compare risk levels (returns: -1 if decreased, 0 if same, 1 if increased)
     */
    protected function compareRiskLevels(string $new, string $old): int
    {
        $order = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $newLevel = $order[$new] ?? 0;
        $oldLevel = $order[$old] ?? 0;

        return $newLevel <=> $oldLevel;
    }
}
