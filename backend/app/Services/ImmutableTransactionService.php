<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ImmutableTransactionService - Enforce Append-Only Transactions
 *
 * [E.16]: Make all financial records append-only
 *
 * PROTOCOL:
 * - Transactions are NEVER updated or deleted
 * - Corrections happen via reversal/compensating transactions
 * - Every reversal creates TWO new transactions:
 *   1. Reversal of original (negative amount)
 *   2. New correct transaction (positive amount)
 *
 * GUARANTEE:
 * - Complete audit trail (all changes visible)
 * - Regulatory compliance (immutable history)
 * - Balance conservation (every debit has matching credit)
 */
class ImmutableTransactionService
{
    /**
     * Create a reversal transaction for an existing transaction
     *
     * PROTOCOL:
     * - Creates new transaction with opposite type/amount
     * - Marks original transaction as reversed
     * - Links reversal to original via reversed_by_transaction_id
     * - Updates wallet balance
     *
     * @param Transaction $originalTransaction
     * @param string $reason
     * @return Transaction The reversal transaction
     */
    public function reverseTransaction(Transaction $originalTransaction, string $reason): Transaction
    {
        if ($originalTransaction->is_reversed) {
            throw new \RuntimeException(
                "Transaction #{$originalTransaction->id} is already reversed. Cannot reverse again."
            );
        }

        return DB::transaction(function () use ($originalTransaction, $reason) {
            // Get wallet
            $wallet = $originalTransaction->wallet;
            $currentBalance = $wallet->balance_paise;

            // Determine reversal type (opposite of original)
            $reversalType = $this->getReversalType($originalTransaction->type);

            // Calculate new balance (undo the original transaction)
            if ($this->isCredit($originalTransaction->type)) {
                // Original was credit, reversal is debit
                $newBalance = $currentBalance - $originalTransaction->amount_paise;
            } else {
                // Original was debit, reversal is credit
                $newBalance = $currentBalance + $originalTransaction->amount_paise;
            }

            // Create reversal transaction
            $reversalTransaction = Transaction::create([
                'wallet_id' => $originalTransaction->wallet_id,
                'user_id' => $originalTransaction->user_id,
                'type' => $reversalType,
                'status' => 'completed',
                'amount_paise' => $originalTransaction->amount_paise,
                'balance_before_paise' => $currentBalance,
                'balance_after_paise' => $newBalance,
                'description' => "REVERSAL: {$originalTransaction->description} (Reason: {$reason})",
                'reference_type' => $originalTransaction->reference_type,
                'reference_id' => $originalTransaction->reference_id,
                'paired_transaction_id' => $originalTransaction->id,
            ]);

            // Update wallet balance
            $wallet->update(['balance_paise' => $newBalance]);

            // Mark original transaction as reversed
            // This is one of the ONLY allowed updates (via Observer)
            $originalTransaction->update([
                'is_reversed' => true,
                'reversed_by_transaction_id' => $reversalTransaction->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            Log::info("TRANSACTION REVERSED", [
                'original_transaction_id' => $originalTransaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'reason' => $reason,
                'amount_paise' => $originalTransaction->amount_paise,
                'old_balance' => $currentBalance,
                'new_balance' => $newBalance,
            ]);

            return $reversalTransaction;
        });
    }

    /**
     * Correct a transaction (reverse + create new correct one)
     *
     * PROTOCOL:
     * - Reverses original transaction
     * - Creates new transaction with correct values
     * - Both are linked in audit trail
     *
     * @param Transaction $originalTransaction
     * @param array $correctData
     * @param string $reason
     * @return array ['reversal' => Transaction, 'correction' => Transaction]
     */
    public function correctTransaction(
        Transaction $originalTransaction,
        array $correctData,
        string $reason
    ): array {
        return DB::transaction(function () use ($originalTransaction, $correctData, $reason) {
            // Step 1: Reverse the incorrect transaction
            $reversalTransaction = $this->reverseTransaction($originalTransaction, $reason);

            // Step 2: Create the correct transaction
            $wallet = $originalTransaction->wallet;
            $currentBalance = $wallet->balance_paise;

            // Calculate correct balance
            $isCredit = $this->isCredit($correctData['type']);
            $newBalance = $isCredit
                ? $currentBalance + $correctData['amount_paise']
                : $currentBalance - $correctData['amount_paise'];

            $correctionTransaction = Transaction::create([
                'wallet_id' => $originalTransaction->wallet_id,
                'user_id' => $originalTransaction->user_id,
                'type' => $correctData['type'],
                'status' => 'completed',
                'amount_paise' => $correctData['amount_paise'],
                'balance_before_paise' => $currentBalance,
                'balance_after_paise' => $newBalance,
                'description' => "CORRECTION: {$correctData['description']} (Original: #{$originalTransaction->id})",
                'reference_type' => $correctData['reference_type'] ?? $originalTransaction->reference_type,
                'reference_id' => $correctData['reference_id'] ?? $originalTransaction->reference_id,
                'paired_transaction_id' => $reversalTransaction->id,
            ]);

            // Update wallet balance
            $wallet->update(['balance_paise' => $newBalance]);

            Log::info("TRANSACTION CORRECTED", [
                'original_transaction_id' => $originalTransaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'correction_transaction_id' => $correctionTransaction->id,
                'reason' => $reason,
            ]);

            return [
                'reversal' => $reversalTransaction,
                'correction' => $correctionTransaction,
            ];
        });
    }

    /**
     * Get active (non-reversed) transaction balance for user
     *
     * @param User $user
     * @return int Balance in paise
     */
    public function getActiveBalance(User $user): int
    {
        $wallet = $user->wallet;
        if (!$wallet) {
            return 0;
        }

        // Calculate balance from active transactions only
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
     * Get reversal type for a transaction type
     *
     * @param string $type
     * @return string
     */
    private function getReversalType(string $type): string
    {
        $reversalMap = [
            'deposit' => 'debit',
            'credit' => 'debit',
            'bonus' => 'debit',
            'refund' => 'debit',
            'referral_bonus' => 'debit',
            'debit' => 'credit',
            'withdrawal' => 'credit',
            'investment' => 'refund',
            'fee' => 'credit',
            'tds' => 'credit',
        ];

        return $reversalMap[$type] ?? 'debit';
    }

    /**
     * Check if transaction type is a credit
     *
     * @param string $type
     * @return bool
     */
    private function isCredit(string $type): bool
    {
        return in_array($type, [
            'deposit',
            'credit',
            'bonus',
            'refund',
            'referral_bonus',
        ]);
    }

    /**
     * Verify transaction chain integrity
     *
     * Checks that all transactions in a chain are properly linked and balanced
     *
     * @param Transaction $transaction
     * @return array ['is_valid' => bool, 'violations' => array]
     */
    public function verifyTransactionChain(Transaction $transaction): array
    {
        $violations = [];

        // Check 1: If reversed, must have reversal transaction
        if ($transaction->is_reversed && !$transaction->reversed_by_transaction_id) {
            $violations[] = "Transaction marked as reversed but no reversal transaction linked";
        }

        // Check 2: Balance conservation
        if ($this->isCredit($transaction->type)) {
            $expectedBalance = $transaction->balance_before_paise + $transaction->amount_paise;
            if ($transaction->balance_after_paise !== $expectedBalance) {
                $violations[] = "Balance conservation violated for CREDIT transaction";
            }
        } else {
            $expectedBalance = $transaction->balance_before_paise - $transaction->amount_paise;
            if ($transaction->balance_after_paise !== $expectedBalance) {
                $violations[] = "Balance conservation violated for DEBIT transaction";
            }
        }

        // Check 3: Paired transaction exists if claimed
        if ($transaction->paired_transaction_id) {
            $pairedTransaction = Transaction::find($transaction->paired_transaction_id);
            if (!$pairedTransaction) {
                $violations[] = "Paired transaction #{$transaction->paired_transaction_id} not found";
            }
        }

        return [
            'is_valid' => empty($violations),
            'violations' => $violations,
        ];
    }
}
