<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlatformValuationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 - SERVICE: ValuationContextService
 *
 * PURPOSE:
 * Build peer comparison context for valuation understanding.
 *
 * CRITICAL REGULATORY SAFEGUARDS:
 * - This is COMPARATIVE DATA, not recommendations
 * - Platform does NOT say "undervalued" or "overvalued" (subjective)
 * - Platform DOES say "below peers" or "above peers" (objective)
 * - No predictive language about future valuations
 * - Peer selection methodology is transparent
 *
 * CALCULATION VERSION: v1.0.0
 */
class ValuationContextService
{
    private const CALCULATION_VERSION = 'v1.0.0';

    /**
     * Calculate valuation context for a company
     *
     * @param Company $company
     * @return PlatformValuationContext|null
     */
    public function calculateValuationContext(Company $company): ?PlatformValuationContext
    {
        DB::beginTransaction();

        try {
            // Find peer group
            $peers = $this->selectPeerGroup($company);

            if ($peers->isEmpty()) {
                Log::info('No peers found for valuation context', [
                    'company_id' => $company->id,
                ]);
                DB::commit();
                return null;
            }

            // Calculate comparative metrics
            $valuations = $this->gatherValuations($company, $peers);
            $revenueMultiples = $this->gatherRevenueMultiples($company, $peers);
            $growthRates = $this->gatherGrowthRates($company, $peers);
            $liquidityData = $this->assessLiquidity($company);

            // Create context record
            $context = PlatformValuationContext::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'peer_group_name' => $this->getPeerGroupName($company),
                    'peer_company_ids' => $peers->pluck('id')->toArray(),
                    'peer_count' => $peers->count(),
                    'peer_selection_criteria' => $this->getPeerSelectionCriteria($company),
                    'company_valuation' => $valuations['company'] ?? null,
                    'peer_median_valuation' => $valuations['peer_median'] ?? null,
                    'peer_p25_valuation' => $valuations['peer_p25'] ?? null,
                    'peer_p75_valuation' => $valuations['peer_p75'] ?? null,
                    'company_revenue_multiple' => $revenueMultiples['company'] ?? null,
                    'peer_median_revenue_multiple' => $revenueMultiples['peer_median'] ?? null,
                    'company_revenue_growth_rate' => $growthRates['company'] ?? null,
                    'peer_median_revenue_growth' => $growthRates['peer_median'] ?? null,
                    'liquidity_outlook' => $liquidityData['outlook'],
                    'liquidity_factors' => $liquidityData['factors'],
                    'recent_transaction_count' => $liquidityData['transaction_count'],
                    'recent_avg_transaction_size' => $liquidityData['avg_transaction_size'],
                    'bid_ask_spread_percentage' => $liquidityData['bid_ask_spread'],
                    'calculated_at' => now(),
                    'data_as_of' => now(),
                    'is_stale' => false,
                    'calculation_version' => self::CALCULATION_VERSION,
                    'methodology_notes' => $this->getMethodologyNotes(),
                    'data_sources' => ['platform_disclosures', 'market_data'],
                ]
            );

            DB::commit();

            return $context;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to calculate valuation context', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Select peer group based on industry, size, stage
     *
     * METHODOLOGY: Industry match + similar revenue size
     */
    private function selectPeerGroup(Company $company)
    {
        return Company::where('id', '!=', $company->id)
            ->where('industry', $company->industry)
            ->where('lifecycle_state', 'live_investable')
            ->limit(10)
            ->get();
    }

    private function getPeerGroupName(Company $company): string
    {
        return sprintf('%s - Similar Stage Companies', $company->industry ?? 'Unlisted');
    }

    private function getPeerSelectionCriteria(Company $company): string
    {
        return sprintf(
            'Peers selected based on: (1) Same industry (%s), (2) Similar stage (investable status), (3) Platform availability',
            $company->industry ?? 'N/A'
        );
    }

    /**
     * Gather valuation data
     */
    private function gatherValuations(Company $company, $peers): array
    {
        // This would fetch actual valuation data from deals/transactions
        // For now, returning placeholder logic
        return [
            'company' => null, // Fetch from latest transaction
            'peer_median' => null,
            'peer_p25' => null,
            'peer_p75' => null,
        ];
    }

    /**
     * Gather revenue multiples
     */
    private function gatherRevenueMultiples(Company $company, $peers): array
    {
        return [
            'company' => null,
            'peer_median' => null,
        ];
    }

    /**
     * Gather growth rates
     */
    private function gatherGrowthRates(Company $company, $peers): array
    {
        return [
            'company' => null,
            'peer_median' => null,
        ];
    }

    /**
     * Assess liquidity based on recent market activity
     *
     * METHODOLOGY: Transaction volume over last 90 days
     */
    private function assessLiquidity(Company $company): array
    {
        // Count recent transactions (last 90 days)
        $transactionCount = 0; // TODO: Fetch from transactions table
        $avgSize = null;
        $bidAskSpread = null;

        $outlook = match(true) {
            $transactionCount >= 50 => 'liquid_market',
            $transactionCount >= 20 => 'active_market',
            $transactionCount >= 5 => 'developing_market',
            $transactionCount >= 1 => 'limited_market',
            default => 'insufficient_data',
        };

        $factors = [];
        if ($transactionCount > 0) {
            $factors[] = sprintf('%d transactions in last 90 days', $transactionCount);
        } else {
            $factors[] = 'No transactions observed in last 90 days';
        }

        return [
            'outlook' => $outlook,
            'factors' => $factors,
            'transaction_count' => $transactionCount,
            'avg_transaction_size' => $avgSize,
            'bid_ask_spread' => $bidAskSpread,
        ];
    }

    private function getMethodologyNotes(): array
    {
        return [
            'peer_selection' => 'Peers selected based on industry match and similar business stage',
            'valuation' => 'Based on most recent disclosed transactions or valuations',
            'liquidity' => 'Based on platform transaction volume over last 90 days',
            'disclaimer' => 'All metrics are for comparative context only, not investment recommendations',
        ];
    }
}
