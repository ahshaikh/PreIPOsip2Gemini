<?php

namespace App\Services;

use App\Enums\ArtifactFreshness;
use App\Enums\PillarVitality;
use App\Enums\DocumentType;
use App\Enums\DisclosurePillar;
use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureModule;
use App\Models\PillarVitalitySnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DisclosureFreshnessService
 *
 * Backend-authoritative service for computing disclosure freshness and pillar vitality.
 *
 * CORE PRINCIPLES:
 * 1. All freshness facts are computed here, not in frontend
 * 2. Enums are frozen: current|aging|stale|unstable and healthy|needs_attention|at_risk
 * 3. freshness_override is AUDIT-ONLY (never improves state silently, always visible in admin)
 * 4. No numeric scores or percentages - only categorical states and fact-based signals
 */
class DisclosureFreshnessService
{
    // Thresholds for freshness state transitions
    private const AGING_THRESHOLD_PERCENT = 70;  // Aging starts at 70% of expected window
    private const STABILITY_AGING_PERCENT = 80;  // Version-controlled aging at 80% of window

    /**
     * Compute freshness state for a single disclosure artifact.
     *
     * @return array{
     *   state: string,
     *   days_since_approval: int,
     *   days_until_stale: int|null,
     *   is_update_overdue: bool,
     *   is_change_excessive: bool,
     *   update_count_in_window: int,
     *   next_update_expected: string|null,
     *   signal_text: string,
     *   signal_text_admin: string,
     *   signal_text_subscriber: string
     * }
     */
    public function computeArtifactFreshness(CompanyDisclosure $disclosure): array
    {
        $module = $disclosure->disclosureModule;

        // Only compute freshness for approved disclosures
        if ($disclosure->status !== 'approved' || !$disclosure->approved_at) {
            return $this->buildFreshnessResult(
                state: null,
                daysSinceApproval: 0,
                signalText: 'Freshness not applicable for non-approved disclosures',
                signalTextAdmin: 'Not approved',
                signalTextSubscriber: null
            );
        }

        $daysSinceApproval = (int) Carbon::parse($disclosure->approved_at)->diffInDays(now());
        $documentType = $module->document_type;

        // Determine freshness based on document type
        if ($documentType === DocumentType::UPDATE_REQUIRED->value) {
            return $this->computeUpdateRequiredFreshness($disclosure, $module, $daysSinceApproval);
        } elseif ($documentType === DocumentType::VERSION_CONTROLLED->value) {
            return $this->computeVersionControlledFreshness($disclosure, $module, $daysSinceApproval);
        }

        // Default: no freshness config, treat as current
        return $this->buildFreshnessResult(
            state: ArtifactFreshness::CURRENT->value,
            daysSinceApproval: $daysSinceApproval,
            signalText: 'No freshness policy configured',
            signalTextAdmin: 'No freshness policy',
            signalTextSubscriber: null
        );
    }

    /**
     * Compute freshness for update-required documents (financials, cap table, etc.)
     */
    private function computeUpdateRequiredFreshness(
        CompanyDisclosure $disclosure,
        DisclosureModule $module,
        int $daysSinceApproval
    ): array {
        $expectedDays = $module->expected_update_days ?? 90; // Default 90 days
        $agingThreshold = (int) ($expectedDays * self::AGING_THRESHOLD_PERCENT / 100);
        $daysUntilStale = max(0, $expectedDays - $daysSinceApproval);
        $nextUpdateExpected = Carbon::parse($disclosure->approved_at)->addDays($expectedDays)->toDateString();

        if ($daysSinceApproval < $agingThreshold) {
            // Current - well within update window
            return $this->buildFreshnessResult(
                state: ArtifactFreshness::CURRENT->value,
                daysSinceApproval: $daysSinceApproval,
                daysUntilStale: $daysUntilStale,
                nextUpdateExpected: $nextUpdateExpected,
                signalText: "Updated {$daysSinceApproval} days ago",
                signalTextAdmin: "Current ({$daysSinceApproval}/{$expectedDays} days)",
                signalTextSubscriber: null
            );
        } elseif ($daysSinceApproval < $expectedDays) {
            // Aging - approaching staleness
            return $this->buildFreshnessResult(
                state: ArtifactFreshness::AGING->value,
                daysSinceApproval: $daysSinceApproval,
                daysUntilStale: $daysUntilStale,
                nextUpdateExpected: $nextUpdateExpected,
                signalText: "Update recommended within {$daysUntilStale} days",
                signalTextAdmin: "Aging ({$daysSinceApproval}/{$expectedDays} days) - update in {$daysUntilStale} days",
                signalTextSubscriber: 'Update Expected Soon'
            );
        } else {
            // Stale - beyond expected update window
            $overdueDays = $daysSinceApproval - $expectedDays;
            return $this->buildFreshnessResult(
                state: ArtifactFreshness::STALE->value,
                daysSinceApproval: $daysSinceApproval,
                daysUntilStale: 0,
                isUpdateOverdue: true,
                nextUpdateExpected: $nextUpdateExpected,
                signalText: "Update overdue by {$overdueDays} days",
                signalTextAdmin: "STALE - overdue by {$overdueDays} days (expected every {$expectedDays} days)",
                signalTextSubscriber: 'Update Pending'
            );
        }
    }

    /**
     * Compute freshness for version-controlled documents (articles, bylaws, etc.)
     */
    private function computeVersionControlledFreshness(
        CompanyDisclosure $disclosure,
        DisclosureModule $module,
        int $daysSinceApproval
    ): array {
        $stabilityWindow = $module->stability_window_days ?? 365; // Default 1 year
        $maxChanges = $module->max_changes_per_window ?? 2;
        $updateCount = $disclosure->update_count_in_window ?? 0;
        $agingThreshold = (int) ($stabilityWindow * self::STABILITY_AGING_PERCENT / 100);

        // Check for instability (excessive changes)
        if ($updateCount > $maxChanges) {
            return $this->buildFreshnessResult(
                state: ArtifactFreshness::UNSTABLE->value,
                daysSinceApproval: $daysSinceApproval,
                updateCountInWindow: $updateCount,
                isChangeExcessive: true,
                signalText: "{$updateCount} changes in {$stabilityWindow} days - excessive",
                signalTextAdmin: "UNSTABLE - {$updateCount} changes in {$stabilityWindow}-day window (max: {$maxChanges})",
                signalTextSubscriber: 'Frequent Changes' // Neutral - not falsely positive
            );
        }

        // Check for aging based on window
        if ($daysSinceApproval > $agingThreshold && $daysSinceApproval <= $stabilityWindow) {
            $daysRemaining = $stabilityWindow - $daysSinceApproval;
            return $this->buildFreshnessResult(
                state: ArtifactFreshness::AGING->value,
                daysSinceApproval: $daysSinceApproval,
                updateCountInWindow: $updateCount,
                daysUntilStale: $daysRemaining,
                signalText: "Approaching stability review ({$daysRemaining} days)",
                signalTextAdmin: "Aging - {$daysSinceApproval}/{$stabilityWindow} days",
                signalTextSubscriber: 'Update Expected Soon'
            );
        }

        // Version-controlled docs can technically go stale (beyond stability window without update)
        if ($daysSinceApproval > $stabilityWindow) {
            return $this->buildFreshnessResult(
                state: ArtifactFreshness::STALE->value,
                daysSinceApproval: $daysSinceApproval,
                updateCountInWindow: $updateCount,
                daysUntilStale: 0,
                signalText: "Stability review overdue",
                signalTextAdmin: "STALE - beyond {$stabilityWindow}-day stability window",
                signalTextSubscriber: 'Update Pending'
            );
        }

        // Current - stable and within window
        return $this->buildFreshnessResult(
            state: ArtifactFreshness::CURRENT->value,
            daysSinceApproval: $daysSinceApproval,
            updateCountInWindow: $updateCount,
            signalText: "Stable ({$updateCount} changes in window)",
            signalTextAdmin: "Current - stable ({$updateCount}/{$maxChanges} changes, {$daysSinceApproval}/{$stabilityWindow} days)",
            signalTextSubscriber: null
        );
    }

    /**
     * Compute vitality state for a pillar (category) across all company disclosures.
     *
     * @return array{
     *   state: string,
     *   total_artifacts: int,
     *   freshness_breakdown: array{current: int, aging: int, stale: int, unstable: int},
     *   drivers: array<array{module_code: string, module_name: string, freshness_state: string, signal_text: string}>,
     *   pillar_signal_text: string
     * }
     */
    public function computePillarVitality(Company $company, string $pillar): array
    {
        // Get all approved disclosures for this pillar
        $disclosures = CompanyDisclosure::query()
            ->where('company_id', $company->id)
            ->where('status', 'approved')
            ->whereHas('disclosureModule', function ($q) use ($pillar) {
                $q->where('category', $pillar);
            })
            ->with('disclosureModule')
            ->get();

        $breakdown = [
            'current' => 0,
            'aging' => 0,
            'stale' => 0,
            'unstable' => 0,
        ];
        $drivers = [];

        foreach ($disclosures as $disclosure) {
            $freshness = $this->computeArtifactFreshness($disclosure);
            $state = $freshness['state'];

            if ($state && isset($breakdown[$state])) {
                $breakdown[$state]++;
            }

            // Track drivers (artifacts causing degradation)
            if ($state && in_array($state, ['aging', 'stale', 'unstable'])) {
                $drivers[] = [
                    'module_code' => $disclosure->disclosureModule->code,
                    'module_name' => $disclosure->disclosureModule->name,
                    'freshness_state' => $state,
                    'signal_text' => $freshness['signal_text_admin'],
                    'days_since_approval' => $freshness['days_since_approval'],
                ];
            }
        }

        $total = array_sum($breakdown);
        $vitalityState = $this->determineVitalityState($breakdown);

        return [
            'state' => $vitalityState->value,
            'total_artifacts' => $total,
            'freshness_breakdown' => $breakdown,
            'drivers' => $drivers,
            'pillar_signal_text' => $this->buildPillarSignalText($vitalityState, $breakdown, $drivers),
        ];
    }

    /**
     * Compute coverage facts for a company at a given tier.
     *
     * NOTE: total_required intentionally excluded to prevent percentage derivation.
     * Coverage is categorical (present/draft/partial/missing), not fractional.
     *
     * @return array<string, array{present: int, draft: int, partial: int, missing: int}>
     */
    public function computeCoverageFacts(Company $company, int $tier): array
    {
        $pillars = DisclosurePillar::all();
        $coverage = [];

        foreach ($pillars as $pillar) {
            $coverage[$pillar->value] = $this->computePillarCoverage($company, $pillar->value, $tier);
        }

        return $coverage;
    }

    /**
     * Compute coverage for a single pillar.
     */
    private function computePillarCoverage(Company $company, string $pillar, int $tier): array
    {
        // Get all required modules for this pillar at this tier
        $requiredModules = DisclosureModule::query()
            ->where('category', $pillar)
            ->where('tier', '<=', $tier)
            ->where('is_required', true)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Get company's disclosures for these modules
        $disclosures = CompanyDisclosure::query()
            ->where('company_id', $company->id)
            ->whereIn('disclosure_module_id', $requiredModules)
            ->get()
            ->keyBy('disclosure_module_id');

        $present = 0;
        $draft = 0;
        $partial = 0;
        $missing = 0;

        foreach ($requiredModules as $moduleId) {
            if (!isset($disclosures[$moduleId])) {
                $missing++;
                continue;
            }

            $disclosure = $disclosures[$moduleId];

            // Classification based on STATUS only - NO PERCENTAGES
            // present: approved and visible
            // draft: actively being worked on (draft, submitted, under_review, resubmitted)
            // partial: exists but rejected/clarification_required (needs rework)
            // missing: no disclosure record exists
            if ($disclosure->status === 'approved') {
                $present++;
            } elseif (in_array($disclosure->status, ['rejected', 'clarification_required'])) {
                // Needs rework - count as partial (exists but not acceptable)
                $partial++;
            } else {
                // draft, submitted, under_review, resubmitted = actively in progress
                $draft++;
            }
        }

        // NOTE: total_required intentionally excluded - prevents percentage derivation
        return [
            'present' => $present,
            'draft' => $draft,
            'partial' => $partial,
            'missing' => $missing,
        ];
    }

    /**
     * Get complete freshness summary for company dashboard.
     *
     * @return array{
     *   pillars: array<string, array>,
     *   overall_vitality: string,
     *   coverage_summary: array,
     *   last_computed: string
     * }
     */
    public function getCompanyFreshnessSummary(Company $company, int $tier = 1): array
    {
        $pillars = [];

        foreach (DisclosurePillar::all() as $pillar) {
            $vitality = $this->computePillarVitality($company, $pillar->value);
            $coverage = $this->computePillarCoverage($company, $pillar->value, $tier);

            $pillars[$pillar->value] = [
                'label' => $pillar->label(),
                'vitality' => $vitality,
                'coverage' => $coverage,
            ];
        }

        // Determine overall vitality (worst of all pillars)
        $overallVitality = $this->determineOverallVitality($pillars);

        // Coverage summary across all pillars (no total_required - prevents percentage derivation)
        $coverageSummary = [
            'present' => 0,
            'draft' => 0,
            'partial' => 0,
            'missing' => 0,
        ];

        foreach ($pillars as $pillarData) {
            foreach (['present', 'draft', 'partial', 'missing'] as $key) {
                $coverageSummary[$key] += $pillarData['coverage'][$key];
            }
        }

        return [
            'pillars' => $pillars,
            'overall_vitality' => $overallVitality->value,
            'coverage_summary' => $coverageSummary,
            'last_computed' => now()->toIso8601String(),
        ];
    }

    /**
     * Refresh freshness states for all approved disclosures.
     * Called by scheduler daily.
     */
    public function refreshAllFreshnessStates(): void
    {
        Log::info('[FreshnessService] Starting daily freshness refresh');

        $disclosures = CompanyDisclosure::query()
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->with('disclosureModule')
            ->cursor();

        $updated = 0;
        $errors = 0;

        foreach ($disclosures as $disclosure) {
            try {
                $freshness = $this->computeArtifactFreshness($disclosure);

                $disclosure->update([
                    'freshness_state' => $freshness['state'],
                    'freshness_computed_at' => now(),
                    'days_since_approval' => $freshness['days_since_approval'],
                    'next_update_expected' => $freshness['next_update_expected'] ?? null,
                ]);

                $updated++;
            } catch (\Exception $e) {
                Log::error('[FreshnessService] Failed to refresh disclosure', [
                    'disclosure_id' => $disclosure->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        // Record vitality snapshots for all companies with approved disclosures
        $this->recordAllVitalitySnapshots();

        Log::info('[FreshnessService] Completed freshness refresh', [
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }

    /**
     * Record vitality snapshots for all companies.
     */
    private function recordAllVitalitySnapshots(): void
    {
        $companyIds = CompanyDisclosure::query()
            ->where('status', 'approved')
            ->distinct()
            ->pluck('company_id');

        foreach ($companyIds as $companyId) {
            try {
                $company = Company::find($companyId);
                if (!$company) continue;

                foreach (DisclosurePillar::all() as $pillar) {
                    $vitality = $this->computePillarVitality($company, $pillar->value);

                    PillarVitalitySnapshot::create([
                        'company_id' => $companyId,
                        'pillar' => $pillar->value,
                        'vitality_state' => $vitality['state'],
                        'current_count' => $vitality['freshness_breakdown']['current'],
                        'aging_count' => $vitality['freshness_breakdown']['aging'],
                        'stale_count' => $vitality['freshness_breakdown']['stale'],
                        'unstable_count' => $vitality['freshness_breakdown']['unstable'],
                        'total_count' => $vitality['total_artifacts'],
                        'vitality_drivers' => $vitality['drivers'],
                        'computed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[FreshnessService] Failed to record vitality snapshot', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Determine vitality state from freshness breakdown.
     */
    private function determineVitalityState(array $breakdown): PillarVitality
    {
        $stale = $breakdown['stale'];
        $unstable = $breakdown['unstable'];
        $aging = $breakdown['aging'];

        // at_risk: 2+ stale OR 2+ unstable
        if ($stale >= 2 || $unstable >= 2) {
            return PillarVitality::AT_RISK;
        }

        // needs_attention: any aging OR 1 stale/unstable
        if ($aging > 0 || $stale > 0 || $unstable > 0) {
            return PillarVitality::NEEDS_ATTENTION;
        }

        // healthy: all current
        return PillarVitality::HEALTHY;
    }

    /**
     * Determine overall vitality (worst of all pillars).
     */
    private function determineOverallVitality(array $pillars): PillarVitality
    {
        $hasAtRisk = false;
        $hasNeedsAttention = false;

        foreach ($pillars as $pillarData) {
            $state = $pillarData['vitality']['state'];
            if ($state === PillarVitality::AT_RISK->value) {
                $hasAtRisk = true;
            } elseif ($state === PillarVitality::NEEDS_ATTENTION->value) {
                $hasNeedsAttention = true;
            }
        }

        if ($hasAtRisk) {
            return PillarVitality::AT_RISK;
        }
        if ($hasNeedsAttention) {
            return PillarVitality::NEEDS_ATTENTION;
        }
        return PillarVitality::HEALTHY;
    }

    /**
     * Build pillar signal text for UI display.
     */
    private function buildPillarSignalText(PillarVitality $state, array $breakdown, array $drivers): string
    {
        if ($state === PillarVitality::HEALTHY) {
            $current = $breakdown['current'];
            return "{$current} artifact" . ($current !== 1 ? 's' : '') . " current";
        }

        $parts = [];
        if ($breakdown['stale'] > 0) {
            $parts[] = "{$breakdown['stale']} stale";
        }
        if ($breakdown['unstable'] > 0) {
            $parts[] = "{$breakdown['unstable']} unstable";
        }
        if ($breakdown['aging'] > 0) {
            $parts[] = "{$breakdown['aging']} aging";
        }

        return implode(', ', $parts);
    }

    /**
     * Build a freshness result array.
     */
    private function buildFreshnessResult(
        ?string $state,
        int $daysSinceApproval,
        string $signalText,
        string $signalTextAdmin,
        ?string $signalTextSubscriber,
        ?int $daysUntilStale = null,
        bool $isUpdateOverdue = false,
        bool $isChangeExcessive = false,
        int $updateCountInWindow = 0,
        ?string $nextUpdateExpected = null
    ): array {
        return [
            'state' => $state,
            'days_since_approval' => $daysSinceApproval,
            'days_until_stale' => $daysUntilStale,
            'is_update_overdue' => $isUpdateOverdue,
            'is_change_excessive' => $isChangeExcessive,
            'update_count_in_window' => $updateCountInWindow,
            'next_update_expected' => $nextUpdateExpected,
            'signal_text' => $signalText,
            'signal_text_admin' => $signalTextAdmin,
            'signal_text_subscriber' => $signalTextSubscriber,
        ];
    }

    /**
     * Get investor-friendly freshness signal (abstracted, non-alarming).
     */
    public function getInvestorFreshnessSignal(CompanyDisclosure $disclosure): ?string
    {
        $freshness = $this->computeArtifactFreshness($disclosure);
        return $freshness['signal_text_subscriber'];
    }
}
