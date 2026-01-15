<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 STABILIZATION - Issue 5: Investment Snapshot Service
 *
 * PURPOSE:
 * Captures immutable snapshot of all disclosures at investment purchase.
 * Proves exactly what investor saw when they made decision.
 *
 * USAGE:
 * Call captureAtPurchase() when investment is created.
 * Call getSnapshotForInvestment() for dispute resolution.
 */
class InvestmentSnapshotService
{
    /**
     * Capture snapshot at investment purchase
     *
     * @param int $investmentId
     * @param User $investor
     * @param Company $company
     * @return int Snapshot ID
     */
    public function captureAtPurchase(int $investmentId, User $investor, Company $company): int
    {
        DB::beginTransaction();

        try {
            // 1. Gather all company disclosures
            $disclosures = DB::table('company_disclosures')
                ->where('company_id', $company->id)
                ->get();

            $disclosureSnapshot = [];
            $versionMap = [];
            $wasUnderReview = false;

            foreach ($disclosures as $disclosure) {
                // Get latest version for each disclosure
                $latestVersion = DB::table('disclosure_versions')
                    ->where('company_disclosure_id', $disclosure->id)
                    ->where('approved_at', '<=', now())
                    ->orderBy('version_number', 'desc')
                    ->first();

                $disclosureSnapshot[$disclosure->id] = [
                    'module_id' => $disclosure->disclosure_module_id,
                    'module_name' => $this->getModuleName($disclosure->disclosure_module_id),
                    'status' => $disclosure->status,
                    'data' => json_decode($disclosure->disclosure_data ?? '{}', true),
                    'version_id' => $latestVersion->id ?? null,
                    'version_number' => $latestVersion->version_number ?? null,
                ];

                if ($latestVersion) {
                    $versionMap[$disclosure->id] = $latestVersion->id;
                }

                if ($disclosure->status === 'under_review') {
                    $wasUnderReview = true;
                }
            }

            // 2. Gather platform metrics
            $metrics = DB::table('platform_company_metrics')
                ->where('company_id', $company->id)
                ->first();

            $metricsSnapshot = $metrics ? [
                'disclosure_completeness_score' => $metrics->disclosure_completeness_score,
                'financial_health_band' => $metrics->financial_health_band,
                'governance_quality_band' => $metrics->governance_quality_band,
                'risk_intensity_band' => $metrics->risk_intensity_band,
                'last_platform_review' => $metrics->last_platform_review,
            ] : null;

            // 3. Gather active risk flags
            $riskFlags = DB::table('platform_risk_flags')
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->where('is_visible_to_investors', true)
                ->get();

            $flagsSnapshot = $riskFlags->map(function ($flag) {
                return [
                    'flag_type' => $flag->flag_type,
                    'severity' => $flag->severity,
                    'category' => $flag->category,
                    'description' => $flag->description,
                    'detected_at' => $flag->detected_at,
                ];
            })->toArray();

            // 4. Gather valuation context
            $valuation = DB::table('platform_valuation_context')
                ->where('company_id', $company->id)
                ->where('is_stale', false)
                ->first();

            $valuationSnapshot = $valuation ? [
                'valuation_context' => $valuation->valuation_context ?? null,
                'company_valuation' => $valuation->company_valuation,
                'peer_median_valuation' => $valuation->peer_median_valuation,
                'liquidity_outlook' => $valuation->liquidity_outlook,
            ] : null;

            // PHASE 2 HARDENING: Capture governance state
            $governanceSnapshot = [
                'lifecycle_state' => $company->lifecycle_state,
                'buying_enabled' => $company->buying_enabled ?? true,
                'governance_state_version' => $company->governance_state_version ?? 1,
                'is_suspended' => $company->is_suspended ?? false,
                'suspension_reason' => $company->suspension_reason ?? null,
                'tier_1_approved_at' => $company->tier_1_approved_at,
                'tier_2_approved_at' => $company->tier_2_approved_at,
                'tier_3_approved_at' => $company->tier_3_approved_at,
            ];

            // 5. Create snapshot record
            $snapshotId = DB::table('investment_disclosure_snapshots')->insertGetId([
                'investment_id' => $investmentId,
                'user_id' => $investor->id,
                'company_id' => $company->id,
                'snapshot_timestamp' => now(),
                'snapshot_trigger' => 'investment_purchase',
                'disclosure_snapshot' => json_encode($disclosureSnapshot),
                'metrics_snapshot' => json_encode($metricsSnapshot),
                'risk_flags_snapshot' => json_encode($flagsSnapshot),
                'valuation_context_snapshot' => json_encode($valuationSnapshot),
                'governance_snapshot' => json_encode($governanceSnapshot), // PHASE 2 HARDENING
                'disclosure_versions_map' => json_encode($versionMap),
                'was_under_review' => $wasUnderReview,
                'company_lifecycle_state' => $company->lifecycle_state,
                'buying_enabled_at_snapshot' => $company->buying_enabled ?? true,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => request()->session()?->getId(),
                'is_immutable' => true,
                'locked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('Investment snapshot captured', [
                'snapshot_id' => $snapshotId,
                'investment_id' => $investmentId,
                'investor_id' => $investor->id,
                'company_id' => $company->id,
                'disclosures_count' => count($disclosureSnapshot),
                'was_under_review' => $wasUnderReview,
            ]);

            return $snapshotId;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to capture investment snapshot', [
                'investment_id' => $investmentId,
                'investor_id' => $investor->id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get snapshot for an investment
     *
     * @param int $investmentId
     * @return object|null
     */
    public function getSnapshotForInvestment(int $investmentId): ?object
    {
        return DB::table('investment_disclosure_snapshots')
            ->where('investment_id', $investmentId)
            ->first();
    }

    /**
     * Get investor's view history for a company
     *
     * @param int $userId
     * @param int $companyId
     * @return array
     */
    public function getInvestorViewHistory(int $userId, int $companyId): array
    {
        return DB::table('investment_disclosure_snapshots')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->orderBy('snapshot_timestamp', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get module name for disclosure
     *
     * @param int $moduleId
     * @return string
     */
    protected function getModuleName(int $moduleId): string
    {
        $module = DB::table('disclosure_modules')->find($moduleId);
        return $module->name ?? 'Unknown Module';
    }

    /**
     * Compare snapshots (for dispute resolution)
     *
     * @param int $snapshotId1
     * @param int $snapshotId2
     * @return array Differences
     */
    public function compareSnapshots(int $snapshotId1, int $snapshotId2): array
    {
        $snapshot1 = DB::table('investment_disclosure_snapshots')->find($snapshotId1);
        $snapshot2 = DB::table('investment_disclosure_snapshots')->find($snapshotId2);

        if (!$snapshot1 || !$snapshot2) {
            return [];
        }

        $disc1 = json_decode($snapshot1->disclosure_snapshot, true);
        $disc2 = json_decode($snapshot2->disclosure_snapshot, true);

        $differences = [];

        foreach ($disc1 as $discId => $data1) {
            $data2 = $disc2[$discId] ?? null;

            if (!$data2) {
                $differences[] = [
                    'disclosure_id' => $discId,
                    'change_type' => 'deleted',
                    'module' => $data1['module_name'],
                ];
                continue;
            }

            if ($data1['version_id'] !== $data2['version_id']) {
                $differences[] = [
                    'disclosure_id' => $discId,
                    'change_type' => 'version_changed',
                    'module' => $data1['module_name'],
                    'old_version' => $data1['version_number'],
                    'new_version' => $data2['version_number'],
                ];
            }
        }

        return $differences;
    }
}
