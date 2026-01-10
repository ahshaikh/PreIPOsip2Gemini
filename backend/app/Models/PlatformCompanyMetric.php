<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 4 - MODEL: PlatformCompanyMetric
 *
 * PURPOSE:
 * Stores platform-calculated health scores and completeness metrics.
 *
 * CRITICAL SAFEGUARDS:
 * - These metrics are PLATFORM-GENERATED, not company-editable
 * - All calculations are transparent and auditable
 * - Metrics are informational context, NOT investment recommendations
 * - Use bands (e.g., "healthy", "moderate") not precise scores that look like ratings
 *
 * REGULATORY COMPLIANCE:
 * - Clear separation: This is platform analysis, not company data
 * - Methodology is documented and accessible
 * - No predictive language (e.g., "will perform well")
 * - No recommendation language (e.g., "good investment")
 *
 * @property int $id
 * @property int $company_id
 * @property float $disclosure_completeness_score
 * @property int $total_fields
 * @property int $completed_fields
 * @property int $missing_critical_fields
 * @property string $financial_health_band
 * @property array|null $financial_health_factors
 * @property string $governance_quality_band
 * @property array|null $governance_quality_factors
 * @property string $risk_intensity_band
 * @property int $disclosed_risk_count
 * @property int $critical_risk_count
 * @property string|null $valuation_context
 * @property array|null $valuation_context_data
 * @property \Carbon\Carbon|null $last_disclosure_update
 * @property \Carbon\Carbon|null $last_platform_review
 * @property bool $is_under_admin_review
 * @property string $calculation_version
 * @property array|null $calculation_metadata
 */
class PlatformCompanyMetric extends Model
{
    use HasFactory;

    protected $table = 'platform_company_metrics';

    protected $fillable = [
        'company_id',
        'disclosure_completeness_score',
        'total_fields',
        'completed_fields',
        'missing_critical_fields',
        'financial_health_band',
        'financial_health_factors',
        'governance_quality_band',
        'governance_quality_factors',
        'risk_intensity_band',
        'disclosed_risk_count',
        'critical_risk_count',
        'valuation_context',
        'valuation_context_data',
        'last_disclosure_update',
        'last_platform_review',
        'is_under_admin_review',
        'calculation_version',
        'calculation_metadata',
    ];

    protected $casts = [
        'disclosure_completeness_score' => 'decimal:2',
        'total_fields' => 'integer',
        'completed_fields' => 'integer',
        'missing_critical_fields' => 'integer',
        'financial_health_factors' => 'array',
        'governance_quality_factors' => 'array',
        'disclosed_risk_count' => 'integer',
        'critical_risk_count' => 'integer',
        'valuation_context_data' => 'array',
        'last_disclosure_update' => 'datetime',
        'last_platform_review' => 'datetime',
        'is_under_admin_review' => 'boolean',
        'calculation_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Company these metrics belong to
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // =========================================================================
    // ACCESSOR METHODS
    // =========================================================================

    /**
     * Get investor-friendly description of financial health band
     *
     * REGULATORY NOTE: Uses neutral, descriptive language
     */
    public function getFinancialHealthDescription(): string
    {
        return match($this->financial_health_band) {
            'insufficient_data' => 'Insufficient financial data disclosed for assessment',
            'concerning' => 'Financial indicators show challenges that may warrant investor attention',
            'moderate' => 'Financial position shows standard performance for peer group',
            'healthy' => 'Financial indicators show stable performance',
            'strong' => 'Financial indicators show robust performance',
            default => 'Financial health assessment not available',
        };
    }

    /**
     * Get investor-friendly description of governance quality band
     */
    public function getGovernanceQualityDescription(): string
    {
        return match($this->governance_quality_band) {
            'insufficient_data' => 'Insufficient governance data disclosed for assessment',
            'basic' => 'Governance structure meets minimum requirements',
            'standard' => 'Governance structure aligns with industry standards',
            'strong' => 'Governance structure includes several best practices',
            'exemplary' => 'Governance structure demonstrates extensive best practices',
            default => 'Governance quality assessment not available',
        };
    }

    /**
     * Get investor-friendly description of risk intensity band
     */
    public function getRiskIntensityDescription(): string
    {
        return match($this->risk_intensity_band) {
            'insufficient_data' => 'Insufficient risk data disclosed for assessment',
            'low' => 'Disclosed risks are primarily low-severity',
            'moderate' => 'Disclosed risks include standard business risks',
            'high' => 'Disclosed risks include several material concerns',
            'very_high' => 'Disclosed risks include multiple critical factors',
            default => 'Risk intensity assessment not available',
        };
    }

    /**
     * Get investor-friendly description of valuation context
     *
     * REGULATORY NOTE: This is COMPARATIVE CONTEXT, not a recommendation
     */
    public function getValuationContextDescription(): string
    {
        if (!$this->valuation_context) {
            return 'Insufficient data for peer valuation comparison';
        }

        return match($this->valuation_context) {
            'insufficient_data' => 'Insufficient data for peer valuation comparison',
            'below_peers' => 'Current valuation is below peer group median',
            'at_peers' => 'Current valuation is near peer group median',
            'above_peers' => 'Current valuation is above peer group median',
            'premium' => 'Current valuation is at a premium to peer group',
            default => 'Valuation context not available',
        };
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if metrics are stale and need recalculation
     *
     * @param int $maxAgeHours Maximum age in hours before metrics are considered stale
     * @return bool
     */
    public function isStale(int $maxAgeHours = 24): bool
    {
        if (!$this->last_platform_review) {
            return true;
        }

        return $this->last_platform_review->diffInHours(now()) > $maxAgeHours;
    }

    /**
     * Get completeness percentage as integer
     */
    public function getCompletenessPercentage(): int
    {
        return (int) round($this->disclosure_completeness_score);
    }

    /**
     * Check if company has sufficient data for meaningful analysis
     */
    public function hasSufficientData(): bool
    {
        return $this->disclosure_completeness_score >= 50
            && $this->financial_health_band !== 'insufficient_data'
            && $this->governance_quality_band !== 'insufficient_data';
    }

    /**
     * Get investor-facing summary of all metrics
     *
     * REGULATORY NOTE: Clear labeling that this is platform analysis
     *
     * @return array
     */
    public function getInvestorSummary(): array
    {
        return [
            'platform_generated_metrics' => [ // CLEAR LABEL
                'disclosure_completeness' => [
                    'score' => $this->getCompletenessPercentage(),
                    'completed_fields' => $this->completed_fields,
                    'total_fields' => $this->total_fields,
                    'missing_critical' => $this->missing_critical_fields,
                    'description' => "Platform assessment of disclosure completeness. Higher scores indicate more comprehensive disclosures.",
                ],
                'financial_health' => [
                    'band' => $this->financial_health_band,
                    'description' => $this->getFinancialHealthDescription(),
                    'factors' => $this->financial_health_factors,
                ],
                'governance_quality' => [
                    'band' => $this->governance_quality_band,
                    'description' => $this->getGovernanceQualityDescription(),
                    'factors' => $this->governance_quality_factors,
                ],
                'risk_intensity' => [
                    'band' => $this->risk_intensity_band,
                    'description' => $this->getRiskIntensityDescription(),
                    'disclosed_risks' => $this->disclosed_risk_count,
                    'critical_risks' => $this->critical_risk_count,
                ],
                'valuation_context' => $this->valuation_context ? [
                    'context' => $this->valuation_context,
                    'description' => $this->getValuationContextDescription(),
                    'peer_data' => $this->valuation_context_data,
                ] : null,
            ],
            'data_freshness' => [
                'last_company_update' => $this->last_disclosure_update,
                'last_platform_review' => $this->last_platform_review,
                'is_under_review' => $this->is_under_admin_review,
            ],
            'methodology' => [
                'calculation_version' => $this->calculation_version,
                'metadata' => $this->calculation_metadata,
                'disclaimer' => 'These metrics are platform-generated assessments based on disclosed information. They do not constitute investment advice or recommendations. Investors should conduct their own due diligence.',
            ],
        ];
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to companies with stale metrics
     */
    public function scopeStale($query, int $maxAgeHours = 24)
    {
        return $query->where(function ($q) use ($maxAgeHours) {
            $q->whereNull('last_platform_review')
                ->orWhere('last_platform_review', '<', now()->subHours($maxAgeHours));
        });
    }

    /**
     * Scope to companies with sufficient data
     */
    public function scopeSufficientData($query)
    {
        return $query->where('disclosure_completeness_score', '>=', 50)
            ->where('financial_health_band', '!=', 'insufficient_data')
            ->where('governance_quality_band', '!=', 'insufficient_data');
    }

    /**
     * Scope to companies currently under admin review
     */
    public function scopeUnderReview($query)
    {
        return $query->where('is_under_admin_review', true);
    }
}
