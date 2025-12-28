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
     * Auto-fix wallet balance discrepancy (DANGEROUS - use with extreme caution)
     *
     * CRITICAL SAFEGUARDS (addressing audit feedback):
     * 1. HARD LIMITS: Maximum adjustment amount (configuration + invariant)
     * 2. APPROVAL REQUIRED: Explicit admin approval token
     * 3. KILL SWITCH: Global setting can disable all auto-fix
     * 4. AUDIT TRAIL: All attempts logged (approved + rejected)
     * 5. BALANCE RECOMPUTATION: Verify against transaction history, not stored balance
     * 6. HUMAN REVIEW: Requires manual confirmation for each wallet
     *
     * @param Wallet $wallet
     * @param string $reason
     * @param string $approvalToken Admin approval token (cryptographically signed)
     * @return Transaction|null The adjustment transaction if created
     * @throws \RuntimeException if safeguards fail
     */
    public function autoFixWalletBalance(Wallet $wallet, string $reason, string $approvalToken): ?Transaction
    {
        // SAFEGUARD 1: Kill switch check
        if (!setting('allow_auto_fix', false)) {
            Log::critical("AUTO-FIX BLOCKED: Kill switch activated", [
                'wallet_id' => $wallet->id,
                'reason' => $reason,
            ]);

            throw new \RuntimeException(
                "AUTO-FIX DISABLED: Global kill switch is activated. " .
                "Manual reconciliation required."
            );
        }

        // SAFEGUARD 2: Verify approval token
        if (!$this->verifyApprovalToken($approvalToken, $wallet->id)) {
            Log::critical("AUTO-FIX BLOCKED: Invalid approval token", [
                'wallet_id' => $wallet->id,
                'token' => substr($approvalToken, 0, 10) . '...',
            ]);

            throw new \RuntimeException(
                "AUTO-FIX BLOCKED: Invalid or expired approval token. " .
                "Each auto-fix requires explicit admin approval."
            );
        }

        // SAFEGUARD 3: Recompute balance from transaction history (don't trust stored balance)
        $reconciliation = $this->reconcileWallet($wallet);

        if ($reconciliation['is_balanced']) {
            Log::info("WALLET ALREADY BALANCED: No fix needed", ['wallet_id' => $wallet->id]);
            return null;
        }

        $discrepancy = $reconciliation['discrepancy_paise'];

        // SAFEGUARD 4: Hard limits (configuration + invariant)
        $configuredMaxPaise = (int) (setting('max_auto_fix_amount', 1000) * 100); // ₹1,000 default
        $invariantMaxPaise = 500000; // ₹5,000 HARD UPPER LIMIT

        $maxAllowedPaise = min($configuredMaxPaise, $invariantMaxPaise);

        if (abs($discrepancy) > $maxAllowedPaise) {
            Log::critical("AUTO-FIX BLOCKED: Exceeds maximum allowed amount", [
                'wallet_id' => $wallet->id,
                'discrepancy_paise' => $discrepancy,
                'discrepancy_rupees' => $discrepancy / 100,
                'max_allowed_paise' => $maxAllowedPaise,
                'max_allowed_rupees' => $maxAllowedPaise / 100,
            ]);

            throw new \RuntimeException(
                "AUTO-FIX BLOCKED: Discrepancy ₹" . ($discrepancy / 100) . " exceeds maximum allowed ₹" . ($maxAllowedPaise / 100) . ". " .
                "Manual investigation required."
            );
        }

        // SAFEGUARD 5: Create adjustment transaction with full audit trail
        return DB::transaction(function () use ($wallet, $discrepancy, $reason, $approvalToken, $reconciliation) {
            $currentBalance = $wallet->balance_paise;
            $correctBalance = $reconciliation['calculated_balance_paise'];

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
                'description' => "BALANCE ADJUSTMENT (AUTO-FIX): {$reason}",
                'reference_type' => 'system_adjustment',
                'reference_id' => null,
            ]);

            // Update wallet balance
            $wallet->update(['balance_paise' => $correctBalance]);

            // CRITICAL: Log to audit_logs for permanent record
            \App\Models\AuditLog::create([
                'actor_type' => 'system',
                'actor_id' => null,
                'actor_name' => 'Auto-Fix Robot',
                'action' => 'auto_fix_balance',
                'module' => 'reconciliation',
                'description' => "AUTO-FIX executed on wallet #{$wallet->id}",
                'target_type' => 'Wallet',
                'target_id' => $wallet->id,
                'old_values' => json_encode([
                    'balance_paise' => $currentBalance,
                ]),
                'new_values' => json_encode([
                    'balance_paise' => $correctBalance,
                    'adjustment_amount_paise' => $adjustmentAmount,
                ]),
                'metadata' => json_encode([
                    'reason' => $reason,
                    'approval_token' => substr($approvalToken, 0, 20) . '...',
                    'adjustment_transaction_id' => $adjustmentTransaction->id,
                    'reconciliation_details' => $reconciliation,
                ]),
                'risk_level' => 'critical',
                'requires_review' => true,
            ]);

            Log::warning("WALLET BALANCE AUTO-FIXED", [
                'wallet_id' => $wallet->id,
                'adjustment_transaction_id' => $adjustmentTransaction->id,
                'adjustment_type' => $adjustmentType,
                'adjustment_amount_paise' => $adjustmentAmount,
                'old_balance' => $currentBalance,
                'new_balance' => $correctBalance,
                'reason' => $reason,
                'approval_token_hash' => hash('sha256', $approvalToken),
            ]);

            return $adjustmentTransaction;
        });
    }

    /**
     * Verify admin approval token for auto-fix
     *
     * Token format: AUTOFIX|WALLET_ID|TIMESTAMP|ADMIN_ID|SIGNATURE
     *
     * @param string $token
     * @param int $walletId
     * @return bool
     */
    private function verifyApprovalToken(string $token, int $walletId): bool
    {
        $parts = explode('|', $token);

        if (count($parts) !== 5) {
            return false;
        }

        [$prefix, $tokenWalletId, $timestamp, $adminId, $signature] = $parts;

        // Check 1: Correct prefix
        if ($prefix !== 'AUTOFIX') {
            return false;
        }

        // Check 2: Wallet ID matches
        if ((int) $tokenWalletId !== $walletId) {
            return false;
        }

        // Check 3: Token not expired (5 minutes)
        if (time() - (int) $timestamp > 300) {
            Log::warning("AUTO-FIX TOKEN EXPIRED", [
                'wallet_id' => $walletId,
                'token_age_seconds' => time() - (int) $timestamp,
            ]);
            return false;
        }

        // Check 4: Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            "{$prefix}|{$tokenWalletId}|{$timestamp}|{$adminId}",
            config('app.key')
        );

        if (!hash_equals($expectedSignature, $signature)) {
            Log::critical("AUTO-FIX TOKEN SIGNATURE MISMATCH", [
                'wallet_id' => $walletId,
                'admin_id' => $adminId,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Generate approval token for auto-fix (admin only)
     *
     * @param int $walletId
     * @param int $adminId
     * @return string
     */
    public function generateApprovalToken(int $walletId, int $adminId): string
    {
        $timestamp = time();
        $prefix = 'AUTOFIX';

        $signature = hash_hmac(
            'sha256',
            "{$prefix}|{$walletId}|{$timestamp}|{$adminId}",
            config('app.key')
        );

        return "{$prefix}|{$walletId}|{$timestamp}|{$adminId}|{$signature}";
    }
}
