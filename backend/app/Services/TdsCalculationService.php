<?php

/**
 * [P1.3 FIX]: TDS Calculation Service
 *
 * WHY: Eliminates scattered TDS calculations across the codebase.
 * Single source of truth for all tax-related calculations.
 *
 * BEFORE:
 * - BonusCalculatorService line 472: hardcoded 10%
 * - BonusCalculatorService line 530: hardcoded 10%
 * - Withdrawal service: hardcoded 1%
 *
 * AFTER:
 * - All TDS calculations go through this service
 * - Rates configured in config/tds.php
 * - Admin can modify rates without code changes
 */

namespace App\Services;

use InvalidArgumentException;

class TdsCalculationService
{
    /**
     * Valid transaction types for TDS calculation
     */
    private const VALID_TYPES = ['bonus', 'referral', 'withdrawal', 'profit_share'];

    /**
     * Calculate TDS for a given amount and transaction type.
     *
     * @param float $grossAmount The amount before TDS deduction
     * @param string $type Transaction type (bonus|referral|withdrawal|profit_share)
     * @return array ['gross' => float, 'tds' => float, 'net' => float, 'rate' => float]
     * @throws InvalidArgumentException if type is invalid
     */
    public function calculate(float $grossAmount, string $type): array
    {
        // Validate transaction type
        if (!in_array($type, self::VALID_TYPES)) {
            throw new InvalidArgumentException(
                "Invalid TDS type: {$type}. Allowed types: " . implode(', ', self::VALID_TYPES)
            );
        }

        // Get TDS rate from config
        $rate = $this->getRate($type);

        // Check exemption threshold
        $threshold = config("tds.exemption_threshold.{$type}", 0);
        if ($grossAmount <= $threshold) {
            return [
                'gross' => $grossAmount,
                'tds' => 0.0,
                'net' => $grossAmount,
                'rate' => 0.0,
                'exempt' => true,
                'reason' => "Amount below exemption threshold (₹{$threshold})"
            ];
        }

        // Calculate TDS
        $tdsAmount = ($rate / 100) * $grossAmount;

        // Apply rounding
        $tdsAmount = $this->applyRounding($tdsAmount);
        $netAmount = $this->applyRounding($grossAmount - $tdsAmount);

        return [
            'gross' => $grossAmount,
            'tds' => $tdsAmount,
            'net' => $netAmount,
            'rate' => $rate,
            'exempt' => false,
        ];
    }

    /**
     * Calculate net amount after TDS deduction.
     *
     * Convenience method for quick calculations.
     *
     * @param float $grossAmount
     * @param string $type
     * @return float Net amount after TDS
     */
    public function calculateNet(float $grossAmount, string $type): float
    {
        return $this->calculate($grossAmount, $type)['net'];
    }

    /**
     * Calculate TDS amount only.
     *
     * @param float $grossAmount
     * @param string $type
     * @return float TDS amount
     */
    public function calculateTds(float $grossAmount, string $type): float
    {
        return $this->calculate($grossAmount, $type)['tds'];
    }

    /**
     * Get TDS rate for a transaction type.
     *
     * @param string $type
     * @return float TDS rate as percentage
     */
    public function getRate(string $type): float
    {
        $rate = config("tds.rates.{$type}");

        if ($rate === null) {
            throw new InvalidArgumentException("TDS rate not configured for type: {$type}");
        }

        return (float) $rate;
    }

    /**
     * Apply rounding based on configuration.
     *
     * @param float $amount
     * @return float Rounded amount
     */
    private function applyRounding(float $amount): float
    {
        $decimals = (int) config('tds.rounding.decimals', 2);
        $mode = config('tds.rounding.mode', 'round');

        return match ($mode) {
            'floor' => floor($amount * pow(10, $decimals)) / pow(10, $decimals),
            'ceil' => ceil($amount * pow(10, $decimals)) / pow(10, $decimals),
            default => round($amount, $decimals),
        };
    }

    /**
     * Get all configured TDS rates.
     *
     * Useful for admin UI display.
     *
     * @return array
     */
    public function getAllRates(): array
    {
        return config('tds.rates', []);
    }

    /**
     * Validate and update TDS rate (for admin use).
     *
     * Note: This only validates. Actual update should modify config or database.
     *
     * @param string $type
     * @param float $newRate
     * @return bool
     * @throws InvalidArgumentException if type or rate is invalid
     */
    public function validateRate(string $type, float $newRate): bool
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new InvalidArgumentException("Invalid TDS type: {$type}");
        }

        if ($newRate < 0 || $newRate > 100) {
            throw new InvalidArgumentException("TDS rate must be between 0 and 100");
        }

        return true;
    }
}
