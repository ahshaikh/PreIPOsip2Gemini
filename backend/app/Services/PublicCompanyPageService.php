<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 5 - Issue 1: Public Company Pages Service
 *
 * PURPOSE:
 * Generate investor-facing company pages with strict data controls.
 * Show ONLY approved data. Hide everything else.
 *
 * DEFENSIVE PRINCIPLES:
 * - Default to HIDING data, not showing
 * - Mark under-review sections explicitly
 * - Display platform context warnings prominently
 * - Never assume investor knowledge
 * - Surface all material risks upfront
 *
 * WHAT IS SHOWN:
 * - Approved disclosures only (status = 'approved')
 * - Platform context (lifecycle, risk level, restrictions)
 * - Tier completion status
 * - Material change warnings
 * - Risk flags visible to investors
 *
 * WHAT IS HIDDEN:
 * - Draft disclosures
 * - Under-review disclosures
 * - Rejected disclosures
 * - Admin internal notes
 * - Clarification discussions
 */
class PublicCompanyPageService
{
    /**
     * Get public company page data for investor
     *
     * DEFENSIVE: Returns only approved, investor-visible data.
     * Everything else explicitly marked as unavailable.
     *
     * @param int $companyId
     * @param int|null $userId Optional user ID for personalization
     * @return array Public page data
     */
    public function getPublicCompanyPage(int $companyId, ?int $userId = null): array
    {
        $company = Company::find($companyId);
        if (!$company) {
            return [
                'available' => false,
                'reason' => 'Company not found',
            ];
        }

        // Check if company is visible to public
        if (!$this->isVisibleToPublic($company)) {
            return [
                'available' => false,
                'reason' => $this->getUnavailableReason($company),
                'company_name' => $company->name,
            ];
        }

        // Get platform context (current snapshot)
        $platformContextService = new PlatformContextSnapshotService();
        $platformContext = $platformContextService->getCurrentSnapshot($companyId);

        // Get approved disclosures only
        $disclosures = $this->getApprovedDisclosures($companyId);

        // Get tier completion status
        $tierStatus = $this->getTierStatus($company);

        // Get risk flags visible to investors
        $riskFlags = $this->getPublicRiskFlags($companyId);

        // Get material change warnings
        $materialChangeWarnings = $this->getMaterialChangeWarnings($platformContext);

        // Get buying eligibility
        $buyEligibility = $this->getBuyingEligibility($company, $platformContext);

        return [
            'available' => true,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'lifecycle_state' => $company->lifecycle_state,
                'description' => $company->description ?? null,
            ],
            'platform_context' => $this->formatPlatformContext($platformContext),
            'disclosures' => $disclosures,
            'tier_status' => $tierStatus,
            'risk_flags' => $riskFlags,
            'material_change_warnings' => $materialChangeWarnings,
            'buy_eligibility' => $buyEligibility,
            'disclaimers' => $this->getDisclaim(),
        ];
    }

    /**
     * Check if company is visible to public
     *
     * DEFENSIVE: Default to false unless explicitly public.
     */
    protected function isVisibleToPublic(Company $company): bool
    {
        // Must be in a public lifecycle state
        $publicStates = ['live_limited', 'live_investable', 'live_fully_disclosed'];
        if (!in_array($company->lifecycle_state, $publicStates)) {
            return false;
        }

        // Must have Tier 1 approved
        if (!$company->tier_1_approved_at) {
            return false;
        }

        // Must not be suspended
        if ($company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false)) {
            return false;
        }

        return true;
    }

    /**
     * Get reason why company is unavailable
     */
    protected function getUnavailableReason(Company $company): string
    {
        if ($company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false)) {
            return 'This company is currently suspended and not available for investment.';
        }

        if ($company->lifecycle_state === 'draft') {
            return 'This company is not yet available for public viewing.';
        }

        if (!$company->tier_1_approved_at) {
            return 'This company has not completed required disclosures for public listing.';
        }

        return 'This company is currently unavailable.';
    }

    /**
     * Get approved disclosures only
     *
     * DEFENSIVE: Show ONLY approved disclosures.
     * Mark missing/under-review sections explicitly.
     */
    protected function getApprovedDisclosures(int $companyId): array
    {
        // Get all disclosure modules
        $modules = DB::table('disclosure_modules')
            ->where('is_active', true)
            ->orderBy('tier', 'asc')
            ->orderBy('display_order', 'asc')
            ->get();

        $disclosures = [];

        foreach ($modules as $module) {
            // Get disclosure for this module
            $disclosure = DB::table('company_disclosures')
                ->where('company_id', $companyId)
                ->where('disclosure_module_id', $module->id)
                ->first();

            if ($disclosure && $disclosure->status === 'approved') {
                // APPROVED: Show data
                $disclosures[] = [
                    'module_code' => $module->code,
                    'module_name' => $module->name,
                    'tier' => $module->tier,
                    'status' => 'approved',
                    'data' => json_decode($disclosure->disclosure_data, true),
                    'approved_at' => $disclosure->approved_at,
                    'version_number' => $disclosure->version_number,
                ];
            } elseif ($disclosure && $disclosure->status === 'under_review') {
                // UNDER REVIEW: Mark explicitly
                $disclosures[] = [
                    'module_code' => $module->code,
                    'module_name' => $module->name,
                    'tier' => $module->tier,
                    'status' => 'under_review',
                    'data' => null,  // Do NOT show data
                    'message' => 'This disclosure is currently under platform review.',
                    'is_required' => $module->is_required,
                ];
            } else {
                // NOT STARTED or other: Mark as missing
                $disclosures[] = [
                    'module_code' => $module->code,
                    'module_name' => $module->name,
                    'tier' => $module->tier,
                    'status' => 'not_available',
                    'data' => null,
                    'message' => 'This disclosure is not yet available.',
                    'is_required' => $module->is_required,
                ];
            }
        }

        return $disclosures;
    }

    /**
     * Get tier completion status
     */
    protected function getTierStatus(Company $company): array
    {
        return [
            'tier_1' => [
                'approved' => $company->tier_1_approved_at !== null,
                'approved_at' => $company->tier_1_approved_at,
                'label' => 'Basic Information',
                'description' => 'Essential company information and structure',
            ],
            'tier_2' => [
                'approved' => $company->tier_2_approved_at !== null,
                'approved_at' => $company->tier_2_approved_at,
                'label' => 'Financial & Offering',
                'description' => 'Financial data and investment offering details',
                'required_for_buying' => true,
            ],
            'tier_3' => [
                'approved' => $company->tier_3_approved_at !== null,
                'approved_at' => $company->tier_3_approved_at,
                'label' => 'Advanced Disclosures',
                'description' => 'Additional transparency and detailed information',
            ],
        ];
    }

    /**
     * Get risk flags visible to investors
     *
     * DEFENSIVE: Show ALL investor-visible risk flags prominently.
     */
    protected function getPublicRiskFlags(int $companyId): array
    {
        $flags = DB::table('platform_risk_flags')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('is_visible_to_investors', true)
            ->orderBy('severity', 'desc')
            ->orderBy('detected_at', 'desc')
            ->get();

        return $flags->map(function ($flag) {
            return [
                'flag_type' => $flag->flag_type,
                'severity' => $flag->severity,  // critical, high, medium, low
                'category' => $flag->category,
                'title' => $flag->title,
                'description' => $flag->description,
                'detected_at' => $flag->detected_at,
                'is_material' => $flag->is_material ?? false,
            ];
        })->toArray();
    }

    /**
     * Get material change warnings from platform context
     */
    protected function getMaterialChangeWarnings(?object $platformContext): array
    {
        if (!$platformContext || !$platformContext->has_material_changes) {
            return [];
        }

        $changes = json_decode($platformContext->material_changes_summary, true) ?? [];

        return array_map(function ($change) {
            return [
                'type' => $change['type'] ?? 'unknown',
                'severity' => $change['severity'] ?? 'medium',
                'description' => $change['description'] ?? 'Material change detected',
                'detected_at' => $change['detected_at'] ?? null,
                'requires_acknowledgement' => true,
            ];
        }, $changes);
    }

    /**
     * Get buying eligibility
     *
     * DEFENSIVE: Default to not eligible. Must explicitly satisfy all criteria.
     */
    protected function getBuyingEligibility(Company $company, ?object $platformContext): array
    {
        $blockers = [];

        // CHECK 1: Tier 2 must be approved
        if (!$company->tier_2_approved_at) {
            $blockers[] = [
                'rule' => 'tier_2_required',
                'severity' => 'critical',
                'message' => 'Tier 2 disclosures must be approved before buying is enabled',
            ];
        }

        // CHECK 2: Buying must be enabled
        if (!($company->buying_enabled ?? true)) {
            $blockers[] = [
                'rule' => 'buying_disabled',
                'severity' => 'critical',
                'message' => 'Buying is currently disabled for this company',
                'reason' => $company->buying_pause_reason ?? 'Platform restriction',
            ];
        }

        // CHECK 3: Company must not be suspended
        if ($company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false)) {
            $blockers[] = [
                'rule' => 'company_suspended',
                'severity' => 'critical',
                'message' => 'Company is suspended - buying not allowed',
            ];
        }

        // CHECK 4: Company must not be frozen
        if ($company->disclosure_freeze ?? false) {
            $blockers[] = [
                'rule' => 'company_frozen',
                'severity' => 'critical',
                'message' => 'Company disclosures are frozen - buying not allowed',
            ];
        }

        // CHECK 5: Material changes require acknowledgement
        $requiresAcknowledgement = $platformContext && $platformContext->has_material_changes;

        $eligible = empty($blockers);

        return [
            'eligible' => $eligible,
            'blockers' => $blockers,
            'requires_acknowledgement' => $requiresAcknowledgement,
            'acknowledgement_types' => $this->getRequiredAcknowledgements($company, $requiresAcknowledgement),
        ];
    }

    /**
     * Get required acknowledgements
     */
    protected function getRequiredAcknowledgements(Company $company, bool $hasMaterialChanges): array
    {
        $acknowledgements = [
            'illiquidity' => [
                'required' => true,
                'title' => 'Illiquidity Risk',
                'description' => 'I understand that Pre-IPO investments are illiquid and I may not be able to sell my shares for an extended period, potentially years.',
            ],
            'no_guarantee' => [
                'required' => true,
                'title' => 'No Guarantee of Returns',
                'description' => 'I understand that there is no guarantee of returns and I may lose my entire investment.',
            ],
            'platform_non_advisory' => [
                'required' => true,
                'title' => 'Platform Non-Advisory',
                'description' => 'I understand that the platform does not provide investment advice and I am making my own independent investment decision.',
            ],
        ];

        if ($hasMaterialChanges) {
            $acknowledgements['material_changes'] = [
                'required' => true,
                'title' => 'Material Changes',
                'description' => 'I acknowledge that material changes have been detected in this company\'s disclosures or platform context and I have reviewed these changes.',
            ];
        }

        return $acknowledgements;
    }

    /**
     * Format platform context for public display
     */
    protected function formatPlatformContext(?object $platformContext): array
    {
        if (!$platformContext) {
            return [
                'available' => false,
                'message' => 'Platform context not available',
            ];
        }

        return [
            'available' => true,
            'snapshot_at' => $platformContext->snapshot_at,
            'lifecycle_state' => $platformContext->lifecycle_state,
            'buying_enabled' => $platformContext->buying_enabled,
            'risk_assessment' => [
                'risk_score' => $platformContext->platform_risk_score,
                'risk_level' => $platformContext->risk_level,
                'risk_level_label' => $this->getRiskLevelLabel($platformContext->risk_level),
            ],
            'restrictions' => [
                'is_suspended' => $platformContext->is_suspended,
                'is_frozen' => $platformContext->is_frozen,
                'is_under_investigation' => $platformContext->is_under_investigation,
            ],
            'compliance_score' => $platformContext->compliance_score,
            'snapshot_id' => $platformContext->id,
        ];
    }

    /**
     * Get risk level label for display
     */
    protected function getRiskLevelLabel(string $riskLevel): string
    {
        return match($riskLevel) {
            'critical' => 'Critical Risk',
            'high' => 'High Risk',
            'medium' => 'Medium Risk',
            'low' => 'Low Risk',
            default => 'Unknown Risk',
        };
    }

    /**
     * Get disclaimers for public page
     */
    protected function getDisclaimers(): array
    {
        return [
            'investment_risk' => 'Investing in Pre-IPO companies involves significant risk. You may lose your entire investment.',
            'illiquidity' => 'Pre-IPO shares are highly illiquid. You may not be able to sell your shares for an extended period.',
            'platform_role' => 'This platform facilitates investments but does not provide investment advice. Consult your financial advisor before investing.',
            'disclosure_completeness' => 'Disclosures are provided by the company and reviewed by the platform. The platform does not guarantee accuracy or completeness.',
            'regulatory' => 'This investment is subject to regulatory restrictions. Ensure you meet all eligibility requirements before investing.',
        ];
    }
}
