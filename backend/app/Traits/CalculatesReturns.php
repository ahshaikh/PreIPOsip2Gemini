<?php

namespace App\Traits;

/**
 * CalculatesReturns Trait
 * * [AUDIT FIX]: Unified source of mathematical truth.
 * * Used by both the live Bonus Engine and the Simulation Controller.
 */
trait CalculatesReturns
{
    /**
     * Calculate projected returns based on principal, rate, and tenure.
     * * Uses integer math (Paise) to prevent floating-point errors.
     */
    public function calculateProjectedBonus(int $principalPaise, float $annualRate, int $months): array
    {
        // Monthly rate calculation
        $monthlyRate = $annualRate / 12 / 100;
        
        // Simple Interest calculation: Principal * Rate * Time
        $totalInterestPaise = (int) round($principalPaise * $monthlyRate * $months);
        $maturityAmountPaise = $principalPaise + $totalInterestPaise;

        return [
            'principal' => $principalPaise / 100,
            'interest' => $totalInterestPaise / 100,
            'maturity_amount' => $maturityAmountPaise / 100,
            'monthly_payout' => ($totalInterestPaise / $months) / 100,
        ];
    }
}