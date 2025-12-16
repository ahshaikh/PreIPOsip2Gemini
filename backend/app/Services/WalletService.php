<?php
// V-FINAL-1730-445 (Created)
// V-AUDIT-MODULE3-003 (Fixed) - Replaced float with string for financial precision

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Model; // Generic Model
use App\Enums\TransactionType; // ADDED: Import TransactionType enum
use App\Exceptions\Financial\InsufficientBalanceException; // ADDED: Import custom exception
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WalletService - Core Financial Operations Handler
 *
 * This service is the **single source of truth** for all wallet balance modifications.
 * It implements a double-entry ledger system with pessimistic locking to ensure
 * data integrity and prevent race conditions in concurrent transactions.
 *
 * ## Architecture
 *
 * The service uses `lockForUpdate()` (SELECT FOR UPDATE) to acquire exclusive locks
 * on wallet rows during transactions. This prevents:
 * - Double-spending attacks
 * - Race conditions during concurrent deposits/withdrawals
 * - Balance inconsistencies from parallel operations
 *
 * ## Available Operations
 *
 * | Method       | Purpose                                              | Balance Effect |
 * |--------------|------------------------------------------------------|----------------|
 * | deposit()    | Add funds (bonuses, refunds, admin adjustments)      | +amount        |
 * | withdraw()   | Remove funds (immediate debit or lock for withdrawal)| -amount        |
 * | unlockFunds()| Reverse a pending withdrawal (cancelled by user)     | +amount        |
 *
 * ## Transaction Types
 *
 * Common `$type` values used throughout the system:
 * - `bonus_credit` - Bonus awarded to user wallet
 * - `refund` - Pro-rata refund for cancellation
 * - `admin_adjustment` - Manual admin balance correction
 * - `withdrawal_request` - User requested withdrawal (locks balance)
 * - `reversal` - Cancelled withdrawal, funds unlocked
 *
 * ## Usage Example
 *
 * ```php
 * // Deposit bonus to user wallet
 * $walletService->deposit($user, 500.00, 'bonus_credit', 'Monthly bonus', $bonusTransaction);
 *
 * // Withdraw with balance locking (for withdrawal requests)
 * $walletService->withdraw($user, 1000.00, 'withdrawal_request', 'Withdrawal #123', $withdrawal, true);
 *
 * // Unlock funds when withdrawal is cancelled
 * $walletService->unlockFunds($user, 1000.00, 'reversal', 'Withdrawal cancelled', $withdrawal);
 * ```
 *
 * @package App\Services
 * @see \App\Models\Wallet
 * @see \App\Models\Transaction
 */
class WalletService
{
    /**
     * Safely deposit funds into a user's wallet.
     *
     * CRITICAL FIX (V-AUDIT-MODULE3-003):
     * - Changed type hint from float to string to prevent IEEE 754 precision errors
     * - Uses string/decimal types to ensure financial precision
     * - Validation ensures proper numeric format
     *
     * @param User $user
     * @param string|float $amount - Amount as string for precision (e.g., "100.50")
     * @param string $type (e.g., 'bonus_credit', 'refund', 'admin_adjustment')
     * @param string $description
     * @param Model|null $reference (e.g., the BonusTransaction or Payment model)
     * @return Transaction
     * @throws \InvalidArgumentException
     */
    public function deposit(User $user, $amount, string $type, string $description = '', Model $reference = null): Transaction
    {
        // CRITICAL: Convert to string to prevent float precision errors
        // e.g., 0.1 + 0.2 in float = 0.30000000000000004
        $amount = (string) $amount;

        // Validate amount format and value
        if (!is_numeric($amount) || bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be a positive number.");
        }

        // DB::transaction ensures that if any part fails, it all rolls back.
        return DB::transaction(function () use ($user, $amount, $type, $description, $reference) {

            // 1. Lock the wallet row.
            // No other process can read or write to this wallet until this transaction is complete.
            $wallet = $user->wallet()->lockForUpdate()->first();

            $balance_before = $wallet->balance;

            // 2. Perform the operation
            // Laravel's increment() handles string amounts correctly at SQL level
            $wallet->increment('balance', $amount);

            // Refresh to get the updated balance
            $wallet->refresh();

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
     * CRITICAL FIX (V-AUDIT-MODULE3-003):
     * - Changed type hint from float to string to prevent IEEE 754 precision errors
     * - Throws InsufficientBalanceException for business logic errors
     * - Uses bccomp() for safe balance comparison
     *
     * @param User $user
     * @param string|float $amount - Amount as string for precision (e.g., "100.50")
     * @param string $type (e.g., 'withdrawal_request', 'admin_adjustment')
     * @param string $description
     * @param Model|null $reference (e.g., the Withdrawal or Payment model)
     * @param bool $lockBalance (Set to true for withdrawals, false for immediate debits)
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws InsufficientBalanceException
     */
    public function withdraw(User $user, $amount, string $type, string $description, ?Model $reference = null, bool $lockBalance = false): Transaction
    {
        // CRITICAL: Convert to string to prevent float precision errors
        $amount = (string) $amount;

        // Validate amount format and value
        if (!is_numeric($amount) || bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be a positive number.");
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $reference, $lockBalance) {

            // 1. Lock the wallet row.
            $wallet = $user->wallet()->lockForUpdate()->first();

            // 2. Check balance (This is now concurrency-safe)
            // CRITICAL FIX: Use bccomp() for safe string comparison
            // bccomp() returns -1 if balance < amount, 0 if equal, 1 if balance > amount
            if (bccomp($wallet->balance, $amount, 2) < 0) {
                // AUDIT FIX: Throw custom exception instead of generic Exception
                // This allows controllers to catch it specifically and return 422 status
                throw new InsufficientBalanceException(
                    (string) $wallet->balance,
                    $amount
                );
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

            // Refresh to get the updated balance
            $wallet->refresh();

            // 4. Create the ledger entry
            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type,
                'status' => $transactionStatus,
                'amount' => bcmul($amount, '-1', 2), // Negative amount using bcmath
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
     * CRITICAL FIX (V-AUDIT-MODULE3-003):
     * - Changed type hint from float to string to prevent IEEE 754 precision errors
     * - Uses bccomp() for safe balance comparison
     *
     * @param User $user
     * @param string|float $amount - Amount as string for precision (e.g., "100.50")
     * @param string $type (e.g., 'reversal', 'withdrawal_cancelled')
     * @param string $description
     * @param Model|null $reference (e.g., the Withdrawal model)
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function unlockFunds(User $user, $amount, string $type, string $description, ?Model $reference = null): Transaction
    {
        // CRITICAL: Convert to string to prevent float precision errors
        $amount = (string) $amount;

        // Validate amount format and value
        if (!is_numeric($amount) || bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException("Unlock amount must be a positive number.");
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $reference) {

            // 1. Lock the wallet row.
            $wallet = $user->wallet()->lockForUpdate()->first();

            // 2. Check locked balance (This is now concurrency-safe)
            // CRITICAL FIX: Use bccomp() for safe string comparison
            if (bccomp($wallet->locked_balance, $amount, 2) < 0) {
                throw new \Exception("Insufficient locked funds. Locked: ₹{$wallet->locked_balance}");
            }

            $balance_before = $wallet->balance;

            // 3. Move money from 'locked_balance' back to 'balance'
            $wallet->decrement('locked_balance', $amount);
            $wallet->increment('balance', $amount);

            // Refresh to get the updated balance
            $wallet->refresh();

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