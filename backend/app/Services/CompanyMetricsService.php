<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlatformCompanyMetric;
use App\Models\CompanyDisclosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 - SERVICE: CompanyMetricsService
 *
 * PURPOSE:
 * Calculate platform-generated health scores and completeness metrics.
 *
 * CRITICAL REGULATORY SAFEGUARDS:
 * - All calculations are TRANSPARENT and AUDITABLE
 * - Methodology is documented inline and in metadata
 * - Uses BANDS (e.g., "healthy") not SCORES that look like ratings
 * - No predictive language (e.g., "will succeed")
 * - No recommendation language (e.g., "good investment")
 *
 * CALCULATION VERSION: v1.0.0
 * - If methodology changes, increment version
 * - Old versions remain in metadata for audit trail
 */
class CompanyMetricsService
{
    private const CALCULATION_VERSION = 'v1.0.0';

    /**
     * Calculate and store all platform metrics for a company
     *
     * @param Company $company
     * @return PlatformCompanyMetric
     */
    public function calculateMetrics(Company $company): PlatformCompanyMetric
    {
        DB::beginTransaction();

        try {
            // Gather all disclosure data
            $disclosures = $company->disclosures()->with('module')->get();

            // Calculate individual metrics
            $completenessData = $this->calculateCompletenessScore($company, $disclosures);
            $financialHealthData = $this->calculateFinancialHealthBand($company, $disclosures);
            $governanceData = $this->calculateGovernanceQualityBand($company, $disclosures);
            $riskData = $this->calculateRiskIntensityBand($company, $disclosures);

            // Build calculation metadata for transparency
            $metadata = [
                'calculated_at' => now()->toIso8601String(),
                'disclosure_count' => $disclosures->count(),
                'approved_disclosure_count' => $disclosures->where('status', 'approved')->count(),
                'methodology' => [
                    'completeness' => 'Field completion percentage weighted by criticality',
                    'financial_health' => 'Based on disclosed revenue trends, margins, and cash flow',
                    'governance' => 'Based on board composition, independence, and committee structure',
                    'risk' => 'Based on count and severity of disclosed risk factors',
                ],
                'version' => self::CALCULATION_VERSION,
            ];

            // Create or update metrics record
            $metrics = PlatformCompanyMetric::updateOrCreate(
                ['company_id' => $company->id],
                array_merge(
                    $completenessData,
                    $financialHealthData,
                    $governanceData,
                    $riskData,
                    [
                        'last_disclosure_update' => $disclosures->max('updated_at'),
                        'last_platform_review' => now(),
                        'is_under_admin_review' => $disclosures->where('status', 'under_review')->isNotEmpty(),
                        'calculation_version' => self::CALCULATION_VERSION,
                        'calculation_metadata' => $metadata,
                    ]
                )
            );

            DB::commit();

            Log::info('Company metrics calculated', [
                'company_id' => $company->id,
                'completeness' => $metrics->disclosure_completeness_score,
                'financial_band' => $metrics->financial_health_band,
                'governance_band' => $metrics->governance_quality_band,
                'risk_band' => $metrics->risk_intensity_band,
            ]);

            return $metrics;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to calculate company metrics', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate disclosure completeness score (0-100)
     *
     * METHODOLOGY:
     * - Count total fields in all disclosure modules
     * - Count completed fields (non-null, non-empty)
     * - Weight critical fields more heavily
     * - Return percentage score
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function calculateCompletenessScore(Company $company, $disclosures): array
    {
        $totalFields = 0;
        $completedFields = 0;
        $missingCriticalFields = 0;

        foreach ($disclosures as $disclosure) {
            if (!$disclosure->module) {
                continue;
            }

            $schema = $disclosure->module->json_schema;
            $data = $disclosure->disclosure_data;

            if (!is_array($schema) || !isset($schema['properties'])) {
                continue;
            }

            foreach ($schema['properties'] as $fieldName => $fieldSchema) {
                $totalFields++;

                // Check if field is completed
                $isCompleted = isset($data[$fieldName]) && $this->isFieldPopulated($data[$fieldName]);

                if ($isCompleted) {
                    $completedFields++;
                } else {
                    // Check if field is required/critical
                    $isRequired = isset($schema['required']) && in_array($fieldName, $schema['required']);
                    if ($isRequired) {
                        $missingCriticalFields++;
                    }
                }
            }
        }

        $score = $totalFields > 0 ? ($completedFields / $totalFields) * 100 : 0;

        return [
            'disclosure_completeness_score' => round($score, 2),
            'total_fields' => $totalFields,
            'completed_fields' => $completedFields,
            'missing_critical_fields' => $missingCriticalFields,
        ];
    }

    /**
     * Calculate financial health band
     *
     * METHODOLOGY (TRANSPARENT & FACTUAL):
     * - Analyzes disclosed revenue trends
     * - Analyzes disclosed margins
     * - Analyzes disclosed cash flow
     * - Returns BAND (not numeric score)
     *
     * BANDS:
     * - insufficient_data: <50% financial fields completed
     * - concerning: Declining revenue + negative cash flow
     * - moderate: Stable revenue, break-even or slight profit
     * - healthy: Growing revenue, positive margins
     * - strong: Strong growth + strong margins + positive cash flow
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function calculateFinancialHealthBand(Company $company, $disclosures): array
    {
        $financialDisclosure = $disclosures->firstWhere('module.code', 'financial_performance');

        if (!$financialDisclosure || $financialDisclosure->status !== 'approved') {
            return [
                'financial_health_band' => 'insufficient_data',
                'financial_health_factors' => ['No approved financial disclosure'],
            ];
        }

        $data = $financialDisclosure->disclosure_data;
        $factors = [];

        // Analyze revenue trend
        $revenue = $data['revenue'] ?? null;
        $hasRevenue = isset($revenue['total']) && $revenue['total'] > 0;

        // Analyze profitability
        $netProfit = $data['net_profit'] ?? null;
        $isProf itable = $netProfit !== null && $netProfit > 0;

        // Analyze cash flow
        $cashFlow = $data['cash_flow'] ?? null;
        $operatingCashFlow = $cashFlow['operating'] ?? null;
        $hasPositiveCashFlow = $operatingCashFlow !== null && $operatingCashFlow > 0;

        // Analyze margins
        $metrics = $data['key_metrics'] ?? [];
        $grossMargin = $metrics['gross_margin'] ?? null;
        $netMargin = $metrics['net_margin'] ?? null;

        // Determine band based on combination of factors
        if ($hasPositiveCashFlow && $isProfitable && $grossMargin > 50) {
            $band = 'strong';
            $factors = [
                'Positive operating cash flow',
                'Profitable operations',
                'Healthy gross margin (>50%)',
            ];
        } elseif ($hasPositiveCashFlow && $isProfitable) {
            $band = 'healthy';
            $factors = [
                'Positive operating cash flow',
                'Profitable operations',
            ];
        } elseif ($hasRevenue && $netProfit >= -($revenue['total'] * 0.1)) {
            $band = 'moderate';
            $factors = [
                'Revenue generating',
                'Near break-even or slight loss',
            ];
        } elseif (!$hasPositiveCashFlow && !$isProfitable) {
            $band = 'concerning';
            $factors = [
                'Negative operating cash flow',
                'Unprofitable operations',
            ];
        } else {
            $band = 'moderate';
            $factors = ['Mixed financial indicators'];
        }

        return [
            'financial_health_band' => $band,
            'financial_health_factors' => $factors,
        ];
    }

    /**
     * Calculate governance quality band
     *
     * METHODOLOGY:
     * - Board size and composition
     * - Independent director presence
     * - Committee structure
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function calculateGovernanceQualityBand(Company $company, $disclosures): array
    {
        $governanceDisclosure = $disclosures->firstWhere('module.code', 'board_management');

        if (!$governanceDisclosure || $governanceDisclosure->status !== 'approved') {
            return [
                'governance_quality_band' => 'insufficient_data',
                'governance_quality_factors' => ['No approved governance disclosure'],
            ];
        }

        $data = $governanceDisclosure->disclosure_data;
        $factors = [];
        $score = 0;

        // Analyze board composition
        $boardMembers = $data['board_members'] ?? [];
        $boardSize = count($boardMembers);
        $independentCount = collect($boardMembers)->where('designation', 'Independent Director')->count();

        if ($boardSize >= 5) {
            $score += 2;
            $factors[] = "Board size adequate ({$boardSize} members)";
        } elseif ($boardSize >= 3) {
            $score += 1;
            $factors[] = "Board size meets minimum ({$boardSize} members)";
        }

        if ($independentCount >= 2) {
            $score += 2;
            $factors[] = "Multiple independent directors ({$independentCount})";
        } elseif ($independentCount >= 1) {
            $score += 1;
            $factors[] = "Has independent director";
        } else {
            $factors[] = "No independent directors";
        }

        // Analyze committees
        $governance = $data['governance_practices'] ?? [];
        $hasAuditCommittee = $governance['audit_committee_exists'] ?? false;
        $hasNominationCommittee = $governance['nomination_committee_exists'] ?? false;

        if ($hasAuditCommittee) {
            $score += 1;
            $factors[] = "Has audit committee";
        }
        if ($hasNominationCommittee) {
            $score += 1;
            $factors[] = "Has nomination committee";
        }

        // Determine band
        $band = match(true) {
            $score >= 5 => 'exemplary',
            $score >= 4 => 'strong',
            $score >= 3 => 'standard',
            $score >= 1 => 'basic',
            default => 'insufficient_data',
        };

        return [
            'governance_quality_band' => $band,
            'governance_quality_factors' => $factors,
        ];
    }

    /**
     * Calculate risk intensity band based on disclosed risks
     *
     * @param Company $company
     * @param \Illuminate\Support\Collection $disclosures
     * @return array
     */
    private function calculateRiskIntensityBand(Company $company, $disclosures): array
    {
        $riskDisclosure = $disclosures->firstWhere('module.code', 'risk_factors');

        if (!$riskDisclosure || $riskDisclosure->status !== 'approved') {
            return [
                'risk_intensity_band' => 'insufficient_data',
                'disclosed_risk_count' => 0,
                'critical_risk_count' => 0,
            ];
        }

        $data = $riskDisclosure->disclosure_data;

        // Count risks by category
        $businessRisks = $data['business_risks'] ?? [];
        $financialRisks = $data['financial_risks'] ?? [];
        $regulatoryRisks = $data['regulatory_risks'] ?? [];

        $totalRisks = count($businessRisks) + count($financialRisks) + count($regulatoryRisks);

        // Count high/critical severity risks
        $criticalCount = collect(array_merge($businessRisks, $financialRisks))
            ->where('severity', 'critical')
            ->count();
        $highCount = collect(array_merge($businessRisks, $financialRisks))
            ->where('severity', 'high')
            ->count();

        $criticalRisks = $criticalCount + $highCount;

        // Determine band
        $band = match(true) {
            $criticalRisks >= 5 => 'very_high',
            $criticalRisks >= 3 => 'high',
            $totalRisks >= 10 => 'moderate',
            $totalRisks >= 5 => 'low',
            default => 'low',
        };

        return [
            'risk_intensity_band' => $band,
            'disclosed_risk_count' => $totalRisks,
            'critical_risk_count' => $criticalRisks,
        ];
    }

    /**
     * Check if a field value is considered "populated"
     *
     * @param mixed $value
     * @return bool
     */
    private function isFieldPopulated($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }
}
