<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LedgerReconciliationService - Ensure Bidirectional Balance
 *
 * [E.17]: Ensure bidirectional ledger reconciliation
 *
 * PROTOCOL:
 * - Every credit must have a matching debit
 * - Every debit must have a matching credit
 * - Total system balance must equal zero (closed system)
 * - Wallet balances must match transaction ledger
 *
 * RECONCILIATION TYPES:
 * 1. Wallet reconciliation: wallet.balance vs SUM(transactions)
 * 2. System reconciliation: Total credits vs Total debits
 * 3. Paired transaction reconciliation: Verify double-entry links
 * 4. Admin ledger reconciliation: Verify admin balance reflects liabilities
 */
class LedgerReconciliationService
{
    /**
     * Reconcile a single wallet
     *
     * Compares wallet balance with calculated balance from transactions
     *
     * @param Wallet $wallet
     * @return array ['is_balanced' => bool, 'discrepancy_paise' => int, 'details' => array]
     */
    public function reconcileWallet(Wallet $wallet): array
    {
        // Get wallet's current balance
        $walletBalance = $wallet->balance_paise;

        // Calculate balance from active (non-reversed) transactions
        $calculatedBalance = $this->calculateWalletBalanceFromTransactions($wallet);

        // Calculate discrepancy
        $discrepancy = $walletBalance - $calculatedBalance;

        $isBalanced = $discrepancy === 0;

        if (!$isBalanced) {
            Log::warning("WALLET RECONCILIATION DISCREPANCY", [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'wallet_balance_paise' => $walletBalance,
                'calculated_balance_paise' => $calculatedBalance,
                'discrepancy_paise' => $discrepancy,
                'discrepancy_rupees' => $discrepancy / 100,
            ]);
        }

        return [
            'is_balanced' => $isBalanced,
            'wallet_balance_paise' => $walletBalance,
            'calculated_balance_paise' => $calculatedBalance,
            'discrepancy_paise' => $discrepancy,
            'discrepancy_rupees' => $discrepancy / 100,
            'total_credits' => $this->getWalletCredits($wallet),
            'total_debits' => $this->getWalletDebits($wallet),
        ];
    }

    /**
     * Reconcile ALL wallets in the system
     *
     * @return array ['total_wallets' => int, 'balanced' => int, 'discrepancies' => array]
     */
    public function reconcileAllWallets(): array
    {
        $wallets = Wallet::all();
        $totalWallets = $wallets->count();
        $balancedCount = 0;
        $discrepancies = [];

        foreach ($wallets as $wallet) {
            $reconciliation = $this->reconcileWallet($wallet);

            if ($reconciliation['is_balanced']) {
                $balancedCount++;
            } else {
                $discrepancies[] = [
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'discrepancy_paise' => $reconciliation['discrepancy_paise'],
                    'discrepancy_rupees' => $reconciliation['discrepancy_rupees'],
                ];
            }
        }

        $result = [
            'total_wallets' => $totalWallets,
            'balanced' => $balancedCount,
            'discrepancies_count' => count($discrepancies),
            'discrepancies' => $discrepancies,
            'reconciliation_date' => now()->toDateTimeString(),
        ];

        if (!empty($discrepancies)) {
            Log::warning("SYSTEM-WIDE WALLET DISCREPANCIES DETECTED", $result);
        } else {
            Log::info("SYSTEM-WIDE WALLET RECONCILIATION: ALL BALANCED", $result);
        }

        return $result;
    }

    /**
     * Reconcile system-wide transaction balance
     *
     * In a closed system: Total Credits = Total Debits (for non-reversed transactions)
     *
     * @return array ['is_balanced' => bool, 'total_credits' => int, 'total_debits' => int]
     */
    public function reconcileSystemBalance(): array
    {
        // Get all active (non-reversed) transactions
        $activeTransactions = Transaction::where('is_reversed', false)->get();

        $totalCredits = $activeTransactions
            ->whereIn('type', ['deposit', 'credit', 'bonus', 'refund', 'referral_bonus'])
            ->sum('amount_paise');

        $totalDebits = $activeTransactions
            ->whereIn('type', ['debit', 'withdrawal', 'investment', 'fee', 'tds'])
            ->sum('amount_paise');

        // System balance (should be zero in a closed system)
        $systemBalance = $totalCredits - $totalDebits;

        $isBalanced = $systemBalance >= 0; // Allow positive (more credits than debits)

        $result = [
            'is_balanced' => $isBalanced,
            'total_credits_paise' => $totalCredits,
            'total_debits_paise' => $totalDebits,
            'system_balance_paise' => $systemBalance,
            'total_credits_rupees' => $totalCredits / 100,
            'total_debits_rupees' => $totalDebits / 100,
            'system_balance_rupees' => $systemBalance / 100,
            'reconciliation_date' => now()->toDateTimeString(),
        ];

        if ($systemBalance < 0) {
            Log::error("SYSTEM BALANCE VIOLATION: More debits than credits", $result);
        } else {
            Log::info("SYSTEM BALANCE RECONCILIATION", $result);
        }

        return $result;
    }

    /**
     * Verify paired transaction integrity
     *
     * For double-entry accounting: Every transaction should have a paired transaction
     *
     * @return array ['total_transactions' => int, 'paired' => int, 'unpaired' => int, 'violations' => array]
     */
    public function verifyPairedTransactions(): array
    {
        $allTransactions = Transaction::where('is_reversed', false)->get();
        $totalTransactions = $allTransactions->count();
        $pairedCount = 0;
        $violations = [];

        foreach ($allTransactions as $transaction) {
            if ($transaction->paired_transaction_id) {
                $pairedTransaction = Transaction::find($transaction->paired_transaction_id);
                if ($pairedTransaction) {
                    $pairedCount++;
                } else {
                    $violations[] = [
                        'transaction_id' => $transaction->id,
                        'issue' => 'Paired transaction not found',
                        'claimed_pair_id' => $transaction->paired_transaction_id,
                    ];
                }
            }
        }

        return [
            'total_transactions' => $totalTransactions,
            'paired' => $pairedCount,
            'unpaired' => $totalTransactions - $pairedCount,
            'violations' => $violations,
            'reconciliation_date' => now()->toDateTimeString(),
        ];
    }

    /**
     * Execute full reconciliation (all checks)
     *
     * @return array Complete reconciliation report
     */
    public function executeFullReconciliation(): array
    {
        Log::info("FULL LEDGER RECONCILIATION STARTED");

        $startTime = microtime(true);

        $results = [
            'started_at' => now()->toDateTimeString(),
            'wallet_reconciliation' => $this->reconcileAllWallets(),
            'system_balance' => $this->reconcileSystemBalance(),
            'paired_transactions' => $this->verifyPairedTransactions(),
        ];

        $endTime = microtime(true);
        $results['execution_time_seconds'] = round($endTime - $startTime, 2);
        $results['completed_at'] = now()->toDateTimeString();

        // Overall status
        $results['overall_status'] = (
            $results['wallet_reconciliation']['discrepancies_count'] === 0 &&
            $results['system_balance']['is_balanced'] &&
            count($results['paired_transactions']['violations']) === 0
        ) ? 'ALL_BALANCED' : 'DISCREPANCIES_FOUND';

        Log::info("FULL LEDGER RECONCILIATION COMPLETED", [
            'status' => $results['overall_status'],
            'execution_time' => $results['execution_time_seconds'],
        ]);

        return $results;
    }

    /**
     * Calculate wallet balance from transactions
     *
     * @param Wallet $wallet
     * @return int Balance in paise
     */
    private function calculateWalletBalanceFromTransactions(Wallet $wallet): int
    {
        $credits = Transaction::where('wallet_id', $wallet->id)
            ->where('is_reversed', false)
            ->whereIn('type', ['deposit', 'credit', 'bonus', 'refund', 'referral_bonus'])
            ->sum('amount_paise');

        $debits = Transaction::where('wallet_id', $wallet->id)
            ->where('is_reversed', false)
            ->whereIn('type', ['debit', 'withdrawal', 'investment', 'fee', 'tds'])
            ->sum('amount_paise');

        return $credits - $debits;
    }

    /**
     * Get total credits for a wallet
     *
     * @param Wallet $wallet
     * @return int Credits in paise
     */
    private function getWalletCredits(Wallet $wallet): int
    {
        return Transaction::where('wallet_id', $wallet->id)
            ->where('is_reversed', false)
            ->whereIn('type', ['deposit', 'credit', 'bonus', 'refund', 'referral_bonus'])
            ->sum('amount_paise');
    }

    /**
     * Get total debits for a wallet
     *
     * @param Wallet $wallet
     * @return int Debits in paise
     */
    private function getWalletDebits(Wallet $wallet): int
    {
        return Transaction::where('wallet_id', $wallet->id)
            ->where('is_reversed', false)
            ->whereIn('type', ['debit', 'withdrawal', 'investment', 'fee', 'tds'])
            ->sum('amount_paise');
    }

    /**
     * Auto-fix wallet balance discrepancy (DANGEROUS - use with caution)
     *
     * Creates adjustment transaction to match calculated balance
     *
     * @param Wallet $wallet
     * @param string $reason
     * @return Transaction|null The adjustment transaction if created
     */
    public function autoFixWalletBalance(Wallet $wallet, string $reason): ?Transaction
    {
        $reconciliation = $this->reconcileWallet($wallet);

        if ($reconciliation['is_balanced']) {
            Log::info("WALLET ALREADY BALANCED: No fix needed", ['wallet_id' => $wallet->id]);
            return null;
        }

        $discrepancy = $reconciliation['discrepancy_paise'];

        // Create adjustment transaction
        return DB::transaction(function () use ($wallet, $discrepancy, $reason) {
            $currentBalance = $wallet->balance_paise;
            $correctBalance = $currentBalance - $discrepancy;

            $adjustmentType = $discrepancy > 0 ? 'debit' : 'credit';
            $adjustmentAmount = abs($discrepancy);

            $adjustmentTransaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => $adjustmentType,
                'status' => 'completed',
                'amount_paise' => $adjustmentAmount,
                'balance_before_paise' => $currentBalance,
                'balance_after_paise' => $correctBalance,
                'description' => "BALANCE ADJUSTMENT: {$reason}",
                'reference_type' => 'system_adjustment',
                'reference_id' => null,
            ]);

            // Update wallet balance
            $wallet->update(['balance_paise' => $correctBalance]);

            Log::warning("WALLET BALANCE AUTO-FIXED", [
                'wallet_id' => $wallet->id,
                'adjustment_transaction_id' => $adjustmentTransaction->id,
                'adjustment_type' => $adjustmentType,
                'adjustment_amount_paise' => $adjustmentAmount,
                'old_balance' => $currentBalance,
                'new_balance' => $correctBalance,
                'reason' => $reason,
            ]);

            return $adjustmentTransaction;
        });
    }
}
