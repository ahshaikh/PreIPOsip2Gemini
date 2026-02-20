<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Models\InvestmentDisclosureSnapshot;
use App\Repositories\ApprovedDisclosureRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 STABILIZATION - Issue 5: Investment Snapshot Service
 * PHASE 1 AUDIT FIX: Now uses ApprovedDisclosureRepository for authority-enforced data
 * V-AUDIT-FIX-2026: Added integrity verification and hash algorithm documentation
 *
 * PURPOSE:
 * Captures immutable snapshot of all disclosures at investment purchase.
 * Proves exactly what investor saw when they made decision.
 *
 * HASH ALGORITHM (V-AUDIT-FIX-2026):
 * - Algorithm: SHA-256
 * - Input: JSON-encoded disclosure data (sorted keys for determinism)
 * - Output: 64-character hex string
 * - Usage: Each disclosure version has a version_hash computed at approval time
 * - Verification: verifySnapshotIntegrity() recomputes and compares hashes
 *
 * PHASE 1 AUDIT REQUIREMENTS:
 * 1. ONLY capture approved disclosures (not draft, submitted, rejected, etc.)
 * 2. Use IMMUTABLE version data from DisclosureVersion, NOT mutable CompanyDisclosure
 * 3. HARD FAIL if approved disclosure lacks a valid version (invariant violation)
 * 4. Prevent mixed-version reads or fallback to draft/previous disclosures
 *
 * USAGE:
 * Call captureAtPurchase() when investment is created.
 * Call getSnapshotForInvestment() for dispute resolution.
 * Call verifySnapshotIntegrity() to detect tampering.
 */
class InvestmentSnapshotService
{
    protected ApprovedDisclosureRepository $disclosureRepository;

    public function __construct(?ApprovedDisclosureRepository $disclosureRepository = null)
    {
        $this->disclosureRepository = $disclosureRepository ?? new ApprovedDisclosureRepository();
    }

    /**
     * Capture snapshot at investment purchase
     *
     * PHASE 4 STABILIZATION - Issue 2: Full Investor View Freeze
     * Now also captures and binds platform context snapshot.
     *
     * PHASE 5 - Issue 4: Investor Snapshotting
     * Now also captures public page view, warnings, and acknowledgements.
     *
     * @param int $investmentId
     * @param User $investor
     * @param Company $company
     * @param array $acknowledgementsGranted Acknowledgements investor granted
     * @return int Snapshot ID
     */
    public function captureAtPurchase(
        int $investmentId,
        User $investor,
        Company $company,
        array $acknowledgementsGranted = []
    ): int {
        DB::beginTransaction();

        try {
            // PHASE 4 STABILIZATION - Issue 2: Ensure platform context snapshot exists
            $platformContextService = new PlatformContextSnapshotService();
            $platformContextSnapshotId = $platformContextService->ensureCurrentSnapshot($company);

            // Link investment to platform context snapshot
            DB::table('investments')
                ->where('id', $investmentId)
                ->update([
                    'platform_context_snapshot_id' => $platformContextSnapshotId,
                ]);

            Log::info('PHASE 4: Investment linked to platform context snapshot', [
                'investment_id' => $investmentId,
                'platform_context_snapshot_id' => $platformContextSnapshotId,
                'company_id' => $company->id,
            ]);

            // PHASE 5 - Issue 4: Capture public page view investor saw
            $publicPageService = new PublicCompanyPageService();
            $publicPageView = $publicPageService->getPublicCompanyPage($company->id, $investor->id);

            // PHASE 5 - Issue 4: Capture acknowledgements granted
            $riskAckService = new RiskAcknowledgementService();
            $acknowledgementsStatus = $riskAckService->hasAcknowledgedAllRisks(
                $investor->id,
                $company->id,
                true // Include material changes
            );
            // =================================================================
            // PHASE 1 AUDIT FIX: Use ApprovedDisclosureRepository
            // =================================================================
            // CRITICAL: Only capture APPROVED disclosures with IMMUTABLE version data
            // This ensures:
            // 1. Draft/submitted/rejected disclosures are NEVER included
            // 2. Disclosure data comes from locked DisclosureVersion (immutable)
            // 3. Hard failure if approved disclosure lacks a valid version
            // =================================================================

            $approvedDisclosures = $this->disclosureRepository->getApprovedDisclosuresForInvestor($company->id);

            $disclosureSnapshot = [];
            $versionMap = [];
            $wasUnderReview = false;  // Always false now - we only capture approved

            foreach ($approvedDisclosures as $disclosureId => $disclosureData) {
                $disclosureSnapshot[$disclosureId] = [
                    'module_id' => $disclosureData['module_id'],
                    'module_name' => $disclosureData['module_name'],
                    'module_code' => $disclosureData['module_code'],
                    'status' => 'approved',  // GUARANTEED by repository
                    // CRITICAL: This is IMMUTABLE version data, NOT mutable disclosure data
                    'data' => $disclosureData['data'],
                    'version_id' => $disclosureData['version_id'],
                    'version_number' => $disclosureData['version_number'],
                    'version_hash' => $disclosureData['version_hash'],
                    'approved_at' => $disclosureData['approved_at'],
                    'is_immutable' => true,  // Flag for audit trail
                ];

                $versionMap[$disclosureId] = $disclosureData['version_id'];
            }

            Log::info('PHASE 1 AUDIT: Captured approved-only disclosures for snapshot', [
                'investment_id' => $investmentId,
                'company_id' => $company->id,
                'approved_count' => count($approvedDisclosures),
                'version_map' => $versionMap,
            ]);

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
            // P0 REMEDIATION: Use Eloquent model for immutability enforcement
            $snapshot = InvestmentDisclosureSnapshot::create([
                'investment_id' => $investmentId,
                'user_id' => $investor->id,
                'company_id' => $company->id,
                'snapshot_timestamp' => now(),
                'snapshot_trigger' => 'investment_purchase',
                'disclosure_snapshot' => $disclosureSnapshot, // Auto-casted to JSON by model
                'metrics_snapshot' => $metricsSnapshot,
                'risk_flags_snapshot' => $flagsSnapshot,
                'valuation_context_snapshot' => $valuationSnapshot,
                'governance_snapshot' => $governanceSnapshot, // PHASE 2 HARDENING
                'disclosure_versions_map' => $versionMap,
                'was_under_review' => $wasUnderReview,
                'company_lifecycle_state' => $company->lifecycle_state,
                'buying_enabled_at_snapshot' => $company->buying_enabled ?? true,

                // PHASE 5 - Issue 4: Investor Snapshotting
                'public_page_view_snapshot' => $publicPageView,
                'acknowledgements_snapshot' => $acknowledgementsStatus,
                'acknowledgements_granted' => $acknowledgementsGranted,

                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => request()->session()?->getId(),
                'is_immutable' => true,
                'locked_at' => now(),
            ]);

            $snapshotId = $snapshot->id;

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

    /**
     * PHASE 4 STABILIZATION - Issue 2: Get complete investor view
     *
     * Returns disclosure snapshot + platform context snapshot.
     * This is the COMPLETE immutable view investor had at purchase.
     *
     * PHASE 5 - Issue 4: Now also includes public page view and acknowledgements.
     *
     * @param int $investmentId
     * @return array Complete investor view
     */
    public function getCompleteInvestorView(int $investmentId): array
    {
        // Get investment
        $investment = DB::table('investments')->find($investmentId);
        if (!$investment) {
            return ['error' => 'Investment not found'];
        }

        // Get disclosure snapshot
        $disclosureSnapshot = $this->getSnapshotForInvestment($investmentId);

        // Get platform context snapshot
        $platformContextSnapshot = null;
        if ($investment->platform_context_snapshot_id) {
            $platformContextService = new PlatformContextSnapshotService();
            $platformContextSnapshot = $platformContextService->getSnapshot(
                $investment->platform_context_snapshot_id
            );
        }

        return [
            'investment_id' => $investmentId,
            'investment_date' => $investment->created_at,
            'company_id' => $investment->company_id,
            'user_id' => $investment->user_id,

            // Disclosure snapshot (what disclosures investor saw)
            'disclosure_snapshot' => $disclosureSnapshot ? [
                'snapshot_id' => $disclosureSnapshot->id,
                'snapshot_timestamp' => $disclosureSnapshot->snapshot_timestamp,
                'disclosures' => json_decode($disclosureSnapshot->disclosure_snapshot, true),
                'governance' => json_decode($disclosureSnapshot->governance_snapshot, true),
                'metrics' => json_decode($disclosureSnapshot->metrics_snapshot, true),
                'risk_flags' => json_decode($disclosureSnapshot->risk_flags_snapshot, true),
            ] : null,

            // Platform context snapshot (platform state at investment time)
            'platform_context_snapshot' => $platformContextSnapshot ? [
                'snapshot_id' => $platformContextSnapshot->id,
                'snapshot_at' => $platformContextSnapshot->snapshot_at,
                'lifecycle_state' => $platformContextSnapshot->lifecycle_state,
                'buying_enabled' => $platformContextSnapshot->buying_enabled,
                'tier_approvals' => [
                    'tier_1' => $platformContextSnapshot->tier_1_approved,
                    'tier_2' => $platformContextSnapshot->tier_2_approved,
                    'tier_3' => $platformContextSnapshot->tier_3_approved,
                ],
                'restrictions' => [
                    'is_suspended' => $platformContextSnapshot->is_suspended,
                    'is_frozen' => $platformContextSnapshot->is_frozen,
                    'is_under_investigation' => $platformContextSnapshot->is_under_investigation,
                ],
                'risk_assessment' => [
                    'platform_risk_score' => $platformContextSnapshot->platform_risk_score,
                    'risk_level' => $platformContextSnapshot->risk_level,
                    'risk_flags' => json_decode($platformContextSnapshot->risk_flags, true),
                ],
                'is_locked' => $platformContextSnapshot->is_locked,
                'valid_from' => $platformContextSnapshot->valid_from,
                'valid_until' => $platformContextSnapshot->valid_until,
            ] : null,

            // PHASE 5 - Issue 4: Public page view snapshot
            'public_page_view' => $disclosureSnapshot && $disclosureSnapshot->public_page_view_snapshot
                ? json_decode($disclosureSnapshot->public_page_view_snapshot, true)
                : null,

            // PHASE 5 - Issue 4: Acknowledgements snapshot
            'acknowledgements' => [
                'status_at_purchase' => $disclosureSnapshot && $disclosureSnapshot->acknowledgements_snapshot
                    ? json_decode($disclosureSnapshot->acknowledgements_snapshot, true)
                    : null,
                'granted_during_purchase' => $disclosureSnapshot && $disclosureSnapshot->acknowledgements_granted
                    ? json_decode($disclosureSnapshot->acknowledgements_granted, true)
                    : null,
            ],

            'snapshot_frozen' => true,
            'recalculation_forbidden' => true,
            'immutability_guarantee' => 'These snapshots are permanently frozen and cannot be recalculated or mutated. This includes disclosure data, platform context, public page view, and risk acknowledgements.',
        ];
    }

    /**
     * V-AUDIT-FIX-2026: Verify snapshot integrity
     *
     * Recomputes hashes for all disclosures in snapshot and compares against
     * stored version_hash values. Detects any tampering or corruption.
     *
     * ALGORITHM: SHA-256 of JSON-encoded disclosure data (sorted keys)
     *
     * @param int $snapshotId
     * @return array Verification result with details
     */
    public function verifySnapshotIntegrity(int $snapshotId): array
    {
        $snapshot = InvestmentDisclosureSnapshot::find($snapshotId);

        if (!$snapshot) {
            return [
                'verified' => false,
                'snapshot_id' => $snapshotId,
                'error' => 'Snapshot not found',
                'tamper_detected' => null,
            ];
        }

        $disclosureSnapshot = $snapshot->disclosure_snapshot;
        if (empty($disclosureSnapshot)) {
            return [
                'verified' => true,
                'snapshot_id' => $snapshotId,
                'disclosures_verified' => 0,
                'message' => 'No disclosures in snapshot',
                'tamper_detected' => false,
            ];
        }

        $verificationResults = [];
        $allValid = true;
        $tamperedDisclosures = [];

        foreach ($disclosureSnapshot as $disclosureId => $disclosureData) {
            $storedHash = $disclosureData['version_hash'] ?? null;

            if (!$storedHash) {
                // No hash stored - cannot verify (legacy data)
                $verificationResults[$disclosureId] = [
                    'status' => 'no_hash',
                    'message' => 'No version_hash stored for verification',
                ];
                continue;
            }

            // Recompute hash from disclosure data
            $dataToHash = $disclosureData['data'] ?? [];
            $computedHash = $this->computeHash($dataToHash);

            $isValid = hash_equals($storedHash, $computedHash);

            $verificationResults[$disclosureId] = [
                'status' => $isValid ? 'valid' : 'tampered',
                'stored_hash' => $storedHash,
                'computed_hash' => $computedHash,
                'module_code' => $disclosureData['module_code'] ?? 'unknown',
            ];

            if (!$isValid) {
                $allValid = false;
                $tamperedDisclosures[] = [
                    'disclosure_id' => $disclosureId,
                    'module_code' => $disclosureData['module_code'] ?? 'unknown',
                    'stored_hash' => $storedHash,
                    'computed_hash' => $computedHash,
                ];
            }
        }

        // Log if tampering detected
        if (!$allValid) {
            Log::critical('[SNAPSHOT INTEGRITY VIOLATION] Tampering detected', [
                'snapshot_id' => $snapshotId,
                'investment_id' => $snapshot->investment_id,
                'company_id' => $snapshot->company_id,
                'tampered_disclosures' => $tamperedDisclosures,
            ]);
        }

        return [
            'verified' => $allValid,
            'snapshot_id' => $snapshotId,
            'investment_id' => $snapshot->investment_id,
            'company_id' => $snapshot->company_id,
            'disclosures_verified' => count($verificationResults),
            'tamper_detected' => !$allValid,
            'tampered_disclosures' => $tamperedDisclosures,
            'verification_details' => $verificationResults,
            'verified_at' => now()->toIso8601String(),
        ];
    }

    /**
     * V-AUDIT-FIX-2026: Compute SHA-256 hash of disclosure data
     *
     * @param array $data Disclosure data to hash
     * @return string 64-character hex hash
     */
    protected function computeHash(array $data): string
    {
        // Sort keys recursively for deterministic hashing
        $sortedData = $this->sortArrayRecursively($data);

        // JSON encode with consistent formatting
        $json = json_encode($sortedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $json);
    }

    /**
     * Sort array keys recursively for deterministic serialization.
     */
    protected function sortArrayRecursively(array $array): array
    {
        ksort($array);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sortArrayRecursively($value);
            }
        }

        return $array;
    }

    /**
     * V-AUDIT-FIX-2026: Verify all snapshots for a company
     *
     * Bulk verification for audit purposes.
     *
     * @param int $companyId
     * @return array Summary of verification results
     */
    public function verifyAllSnapshotsForCompany(int $companyId): array
    {
        $snapshots = InvestmentDisclosureSnapshot::where('company_id', $companyId)->get();

        $results = [
            'company_id' => $companyId,
            'total_snapshots' => $snapshots->count(),
            'verified' => 0,
            'tampered' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($snapshots as $snapshot) {
            $result = $this->verifySnapshotIntegrity($snapshot->id);

            if ($result['verified']) {
                $results['verified']++;
            } elseif ($result['tamper_detected']) {
                $results['tampered']++;
                $results['details'][] = [
                    'snapshot_id' => $snapshot->id,
                    'investment_id' => $snapshot->investment_id,
                    'status' => 'tampered',
                    'tampered_disclosures' => $result['tampered_disclosures'],
                ];
            } else {
                $results['errors']++;
                $results['details'][] = [
                    'snapshot_id' => $snapshot->id,
                    'status' => 'error',
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }
        }

        return $results;
    }
}
