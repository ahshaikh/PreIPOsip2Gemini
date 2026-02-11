<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 4 - MODEL: PlatformValuationContext
 * 
 * PURPOSE:
 * Provides peer comparison context to help investors understand valuation positioning.
 * 
 * CRITICAL REGULATORY SAFEGUARDS:
 * - This is COMPARATIVE DATA, not investment advice or recommendations
 * - Platform does NOT say "undervalued" or "overvalued" (subjective judgments)
 * - Platform DOES say "below peers" or "above peers" (objective comparison)
 * - No predictive language (e.g., "will appreciate" or "likely to grow")
 * - Methodology is transparent and documented
 * 
 * SAFE LANGUAGE EXAMPLES:
 * ✅ "Company valuation is at 5x revenue, peers median is 3x revenue"
 * ✅ "Trading at premium to peer group based on disclosed valuations"
 * ✅ "Liquidity appears limited based on recent transaction volume"
 * ❌ "This company is undervalued and a good buy" (recommendation)
 * ❌ "Price will likely increase" (prediction)
 *
 * @mixin IdeHelperPlatformValuationContext
 */
class PlatformValuationContext extends Model
{
    use HasFactory;

    protected $table = 'platform_valuation_context';

    protected $fillable = [
        'company_id',
        'peer_group_name',
        'peer_company_ids',
        'peer_count',
        'peer_selection_criteria',
        'company_valuation',
        'peer_median_valuation',
        'peer_p25_valuation',
        'peer_p75_valuation',
        'company_revenue_multiple',
        'peer_median_revenue_multiple',
        'company_revenue_growth_rate',
        'peer_median_revenue_growth',
        'liquidity_outlook',
        'liquidity_factors',
        'recent_transaction_count',
        'recent_avg_transaction_size',
        'bid_ask_spread_percentage',
        'calculated_at',
        'data_as_of',
        'is_stale',
        'calculation_version',
        'methodology_notes',
        'data_sources',
    ];

    protected $casts = [
        'peer_company_ids' => 'array',
        'peer_count' => 'integer',
        'company_valuation' => 'decimal:2',
        'peer_median_valuation' => 'decimal:2',
        'peer_p25_valuation' => 'decimal:2',
        'peer_p75_valuation' => 'decimal:2',
        'company_revenue_multiple' => 'decimal:2',
        'peer_median_revenue_multiple' => 'decimal:2',
        'company_revenue_growth_rate' => 'decimal:2',
        'peer_median_revenue_growth' => 'decimal:2',
        'liquidity_factors' => 'array',
        'recent_transaction_count' => 'integer',
        'recent_avg_transaction_size' => 'decimal:2',
        'bid_ask_spread_percentage' => 'decimal:2',
        'calculated_at' => 'datetime',
        'data_as_of' => 'datetime',
        'is_stale' => 'boolean',
        'methodology_notes' => 'array',
        'data_sources' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // =========================================================================
    // ACCESSOR METHODS
    // =========================================================================

    /**
     * Get investor-friendly liquidity description
     *
     * REGULATORY NOTE: Descriptive, not predictive
     */
    public function getLiquidityDescription(): string
    {
        return match($this->liquidity_outlook) {
            'insufficient_data' => 'Insufficient transaction data to assess liquidity',
            'limited_market' => 'Limited recent market activity observed',
            'developing_market' => 'Some market activity with moderate transaction frequency',
            'active_market' => 'Regular market activity with consistent transaction volume',
            'liquid_market' => 'High market activity with frequent transactions',
            default => 'Liquidity information not available',
        };
    }

    /**
     * Get investor summary with all comparative context
     *
     * @return array
     */
    public function getInvestorSummary(): array
    {
        return [
            'peer_comparison' => [
                'peer_group' => $this->peer_group_name,
                'peer_count' => $this->peer_count,
                'selection_criteria' => $this->peer_selection_criteria,
                'valuation' => $this->company_valuation ? [
                    'company' => $this->company_valuation,
                    'peer_median' => $this->peer_median_valuation,
                    'peer_range' => [$this->peer_p25_valuation, $this->peer_p75_valuation],
                    'context' => $this->getValuationPosition(),
                ] : null,
                'revenue_multiple' => $this->company_revenue_multiple ? [
                    'company' => $this->company_revenue_multiple,
                    'peer_median' => $this->peer_median_revenue_multiple,
                ] : null,
                'growth_rate' => $this->company_revenue_growth_rate ? [
                    'company' => $this->company_revenue_growth_rate,
                    'peer_median' => $this->peer_median_revenue_growth,
                ] : null,
            ],
            'market_liquidity' => [
                'outlook' => $this->liquidity_outlook,
                'description' => $this->getLiquidityDescription(),
                'recent_transactions' => $this->recent_transaction_count,
                'avg_transaction_size' => $this->recent_avg_transaction_size,
                'bid_ask_spread' => $this->bid_ask_spread_percentage,
                'factors' => $this->liquidity_factors,
            ],
            'data_quality' => [
                'calculated_at' => $this->calculated_at,
                'data_as_of' => $this->data_as_of,
                'is_current' => !$this->is_stale,
            ],
            'methodology' => [
                'version' => $this->calculation_version,
                'notes' => $this->methodology_notes,
                'sources' => $this->data_sources,
            ],
            'disclaimer' => 'This comparative data is provided for informational purposes only and does not constitute investment advice or a recommendation. Peer selection and comparisons are based on platform methodology. Investors should conduct their own due diligence.',
        ];
    }

    /**
     * Get valuation position relative to peers
     *
     * @return string
     */
    private function getValuationPosition(): string
    {
        if (!$this->company_valuation || !$this->peer_median_valuation) {
            return 'Insufficient data for comparison';
        }

        $ratio = $this->company_valuation / $this->peer_median_valuation;

        if ($ratio < 0.8) {
            return 'Below peer median';
        } elseif ($ratio <= 1.2) {
            return 'Near peer median';
        } elseif ($ratio <= 1.5) {
            return 'Above peer median';
        } else {
            return 'At premium to peers';
        }
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeStale($query)
    {
        return $query->where('is_stale', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_stale', false);
    }
}
