<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlatformRiskFlag;
use App\Models\CompanyDisclosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 - SERVICE: RiskFlaggingService
 *
 * PURPOSE:
 * Detect concerning patterns in disclosed data and create informational flags.
 *
 * CRITICAL REGULATORY SAFEGUARDS:
 * - Flags are FACTUAL OBSERVATIONS, not predictions or recommendations
 * - Detection logic is TRANSPARENT and documented
 * - Language is NEUTRAL (e.g., "declining revenue" not "failing business")
 * - No subjective judgments (e.g., "bad investment")
 * - Investors can see HOW flags were detected
 *
 * SAFE FLAG EXAMPLES:
 * ✅ "Revenue declined in 3 consecutive quarters"
 * ✅ "Board has 0 independent directors"
 * ✅ "Operating cash flow is negative"
 * ❌ "This company will fail" (predictive)
 * ❌ "Don't invest in this company" (advisory)
 *
 * DETECTION VERSION: v1.0.0
 */
class RiskFlaggingService
{
    private const DETECTION_VERSION = 'v1.0.0';

    /**
     * Run all risk detection checks for a company
     *
     * @param Company $company
     * @return array Array of created flags
     */
    public function detectRisks(Company $company): array
    {
        DB::beginTransaction();

        try {
            $createdFlags = [];

            // Deactivate old flags (will be recreated if still relevant)
            PlatformRiskFlag::where('company_id', $company->id)
                ->where('status', 'active')
                ->update(['status' => 'superseded']);

            // Run detection checks
            $disclosures = $company->disclosures()->with('module')->get();

            // Financial flags
            $createdFlags = array_merge($createdFlags, $this->detectFinancialRisks($company, $disclosures));

            // Governance flags
            $createdFlags = array_merge($createdFlags, $this->detectGovernanceRisks($company, $disclosures));

            // Disclosure quality flags
            $createdFlags = array_merge($createdFlags, $this->detectDisclosureQualityRisks($company, $disclosures));

            // Legal/compliance flags
            $createdFlags = array_merge($createdFlags, $this->detectLegalRisks($company, $disclosures));

            DB::commit();

            Log::info('Risk detection completed', [
                'company_id' => $company->id,
                'flags_created' => count($createdFlags),
            ]);

            return $createdFlags;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Risk detection failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Detect financial-related risk flags
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function detectFinancialRisks(Company $company, $disclosures): array
    {
        $flags = [];
        $financialDisclosure = $disclosures->firstWhere('module.code', 'financial_performance');

        if (!$financialDisclosure || $financialDisclosure->status !== 'approved') {
            // Flag: Incomplete financial disclosure
            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_INCOMPLETE_FINANCIALS,
                'severity' => 'high',
                'category' => 'disclosure_quality',
                'description' => 'Company has not provided complete financial disclosure',
                'detection_logic' => 'No approved financial performance disclosure found',
                'supporting_data' => ['disclosure_status' => $financialDisclosure->status ?? 'not_submitted'],
                'investor_message' => 'Financial information is incomplete. This limits ability to assess financial health.',
                'is_visible_to_investors' => true,
            ]);

            return $flags;
        }

        $data = $financialDisclosure->disclosure_data;

        // FLAG: Negative operating cash flow
        $cashFlow = $data['cash_flow'] ?? null;
        if (isset($cashFlow['operating']) && $cashFlow['operating'] < 0) {
            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_NEGATIVE_CASH_FLOW,
                'severity' => 'high',
                'category' => 'financial',
                'description' => 'Operating cash flow is negative',
                'detection_logic' => 'Disclosed operating cash flow < 0',
                'supporting_data' => [
                    'operating_cash_flow' => $cashFlow['operating'],
                    'fiscal_year' => $data['fiscal_year'] ?? 'unknown',
                ],
                'context' => [
                    'note' => 'Negative operating cash flow means company is consuming cash in operations',
                    'investor_consideration' => 'Consider whether company has sufficient reserves',
                ],
                'disclosure_id' => $financialDisclosure->id,
                'disclosure_field_path' => 'cash_flow.operating',
                'investor_message' => 'Company disclosed negative operating cash flow for the reported period.',
            ]);
        }

        // FLAG: Negative profit margins
        $netProfit = $data['net_profit'] ?? null;
        if ($netProfit !== null && $netProfit < 0) {
            $revenue = $data['revenue']['total'] ?? 0;
            $margin = $revenue > 0 ? ($netProfit / $revenue) * 100 : null;

            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_NEGATIVE_MARGINS,
                'severity' => 'medium',
                'category' => 'financial',
                'description' => 'Company reported net loss for the period',
                'detection_logic' => 'Disclosed net_profit < 0',
                'supporting_data' => [
                    'net_profit' => $netProfit,
                    'revenue' => $revenue,
                    'net_margin_percentage' => $margin,
                    'fiscal_year' => $data['fiscal_year'] ?? 'unknown',
                ],
                'disclosure_id' => $financialDisclosure->id,
                'disclosure_field_path' => 'net_profit',
                'investor_message' => 'Company disclosed a net loss for the reported fiscal period.',
            ]);
        }

        return $flags;
    }

    /**
     * Detect governance-related risk flags
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function detectGovernanceRisks(Company $company, $disclosures): array
    {
        $flags = [];
        $governanceDisclosure = $disclosures->firstWhere('module.code', 'board_management');

        if (!$governanceDisclosure || $governanceDisclosure->status !== 'approved') {
            return [];
        }

        $data = $governanceDisclosure->disclosure_data;

        // FLAG: No independent directors
        $boardMembers = $data['board_members'] ?? [];
        $independentCount = collect($boardMembers)->where('designation', 'Independent Director')->count();

        if ($independentCount === 0 && count($boardMembers) > 0) {
            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_NO_INDEPENDENT_DIRECTORS,
                'severity' => 'medium',
                'category' => 'governance',
                'description' => 'Board has no independent directors',
                'detection_logic' => 'Count of board_members with designation "Independent Director" = 0',
                'supporting_data' => [
                    'total_board_members' => count($boardMembers),
                    'independent_directors' => $independentCount,
                    'board_composition' => collect($boardMembers)->pluck('designation')->toArray(),
                ],
                'context' => [
                    'note' => 'Independent directors provide oversight and reduce conflicts of interest',
                    'best_practice' => 'SEBI guidelines recommend at least 1/3 independent directors',
                ],
                'disclosure_id' => $governanceDisclosure->id,
                'disclosure_field_path' => 'board_members',
                'investor_message' => 'Company disclosed a board with no independent directors.',
            ]);
        }

        // FLAG: Board size below minimum
        if (count($boardMembers) < 3) {
            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_SMALL_BOARD,
                'severity' => 'low',
                'category' => 'governance',
                'description' => 'Board size is below recommended minimum',
                'detection_logic' => 'Count of board_members < 3',
                'supporting_data' => [
                    'total_board_members' => count($boardMembers),
                ],
                'disclosure_id' => $governanceDisclosure->id,
                'disclosure_field_path' => 'board_members',
                'investor_message' => 'Company disclosed a board with fewer than 3 members.',
            ]);
        }

        // FLAG: Missing governance committees
        $governance = $data['governance_practices'] ?? [];
        $hasAuditCommittee = $governance['audit_committee_exists'] ?? false;

        if (!$hasAuditCommittee) {
            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_MISSING_COMMITTEES,
                'severity' => 'low',
                'category' => 'governance',
                'description' => 'Company does not have an audit committee',
                'detection_logic' => 'governance_practices.audit_committee_exists = false',
                'supporting_data' => [
                    'audit_committee_exists' => $hasAuditCommittee,
                ],
                'context' => [
                    'note' => 'Audit committees provide oversight of financial reporting and internal controls',
                ],
                'disclosure_id' => $governanceDisclosure->id,
                'disclosure_field_path' => 'governance_practices.audit_committee_exists',
                'investor_message' => 'Company disclosed that it does not have an audit committee.',
            ]);
        }

        return $flags;
    }

    /**
     * Detect disclosure quality risk flags
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function detectDisclosureQualityRisks(Company $company, $disclosures): array
    {
        $flags = [];

        // FLAG: Missing required disclosures
        $requiredModuleCodes = ['business_model', 'financial_performance', 'risk_factors', 'board_management'];
        $approvedModuleCodes = $disclosures->where('status', 'approved')->pluck('module.code')->toArray();
        $missingModules = array_diff($requiredModuleCodes, $approvedModuleCodes);

        if (!empty($missingModules)) {
            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_INCOMPLETE_DISCLOSURE,
                'severity' => 'high',
                'category' => 'disclosure_quality',
                'description' => 'Company has not completed required disclosures',
                'detection_logic' => 'Missing approved disclosures for required modules',
                'supporting_data' => [
                    'missing_modules' => $missingModules,
                    'approved_count' => count($approvedModuleCodes),
                    'required_count' => count($requiredModuleCodes),
                ],
                'investor_message' => 'Company has not completed all required disclosure modules.',
            ]);
        }

        // FLAG: Insufficient risk disclosures
        $riskDisclosure = $disclosures->firstWhere('module.code', 'risk_factors');
        if ($riskDisclosure && $riskDisclosure->status === 'approved') {
            $data = $riskDisclosure->disclosure_data;
            $totalRisks = count($data['business_risks'] ?? []) +
                          count($data['financial_risks'] ?? []) +
                          count($data['regulatory_risks'] ?? []);

            if ($totalRisks < 5) {
                $flags[] = $this->createFlag($company, [
                    'flag_type' => PlatformRiskFlag::FLAG_MISSING_RISK_FACTORS,
                    'severity' => 'medium',
                    'category' => 'disclosure_quality',
                    'description' => 'Company disclosed fewer risk factors than typical',
                    'detection_logic' => 'Total disclosed risk factors < 5',
                    'supporting_data' => [
                        'total_risk_factors' => $totalRisks,
                        'typical_range' => '5-15 risk factors',
                    ],
                    'context' => [
                        'note' => 'Most companies disclose 5-15 material risk factors',
                        'consideration' => 'Fewer disclosures may indicate incomplete risk assessment',
                    ],
                    'disclosure_id' => $riskDisclosure->id,
                    'investor_message' => 'Company disclosed fewer risk factors than is typical for similar companies.',
                ]);
            }
        }

        return $flags;
    }

    /**
     * Detect legal/compliance risk flags
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function detectLegalRisks(Company $company, $disclosures): array
    {
        $flags = [];
        $legalDisclosure = $disclosures->firstWhere('module.code', 'legal_compliance');

        if (!$legalDisclosure || $legalDisclosure->status !== 'approved') {
            return [];
        }

        $data = $legalDisclosure->disclosure_data;

        // FLAG: Pending litigation
        $litigation = $data['pending_litigation'] ?? [];
        if (count($litigation) > 0) {
            $totalLiability = collect($litigation)->sum('potential_liability');

            $flags[] = $this->createFlag($company, [
                'flag_type' => PlatformRiskFlag::FLAG_PENDING_LITIGATION,
                'severity' => $totalLiability > 10000000 ? 'high' : 'medium',
                'category' => 'legal',
                'description' => 'Company has pending legal proceedings',
                'detection_logic' => 'Count of pending_litigation > 0',
                'supporting_data' => [
                    'litigation_count' => count($litigation),
                    'total_potential_liability' => $totalLiability,
                    'cases' => collect($litigation)->pluck('description')->toArray(),
                ],
                'disclosure_id' => $legalDisclosure->id,
                'disclosure_field_path' => 'pending_litigation',
                'investor_message' => sprintf(
                    'Company disclosed %d pending legal proceeding(s) with potential liability of ₹%s.',
                    count($litigation),
                    number_format($totalLiability)
                ),
            ]);
        }

        return $flags;
    }

    /**
     * Create a risk flag record
     *
     * @param Company $company
     * @param array $flagData
     * @return PlatformRiskFlag
     */
    private function createFlag(Company $company, array $flagData): PlatformRiskFlag
    {
        $flag = PlatformRiskFlag::create(array_merge([
            'company_id' => $company->id,
            'status' => 'active',
            'detected_at' => now(),
            'is_visible_to_investors' => true,
            'detection_version' => self::DETECTION_VERSION,
        ], $flagData));

        Log::info('Risk flag created', [
            'flag_id' => $flag->id,
            'company_id' => $company->id,
            'flag_type' => $flag->flag_type,
            'severity' => $flag->severity,
        ]);

        return $flag;
    }
}
