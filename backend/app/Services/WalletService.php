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
use App\Models\Model;
use App\Enums\TransactionType; // [AUDIT FIX]: Use strict Enums
use App\Exceptions\Financial\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * Safely deposit funds into a user's wallet.
     * [AUDIT FIX]: Uses integer-based Paise math to eliminate float errors.
     * * @param int $amountPaise Amount in Paise (e.g., 10050 for ₹100.50)
     */
    public function deposit(User $user, int $amountPaise, TransactionType $type, string $description = '', Model $reference = null): Transaction
    {
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
     */
    public function withdraw(User $user, int $amountPaise, TransactionType $type, string $description, ?Model $reference = null, bool $lockBalance = false): Transaction
    {
        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be positive.");
        }

        return DB::transaction(function () use ($user, $amountPaise, $type, $description, $reference, $lockBalance) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            if (!$wallet || $wallet->balance_paise < $amountPaise) {
                throw new InsufficientBalanceException($wallet->balance_paise ?? 0, $amountPaise);
            }

            $balanceBefore = $wallet->balance_paise;
            $transactionStatus = 'completed';

            // [AUDIT FIX]: Atomic state transition for withdrawal requests
            if ($lockBalance) {
                $wallet->decrement('balance_paise', $amountPaise);
                $wallet->increment('locked_balance_paise', $amountPaise);
                $transactionStatus = 'pending';
            } else {
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