<?php
// V-FINAL-1730-445 (Created)

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Model; // Generic Model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * This service is the *only* class allowed to modify a wallet's balance.
 * It enforces pessimistic locking to prevent all race conditions.
 */
class WalletService
{
    /**
     * Safely deposit funds into a user's wallet.
     *
     * @param User $user
     * @param float $amount
     * @param string $type (e.g., 'bonus_credit', 'refund', 'admin_adjustment')
     * @param string $description
     * @param Model|null $reference (e.g., the BonusTransaction or Payment model)
     * @return Transaction
     * @throws \Exception
     */
    public function deposit(User $user, float $amount, string $type, string $description, ?Model $reference = null): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be positive.");
        }

        // DB::transaction ensures that if any part fails, it all rolls back.
        return DB::transaction(function () use ($user, $amount, $type, $description, $reference) {
            
            // 1. Lock the wallet row.
            // No other process can read or write to this wallet until this transaction is complete.
            $wallet = $user->wallet()->lockForUpdate()->first();

            $balance_before = $wallet->balance;

            // 2. Perform the operation
            $wallet->increment('balance', $amount);

            // 3. Create the ledger entry
            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type,
                'status' => 'completed',
                'amount' => $amount,
                'balance_before' => $balance_before,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }

    /**
     * Safely withdraw funds from a user's wallet.
     *
     * @param User $user
     * @param float $amount
     * @param string $type (e.g., 'withdrawal_request', 'admin_adjustment')
     * @param string $description
     * @param Model|null $reference (e.g., the Withdrawal or Payment model)
     * @param bool $lockBalance (Set to true for withdrawals, false for immediate debits)
     * @return Transaction
     * @throws \Exception
     */
    public function withdraw(User $user, float $amount, string $type, string $description, ?Model $reference = null, bool $lockBalance = false): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be positive.");
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $reference, $lockBalance) {
            
            // 1. Lock the wallet row.
            $wallet = $user->wallet()->lockForUpdate()->first();

            // 2. Check balance (This is now concurrency-safe)
            if ($wallet->balance < $amount) {
                throw new \Exception("Insufficient funds. Available: ₹{$wallet->balance}");
            }

            $balance_before = $wallet->balance;
            $transactionStatus = 'completed';

            // 3. Perform the operation
            if ($lockBalance) {
                // This is for a withdrawal request.
                // Move money from 'balance' to 'locked_balance'.
                $wallet->decrement('balance', $amount);
                $wallet->increment('locked_balance', $amount);
                $transactionStatus = 'pending'; // The transaction is pending until admin completes it
            } else {
                // This is an immediate debit (e.g., an admin reversal).
                $wallet->decrement('balance', $amount);
            }

            // 4. Create the ledger entry
            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type,
                'status' => $transactionStatus,
                'amount' => -$amount, // Negative
                'balance_before' => $balance_before,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }

    /**
     * Safely unlock funds from a user's wallet (reverse a pending withdrawal).
     * Moves funds from locked_balance back to available balance.
     *
     * @param User $user
     * @param float $amount
     * @param string $type (e.g., 'reversal', 'withdrawal_cancelled')
     * @param string $description
     * @param Model|null $reference (e.g., the Withdrawal model)
     * @return Transaction
     * @throws \Exception
     */
    public function unlockFunds(User $user, float $amount, string $type, string $description, ?Model $reference = null): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Unlock amount must be positive.");
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $reference) {

            // 1. Lock the wallet row.
            $wallet = $user->wallet()->lockForUpdate()->first();

            // 2. Check locked balance (This is now concurrency-safe)
            if ($wallet->locked_balance < $amount) {
                throw new \Exception("Insufficient locked funds. Locked: ₹{$wallet->locked_balance}");
            }

            $balance_before = $wallet->balance;

            // 3. Move money from 'locked_balance' back to 'balance'
            $wallet->decrement('locked_balance', $amount);
            $wallet->increment('balance', $amount);

            // 4. Create the ledger entry
            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type,
                'status' => 'completed',
                'amount' => $amount, // Positive (funds returned to available balance)
                'balance_before' => $balance_before,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }
}