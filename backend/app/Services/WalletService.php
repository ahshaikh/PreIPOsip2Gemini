<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-ATOMIC-OPERATIONS | V-NUMERIC-PRECISION
 * Refactored to address Module 8 Audit Gaps:
 * 1. Enforce Atomic Operations: Uses lockForUpdate() and increment()/decrement().
 * 2. Smallest Denomination Storage: All math is performed in Paise (Integers).
 * 3. Unified Transaction Types: Enforces strict Enum usage for ledger consistency.
 */

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model; // [AUDIT FIX]: Use Eloquent base model for broader compatibility
use App\Enums\TransactionType; // [AUDIT FIX]: Use strict Enums
use App\Exceptions\Financial\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * Safely deposit funds into a user's wallet.
     * [AUDIT FIX]: Uses integer-based Paise math to eliminate float errors.
     * [BACKWARD COMPATIBLE]: Accepts both float (Rupees) and int (Paise) amounts
     * @param User $user
     * @param int|float $amount Amount in Paise (int) or Rupees (float)
     * @param TransactionType|string $type Transaction type (enum or string)
     * @param string $description
     * @param Model|null $reference
     * @return Transaction
     */
    public function deposit(User $user, int|float $amount, TransactionType|string $type, string $description = '', ?Model $reference = null): Transaction
    {
        // [BACKWARD COMPATIBLE]: Convert float (Rupees) to int (Paise) if needed
        $amountPaise = is_float($amount) ? (int)round($amount * 100) : $amount;

        // [BACKWARD COMPATIBLE]: Convert string to TransactionType enum if needed
        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be positive.");
        }

        return DB::transaction(function () use ($user, $amountPaise, $type, $description, $reference) {
            // [AUDIT FIX]: lockForUpdate() prevents race conditions during high-volume credits.
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate(['user_id' => $user->id]);

            $balanceBefore = $wallet->balance_paise;

            // [AUDIT FIX]: Atomic increment at the database level.
            $wallet->increment('balance_paise', $amountPaise);
            $wallet->refresh();

            // 3. Create the immutable ledger entry
            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'status' => 'completed',
                'amount_paise' => $amountPaise,
                'balance_before_paise' => $balanceBefore,
                'balance_after_paise' => $wallet->balance_paise,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }

    /**
     * Safely withdraw funds from a user's wallet.
     * [AUDIT FIX]: Added $allowOverdraft parameter to support Admin corrections/recoveries.
     * [BACKWARD COMPATIBLE]: Accepts both float (Rupees) and int (Paise) amounts
     * @param User $user
     * @param int|float $amount Amount in Paise (int) or Rupees (float)
     * @param TransactionType|string $type Transaction type (enum or string)
     * @param string $description
     * @param Model|null $reference
     * @param bool $lockBalance If true, moves funds to 'locked_balance' instead of deducting immediately.
     * @param bool $allowOverdraft [NEW] If true, allows balance to go negative (e.g., -500).
     * @return Transaction
     */
    public function withdraw(
        User $user,
        int|float $amount,
        TransactionType|string $type,
        string $description,
        ?Model $reference = null,
        bool $lockBalance = false,
        bool $allowOverdraft = false // [PROTOCOL 7]: New Argument for Admin Overrides
    ): Transaction
    {
        // [BACKWARD COMPATIBLE]: Convert float (Rupees) to int (Paise) if needed
        $amountPaise = is_float($amount) ? (int)round($amount * 100) : $amount;

        // [BACKWARD COMPATIBLE]: Convert string to TransactionType enum if needed
        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be positive.");
        }

        return DB::transaction(function () use ($user, $amountPaise, $type, $description, $reference, $lockBalance, $allowOverdraft) {
            // [AUDIT FIX]: Changed first() to firstOrCreate().
            // If a user has never had a wallet, we create one now so we can deduct from it (resulting in negative balance).
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate(['user_id' => $user->id]);

            // [AUDIT FIX]: Validation Logic
            // If overdraft is NOT allowed, we strictly check for sufficient funds.
            // If overdraft IS allowed (Admin), we skip this check.
            if (!$allowOverdraft && $wallet->balance_paise < $amountPaise) {
                throw new InsufficientBalanceException($wallet->balance_paise ?? 0, $amountPaise);
            }

            $balanceBefore = $wallet->balance_paise;
            $transactionStatus = 'completed';

            // [AUDIT FIX]: Atomic state transition for withdrawal requests
            if ($lockBalance) {
                // If locking, we move funds from available to locked.
                // Logic: If overdraft allowed, this can still go negative.
                $wallet->decrement('balance_paise', $amountPaise);
                $wallet->increment('locked_balance_paise', $amountPaise);
                $transactionStatus = 'pending';
            } else {
                // Standard withdrawal/deduction
                $wallet->decrement('balance_paise', $amountPaise);
            }

            $wallet->refresh();

            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'status' => $transactionStatus,
                'amount_paise' => -$amountPaise, // Negative for debits
                'balance_before_paise' => $balanceBefore,
                'balance_after_paise' => $wallet->balance_paise,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }
}