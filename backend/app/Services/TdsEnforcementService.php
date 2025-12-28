<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TdsEnforcementService - Mandatory TDS Calculation & Deduction
 *
 * [F.20]: Guarantee TDS enforcement on all taxable paths
 *
 * PROTOCOL:
 * - TDS is MANDATORY on all taxable transactions
 * - NO bypass, NO optional application, NO derived recalculations
 * - TDS calculated BEFORE transaction execution
 * - TDS deducted ATOMICALLY with source transaction
 * - All TDS transactions logged for Form 26AS reporting
 *
 * TAXABLE PATHS:
 * 1. Withdrawals (above threshold)
 * 2. Profit distributions
 * 3. Referral bonuses
 * 4. Investment returns
 * 5. Interest income
 *
 * GUARANTEE:
 * - Every taxable transaction has TDS entry
 * - TDS amount cannot be bypassed or modified
 * - Complete audit trail for tax authorities
 */
class TdsEnforcementService
{
    /**
     * TDS rates (configurable but with hard limits)
     */
    private const TDS_RATE_MIN = 0; // 0%
    private const TDS_RATE_MAX = 30; // 30% hard upper limit

    /**
     * Calculate TDS for withdrawal
     *
     * PROTOCOL:
     * - TDS applies if withdrawal amount > threshold
     * - Rate based on user's PAN status
     * - If no PAN: Higher TDS rate applies
     *
     * @param User $user
     * @param float $amount Withdrawal amount in rupees
     * @return array ['tds_applicable' => bool, 'tds_amount' => float, 'net_amount' => float, 'rate' => float, 'reason' => string]
     */
    public function calculateWithdrawalTds(User $user, float $amount): array
    {
        // Get TDS threshold
        $tdsThreshold = (float) setting('tds_withdrawal_threshold', 50000);

        // Check if TDS applies
        if ($amount < $tdsThreshold) {
            return [
                'tds_applicable' => false,
                'tds_amount' => 0,
                'net_amount' => $amount,
                'gross_amount' => $amount,
                'rate' => 0,
                'reason' => "Amount below TDS threshold (₹{$tdsThreshold})",
                'threshold' => $tdsThreshold,
            ];
        }

        // Get TDS rate based on PAN status
        $tdsRate = $this->getTdsRate($user, 'withdrawal');

        // Calculate TDS amount
        $tdsAmount = $amount * ($tdsRate / 100);
        $netAmount = $amount - $tdsAmount;

        Log::info("TDS CALCULATED FOR WITHDRAWAL", [
            'user_id' => $user->id,
            'gross_amount' => $amount,
            'tds_rate' => $tdsRate,
            'tds_amount' => $tdsAmount,
            'net_amount' => $netAmount,
            'pan_status' => $user->pan_verified ? 'verified' : 'not_verified',
        ]);

        return [
            'tds_applicable' => true,
            'tds_amount' => round($tdsAmount, 2),
            'net_amount' => round($netAmount, 2),
            'gross_amount' => $amount,
            'rate' => $tdsRate,
            'reason' => "TDS deducted as per Income Tax Act",
            'pan_verified' => $user->pan_verified ?? false,
            'threshold' => $tdsThreshold,
        ];
    }

    /**
     * Calculate TDS for profit distribution
     *
     * @param User $user
     * @param float $profitAmount
     * @return array
     */
    public function calculateProfitTds(User $user, float $profitAmount): array
    {
        // Profit distributions are ALWAYS taxable (no threshold)
        $tdsRate = $this->getTdsRate($user, 'profit');

        $tdsAmount = $profitAmount * ($tdsRate / 100);
        $netAmount = $profitAmount - $tdsAmount;

        Log::info("TDS CALCULATED FOR PROFIT", [
            'user_id' => $user->id,
            'profit_amount' => $profitAmount,
            'tds_rate' => $tdsRate,
            'tds_amount' => $tdsAmount,
            'net_amount' => $netAmount,
        ]);

        return [
            'tds_applicable' => true,
            'tds_amount' => round($tdsAmount, 2),
            'net_amount' => round($netAmount, 2),
            'gross_amount' => $profitAmount,
            'rate' => $tdsRate,
            'reason' => "TDS on profit distribution",
        ];
    }

    /**
     * Calculate TDS for referral bonus
     *
     * @param User $user
     * @param float $bonusAmount
     * @return array
     */
    public function calculateReferralBonusTds(User $user, float $bonusAmount): array
    {
        // Get threshold for referral bonuses
        $tdsThreshold = (float) setting('tds_bonus_threshold', 10000);

        if ($bonusAmount < $tdsThreshold) {
            return [
                'tds_applicable' => false,
                'tds_amount' => 0,
                'net_amount' => $bonusAmount,
                'gross_amount' => $bonusAmount,
                'rate' => 0,
                'reason' => "Amount below TDS threshold",
            ];
        }

        $tdsRate = $this->getTdsRate($user, 'bonus');

        $tdsAmount = $bonusAmount * ($tdsRate / 100);
        $netAmount = $bonusAmount - $tdsAmount;

        return [
            'tds_applicable' => true,
            'tds_amount' => round($tdsAmount, 2),
            'net_amount' => round($netAmount, 2),
            'gross_amount' => $bonusAmount,
            'rate' => $tdsRate,
            'reason' => "TDS on referral bonus",
        ];
    }

    /**
     * Deduct TDS and create transaction records
     *
     * PROTOCOL:
     * - Creates TDS transaction in wallet
     * - Records in tds_deductions table for Form 26AS
     * - Updates user's TDS ledger
     * - ATOMIC: Both deductions must succeed
     *
     * @param User $user
     * @param float $grossAmount
     * @param float $tdsAmount
     * @param string $transactionType
     * @param mixed $reference
     * @return array ['tds_transaction_id' => int, 'tds_deduction_id' => int]
     */
    public function deductTds(
        User $user,
        float $grossAmount,
        float $tdsAmount,
        string $transactionType,
        $reference = null
    ): array {
        if ($tdsAmount <= 0) {
            throw new \InvalidArgumentException("TDS amount must be positive");
        }

        return DB::transaction(function () use ($user, $grossAmount, $tdsAmount, $transactionType, $reference) {
            $wallet = $user->wallet;
            $currentBalance = $wallet->balance_paise;

            // Create TDS transaction
            $tdsTransaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'tds',
                'status' => 'completed',
                'amount_paise' => $tdsAmount * 100,
                'balance_before_paise' => $currentBalance,
                'balance_after_paise' => $currentBalance - ($tdsAmount * 100),
                'description' => "TDS deducted on {$transactionType}",
                'reference_type' => get_class($reference),
                'reference_id' => $reference?->id,
                'tds_deducted' => $tdsAmount,
            ]);

            // Record in tds_deductions table (for Form 26AS reporting)
            $tdsDeduction = DB::table('tds_deductions')->insertGetId([
                'user_id' => $user->id,
                'transaction_id' => $tdsTransaction->id,
                'financial_year' => $this->getCurrentFinancialYear(),
                'transaction_type' => $transactionType,
                'gross_amount' => $grossAmount,
                'tds_amount' => $tdsAmount,
                'tds_rate' => ($tdsAmount / $grossAmount) * 100,
                'pan_number' => $user->pan_number,
                'pan_verified' => $user->pan_verified ?? false,
                'deducted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update([
                'balance_paise' => $currentBalance - ($tdsAmount * 100),
            ]);

            Log::info("TDS DEDUCTED", [
                'user_id' => $user->id,
                'transaction_type' => $transactionType,
                'gross_amount' => $grossAmount,
                'tds_amount' => $tdsAmount,
                'tds_transaction_id' => $tdsTransaction->id,
                'tds_deduction_id' => $tdsDeduction,
            ]);

            return [
                'tds_transaction_id' => $tdsTransaction->id,
                'tds_deduction_id' => $tdsDeduction,
            ];
        });
    }

    /**
     * Get TDS rate for user based on PAN status and transaction type
     *
     * PROTOCOL:
     * - Configuration sets policy rate
     * - Invariant enforces maximum rate (30%)
     * - Higher rate for non-PAN holders
     *
     * @param User $user
     * @param string $transactionType
     * @return float TDS rate as percentage
     */
    private function getTdsRate(User $user, string $transactionType): float
    {
        // Base rates from configuration
        $configuredRates = [
            'withdrawal' => (float) setting('tds_rate_withdrawal', 10),
            'profit' => (float) setting('tds_rate_profit', 10),
            'bonus' => (float) setting('tds_rate_bonus', 10),
        ];

        $baseRate = $configuredRates[$transactionType] ?? 10;

        // If PAN not verified, apply higher rate
        if (!($user->pan_verified ?? false)) {
            $baseRate = (float) setting('tds_rate_without_pan', 20);
        }

        // INVARIANT BOUND: Never exceed 30% (even if misconfigured)
        $tdsRate = min($baseRate, self::TDS_RATE_MAX);

        // INVARIANT BOUND: Never below 0%
        $tdsRate = max($tdsRate, self::TDS_RATE_MIN);

        return $tdsRate;
    }

    /**
     * Get current financial year (April to March)
     *
     * @return string Format: "2025-2026"
     */
    private function getCurrentFinancialYear(): string
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        if ($currentMonth >= 4) {
            // April onwards: Current year - Next year
            return "{$currentYear}-" . ($currentYear + 1);
        } else {
            // January-March: Previous year - Current year
            return ($currentYear - 1) . "-{$currentYear}";
        }
    }

    /**
     * Generate TDS certificate (Form 16A)
     *
     * @param User $user
     * @param string $financialYear
     * @return array TDS summary for the financial year
     */
    public function generateTdsCertificate(User $user, string $financialYear): array
    {
        $tdsDeductions = DB::table('tds_deductions')
            ->where('user_id', $user->id)
            ->where('financial_year', $financialYear)
            ->get();

        $totalGrossAmount = $tdsDeductions->sum('gross_amount');
        $totalTdsAmount = $tdsDeductions->sum('tds_amount');

        $breakdown = $tdsDeductions->groupBy('transaction_type')->map(function ($group, $type) {
            return [
                'transaction_type' => $type,
                'count' => $group->count(),
                'total_gross' => $group->sum('gross_amount'),
                'total_tds' => $group->sum('tds_amount'),
            ];
        });

        return [
            'user_id' => $user->id,
            'pan_number' => $user->pan_number,
            'financial_year' => $financialYear,
            'total_gross_amount' => $totalGrossAmount,
            'total_tds_deducted' => $totalTdsAmount,
            'breakdown' => $breakdown->values()->toArray(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Verify TDS deduction integrity
     *
     * Ensures all taxable transactions have TDS deductions
     *
     * @param User $user
     * @param string $financialYear
     * @return array ['is_compliant' => bool, 'violations' => array]
     */
    public function verifyTdsCompliance(User $user, string $financialYear): array
    {
        $violations = [];

        // Get all taxable transactions (withdrawals, profits, bonuses)
        $taxableTransactions = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['withdrawal', 'profit', 'bonus'])
            ->whereBetween('created_at', $this->getFinancialYearDates($financialYear))
            ->get();

        foreach ($taxableTransactions as $transaction) {
            // Check if TDS was deducted
            $hasTdsDeduction = DB::table('tds_deductions')
                ->where('transaction_id', $transaction->id)
                ->exists();

            if (!$hasTdsDeduction && $transaction->amount > 0) {
                $violations[] = [
                    'transaction_id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'issue' => 'Missing TDS deduction',
                ];
            }
        }

        return [
            'is_compliant' => empty($violations),
            'total_taxable_transactions' => $taxableTransactions->count(),
            'violations' => $violations,
        ];
    }

    /**
     * Get date range for financial year
     *
     * @param string $financialYear Format: "2025-2026"
     * @return array [start_date, end_date]
     */
    private function getFinancialYearDates(string $financialYear): array
    {
        [$startYear, $endYear] = explode('-', $financialYear);

        $startDate = "{$startYear}-04-01 00:00:00";
        $endDate = "{$endYear}-03-31 23:59:59";

        return [$startDate, $endDate];
    }
}
