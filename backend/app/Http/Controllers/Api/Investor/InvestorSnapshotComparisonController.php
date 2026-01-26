<?php

namespace App\Http\Controllers\Api\Investor;

use App\Http\Controllers\Controller;
use App\Models\PlatformRiskFlag;
use App\Services\InvestmentSnapshotService;
use App\Services\PlatformContextSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * P0 FIX (GAP 36): Investor Snapshot Comparison Controller
 *
 * PURPOSE:
 * Provide "then vs now" comparison for investor investments.
 * Returns investment-time snapshot vs current platform state.
 *
 * STRUCTURE:
 * Matches SnapshotComparison TypeScript interface exactly:
 * - then: snapshot at investment time
 * - now: current platform state
 * - changes: diff summary
 */
class InvestorSnapshotComparisonController extends Controller
{
    /**
     * GET /investments/{investmentId}/snapshot-comparison
     *
     * Returns comparison between investment-time snapshot and current state.
     * Read-only, idempotent, auditable.
     */
    public function getSnapshotComparison(Request $request, int $investmentId)
    {
        $user = $request->user();

        // Verify investment belongs to user
        $investment = DB::table('investments')
            ->where('id', $investmentId)
            ->where('user_id', $user->id)
            ->first();

        if (!$investment) {
            return response()->json([
                'success' => false,
                'message' => 'Investment not found or access denied',
            ], 404);
        }

        // Get company
        $company = DB::table('companies')->find($investment->company_id);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        // Get investment-time snapshot ("then")
        $snapshotService = new InvestmentSnapshotService();
        $investorView = $snapshotService->getCompleteInvestorView($investmentId);

        // Build "then" state from snapshot
        $thenState = $this->buildThenState($investorView, $investment);

        // Build "now" state from current data
        $nowState = $this->buildNowState($company);

        // Calculate changes
        $changes = $this->calculateChanges($thenState, $nowState);

        return response()->json([
            'success' => true,
            'data' => [
                'investment_id' => $investmentId,
                'investment_date' => $investment->created_at,
                'company_name' => $company->name,
                'then' => $thenState,
                'now' => $nowState,
                'changes' => $changes,
            ],
        ]);
    }

    /**
     * Build "then" state from investment snapshot
     */
    private function buildThenState(array $investorView, object $investment): array
    {
        $platformContext = $investorView['platform_context_snapshot'] ?? [];
        $disclosureSnapshot = $investorView['disclosure_snapshot'] ?? [];

        // Get risk flags from snapshot
        $riskFlagsSnapshot = $disclosureSnapshot['risk_flags'] ?? [];

        return [
            'snapshot_id' => $platformContext['snapshot_id'] ?? $disclosureSnapshot['snapshot_id'] ?? 0,
            'snapshot_date' => $platformContext['snapshot_at'] ?? $investment->created_at,
            'lifecycle_state' => $platformContext['lifecycle_state'] ?? 'unknown',
            'buying_enabled' => $platformContext['buying_enabled'] ?? false,
            'risk_level' => $platformContext['risk_assessment']['risk_level'] ?? 'unknown',
            'compliance_score' => $platformContext['risk_assessment']['platform_risk_score'] ?? 0,
            'risk_flags' => $this->transformSnapshotRiskFlags($riskFlagsSnapshot),
        ];
    }

    /**
     * Build "now" state from current company data
     */
    private function buildNowState(object $company): array
    {
        // Get current platform context snapshot
        $platformContextService = new PlatformContextSnapshotService();
        $currentSnapshot = $platformContextService->getCurrentSnapshot($company->id);

        // Get current risk flags
        $currentRiskFlags = PlatformRiskFlag::where('company_id', $company->id)
            ->where('status', 'active')
            ->where('is_visible_to_investors', true)
            ->get();

        return [
            'snapshot_id' => $currentSnapshot->id ?? 0,
            'snapshot_date' => $currentSnapshot->snapshot_at ?? now()->toIso8601String(),
            'lifecycle_state' => $company->lifecycle_state ?? 'unknown',
            'buying_enabled' => $company->buying_enabled ?? false,
            'risk_level' => $company->risk_level ?? 'unknown',
            'compliance_score' => $company->platform_risk_score ?? 0,
            'risk_flags' => $this->transformCurrentRiskFlags($currentRiskFlags),
        ];
    }

    /**
     * Transform snapshot risk flags to frontend format
     */
    private function transformSnapshotRiskFlags(array $flags): array
    {
        $categoryMap = [
            'financial' => 'financial',
            'governance' => 'operational',
            'legal' => 'regulatory',
            'disclosure_quality' => 'regulatory',
            'market' => 'market',
            'operational' => 'operational',
            'liquidity' => 'liquidity',
        ];

        return array_map(function ($flag, $index) use ($categoryMap) {
            return [
                'id' => $index + 1, // Snapshot flags don't have IDs
                'code' => $flag['flag_type'] ?? 'unknown',
                'name' => ucwords(str_replace('_', ' ', $flag['flag_type'] ?? 'Unknown')),
                'severity' => $this->normalizeSeverity($flag['severity'] ?? 'medium'),
                'category' => $categoryMap[$flag['category'] ?? 'operational'] ?? 'operational',
                'is_active' => true,
                'rationale' => $flag['description'] ?? '',
                'mitigation_guidance' => 'Review underlying disclosures.',
                'created_at' => $flag['detected_at'] ?? null,
                'updated_at' => $flag['detected_at'] ?? null,
            ];
        }, $flags, array_keys($flags));
    }

    /**
     * Transform current risk flags to frontend format
     */
    private function transformCurrentRiskFlags($flags): array
    {
        $categoryMap = [
            'financial' => 'financial',
            'governance' => 'operational',
            'legal' => 'regulatory',
            'disclosure_quality' => 'regulatory',
            'market' => 'market',
            'operational' => 'operational',
            'liquidity' => 'liquidity',
        ];

        return $flags->map(function ($flag) use ($categoryMap) {
            return [
                'id' => $flag->id,
                'code' => $flag->flag_type,
                'name' => ucwords(str_replace('_', ' ', $flag->flag_type)),
                'severity' => $this->normalizeSeverity($flag->severity),
                'category' => $categoryMap[$flag->category] ?? 'operational',
                'is_active' => $flag->status === 'active',
                'rationale' => $flag->description,
                'mitigation_guidance' => $flag->investor_message ?? 'Review underlying disclosures.',
                'created_at' => $flag->created_at?->toIso8601String(),
                'updated_at' => $flag->updated_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Calculate changes between then and now
     */
    private function calculateChanges(array $then, array $now): array
    {
        // Find new risk flags (in now but not in then)
        $thenFlagCodes = array_column($then['risk_flags'], 'code');
        $nowFlagCodes = array_column($now['risk_flags'], 'code');

        $newFlagCodes = array_diff($nowFlagCodes, $thenFlagCodes);
        $removedFlagCodes = array_diff($thenFlagCodes, $nowFlagCodes);

        $newFlags = array_filter($now['risk_flags'], fn($f) => in_array($f['code'], $newFlagCodes));
        $removedFlags = array_filter($then['risk_flags'], fn($f) => in_array($f['code'], $removedFlagCodes));

        return [
            'lifecycle_state_changed' => $then['lifecycle_state'] !== $now['lifecycle_state'],
            'buying_status_changed' => $then['buying_enabled'] !== $now['buying_enabled'],
            'risk_level_changed' => $then['risk_level'] !== $now['risk_level'],
            'compliance_score_delta' => $now['compliance_score'] - $then['compliance_score'],
            'new_risk_flags' => array_values($newFlags),
            'removed_risk_flags' => array_values($removedFlags),
        ];
    }

    /**
     * Normalize severity to frontend enum
     */
    private function normalizeSeverity(string $severity): string
    {
        $validSeverities = ['low', 'medium', 'high', 'critical'];
        $severity = strtolower($severity);

        if ($severity === 'info') {
            return 'low';
        }

        return in_array($severity, $validSeverities) ? $severity : 'medium';
    }
}
