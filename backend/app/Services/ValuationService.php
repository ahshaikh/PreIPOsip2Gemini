<?php
// V-AUDIT-MODULE5-003 (Created) - Centralized Valuation Logic
// Purpose: Single source of truth for investment valuation calculations
// Eliminates logic duplication between UserInvestment model and PortfolioController

namespace App\Services;

use App\Models\UserInvestment;
use App\Models\Product;

/**
 * ValuationService - Centralized Investment Valuation Logic
 *
 * This service provides a single source of truth for calculating:
 * - Current market value of investments
 * - Profit/Loss calculations
 * - ROI percentages
 *
 * Previously, valuation logic was duplicated in:
 * 1. UserInvestment::currentValue() accessor
 * 2. PortfolioController::index() closure
 *
 * Now both use this service for consistency.
 */
class ValuationService
{
    /**
     * Calculate the current market value of an investment
     *
     * Formula: units × current_market_price (or face_value if no market price)
     *
     * @param float $units Number of units/shares allocated
     * @param Product|null $product The investment product
     * @param float|null $fallbackPrice Optional fallback price per unit
     * @return float Current market value in rupees
     */
    public function calculateCurrentValue(
        float $units,
        ?Product $product,
        ?float $fallbackPrice = null
    ): float {
        if (!$product) {
            // If no product data, use fallback or return cost basis
            return $fallbackPrice ? ($units * $fallbackPrice) : 0.00;
        }

        // Priority: current_market_price > face_value_per_unit > fallback > 0
        $currentPrice = $product->current_market_price
            ?? $product->face_value_per_unit
            ?? $fallbackPrice
            ?? 0.00;

        return $units * $currentPrice;
    }

    /**
     * Calculate the current market value from a UserInvestment model
     *
     * @param UserInvestment $investment
     * @return float Current market value
     */
    public function calculateInvestmentCurrentValue(UserInvestment $investment): float
    {
        return $this->calculateCurrentValue(
            units: (float) $investment->units_allocated,
            product: $investment->product,
            fallbackPrice: $investment->price_per_share ?? null
        );
    }

    /**
     * Calculate profit/loss for an investment
     *
     * Formula: current_value - cost_basis
     *
     * @param float $currentValue Current market value
     * @param float $costBasis Original investment amount
     * @return float Profit (positive) or Loss (negative)
     */
    public function calculateProfitLoss(float $currentValue, float $costBasis): float
    {
        return $currentValue - $costBasis;
    }

    /**
     * Calculate ROI percentage
     *
     * Formula: (profit_loss / cost_basis) × 100
     *
     * @param float $profitLoss Profit or loss amount
     * @param float $costBasis Original investment amount
     * @return float ROI percentage (e.g., 25.50 for 25.5%)
     */
    public function calculateROI(float $profitLoss, float $costBasis): float
    {
        if ($costBasis == 0) {
            return 0.00;
        }

        return ($profitLoss / $costBasis) * 100;
    }

    /**
     * Calculate complete valuation metrics for an investment
     *
     * Returns an array with all key metrics:
     * - current_value
     * - profit_loss
     * - roi_percentage
     *
     * @param float $units Number of units
     * @param float $costBasis Original investment
     * @param Product|null $product Product data
     * @param float|null $fallbackPrice Optional fallback price
     * @return array Valuation metrics
     */
    public function calculateMetrics(
        float $units,
        float $costBasis,
        ?Product $product,
        ?float $fallbackPrice = null
    ): array {
        $currentValue = $this->calculateCurrentValue($units, $product, $fallbackPrice);
        $profitLoss = $this->calculateProfitLoss($currentValue, $costBasis);
        $roiPercentage = $this->calculateROI($profitLoss, $costBasis);

        return [
            'current_value' => $currentValue,
            'profit_loss' => $profitLoss,
            'roi_percentage' => $roiPercentage,
        ];
    }

    /**
     * Get current price for a product
     *
     * Priority: current_market_price > face_value_per_unit > fallback
     *
     * @param Product|null $product
     * @param float|null $fallback
     * @return float Price per unit
     */
    public function getCurrentPrice(?Product $product, ?float $fallback = null): float
    {
        if (!$product) {
            return $fallback ?? 0.00;
        }

        return $product->current_market_price
            ?? $product->face_value_per_unit
            ?? $fallback
            ?? 0.00;
    }
}
