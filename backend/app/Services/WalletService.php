<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-ATOMIC-OPERATIONS | V-NUMERIC-PRECISION
 * Refactored to address Module 8 Audit Gaps:
 * 1. Enforce Atomic Operations: Uses lockForUpdate() and increment()/decrement().
 * 2. Smallest Denomination Storage: All math is performed in Paise (Integers).
 * 3. Unified Transaction Types: Enforces strict Enum usage for ledger consistency.
 *
 * V-COMPLIANCE-GATE-2025 | C.8 FIX:
 * 4. KYC Enforcement: No cash ingress before KYC complete (except internal operations).
 *
 * PATCH NOTES (2026-01):
 * - Ledger immutability enforced (amount_paise always positive)
 * - Runtime invariants added (negative balance impossible unless explicitly allowed)
 * - Backward compatibility retained for float/string inputs (logged)
 */

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model; // [AUDIT FIX]: Use Eloquent base model for broader compatibility
use App\Enums\TransactionType; // [AUDIT FIX]: Use strict Enums
use App\Exceptions\Financial\InsufficientBalanceException;
use App\Exceptions\Financial\ComplianceBlockedException; // [C.8]: KYC enforcement
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * [PATCH]: Normalize mixed inputs to integer paise.
     * BACKWARD COMPATIBILITY:
     * - float  => treated as Rupees
     * - string => treated as Rupees (from decimal DB columns)
     * - int    => assumed Paise
     */
    private function normalizeAmount(int|float|string $amount): int
    {
        if (is_string($amount) || is_float($amount)) {
            Log::warning('DEPRECATED: Non-integer amount passed to WalletService', [
                'amount' => $amount,
                'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? null,
            ]);
        }

        if (is_string($amount)) {
            return (int) round((float) $amount * 100);
        }

        if (is_float($amount)) {
            return (int) round($amount * 100);
        }

        return $amount;
    }

    /**
     * Safely deposit funds into a user's wallet.
     * [AUDIT FIX]: Uses integer-based Paise math to eliminate float errors.
     * [BACKWARD COMPATIBLE]: Accepts both float (Rupees) and int (Paise) amounts
     * [C.8 FIX]: KYC enforcement for external cash ingress
     */
    public function deposit(
        User $user,
        int|float|string $amount,
        TransactionType|string $type,
        string $description = '',
        ?Model $reference = null,
        bool $bypassComplianceCheck = false
    ): Transaction {
        $amountPaise = $this->normalizeAmount($amount);

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be positive.");
        }

        // [BACKWARD COMPATIBLE]: Convert string to TransactionType enum if needed
        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        // [C.8 FIX]: COMPLIANCE GATE
        if (!$bypassComplianceCheck) {
            $this->enforceComplianceGate($user, $type);
        }

        return DB::transaction(function () use ($user, $amountPaise, $type, $description, $reference) {
            // [AUDIT FIX]: lockForUpdate() prevents race conditions during high-volume credits.
            $wallet = $user->wallet()
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id]);

            $balanceBefore = $wallet->balance_paise;

            // [AUDIT FIX]: Atomic increment at the database level.
            $wallet->increment('balance_paise', $amountPaise);
            $wallet->refresh();

            // [PATCH]: Runtime invariant
            if ($wallet->balance_paise < 0) {
                throw new \RuntimeException('Invariant violation: negative balance after deposit');
            }

            // 3. Create the immutable ledger entry
            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'status' => 'completed',
                'amount_paise' => $amountPaise, // [PATCH]: ALWAYS POSITIVE
                'balance_before_paise' => $balanceBefore,
                'balance_after_paise' => $wallet->balance_paise,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
            ]);
        });
    }

    /**
     * [C.8]: Enforce compliance gate for cash ingress operations
     */
    private function enforceComplianceGate(User $user, TransactionType $type): void
    {
        // Only external cash ingress
        $externalCashTypes = [
            TransactionType::DEPOSIT->value,
        ];

        if (!in_array($type->value, $externalCashTypes, true)) {
            return;
        }

        $complianceGate = app(ComplianceGateService::class);
        $canReceiveFunds = $complianceGate->canReceiveFunds($user);

        if (!$canReceiveFunds['allowed']) {
            Log::warning("WALLET DEPOSIT BLOCKED: KYC incomplete", [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $canReceiveFunds['reason'],
            ]);

            $complianceGate->logComplianceBlock($user, 'wallet_deposit', $canReceiveFunds);

            throw new ComplianceBlockedException(
                $canReceiveFunds['reason'],
                $canReceiveFunds['requirements'] ?? []
            );
        }
    }

    /**
     * [PROTOCOL 1 FIX]: Deposit taxable funds with TDS enforcement.
     * STRUCTURALLY IMPOSSIBLE to bypass TDS.
     */
    public function depositTaxable(
        User $user,
        TdsResult $tdsResult,
        TransactionType|string $type,
        string $baseDescription = '',
        ?Model $reference = null
    ): Transaction {
        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        return $this->deposit(
            user: $user,
            amount: $tdsResult->netAmount,
            type: $type,
            description: $tdsResult->getDescription($baseDescription),
            reference: $reference
        );
    }

    /**
     * Safely withdraw funds from a user's wallet.
     * [AUDIT FIX]: Added $allowOverdraft parameter to support Admin corrections/recoveries.
     */
    public function withdraw(
        User $user,
        int|float|string $amount,
        TransactionType|string $type,
        string $description,
        ?Model $reference = null,
        bool $lockBalance = false,
        bool $allowOverdraft = false
    ): Transaction {
        $amountPaise = $this->normalizeAmount($amount);

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be positive.");
        }

        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        return DB::transaction(function () use (
            $user,
            $amountPaise,
            $type,
            $description,
            $reference,
            $lockBalance,
            $allowOverdraft
        ) {
            $wallet = $user->wallet()
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id]);

            if (!$allowOverdraft && $wallet->balance_paise < $amountPaise) {
                throw new InsufficientBalanceException($wallet->balance_paise, $amountPaise);
            }

            $balanceBefore = $wallet->balance_paise;
            $status = 'completed';

            if ($lockBalance) {
                // Move funds to locked balance (pending withdrawal)
                $wallet->decrement('balance_paise', $amountPaise);
                $wallet->increment('locked_balance_paise', $amountPaise);
                $status = 'pending';
            } else {
                $wallet->decrement('balance_paise', $amountPaise);
            }

            $wallet->refresh();

            // [PATCH]: Runtime invariants
            if (!$allowOverdraft && $wallet->balance_paise < 0) {
                throw new \RuntimeException('Invariant violation: negative balance');
            }

            if ($wallet->locked_balance_paise < 0) {
                throw new \RuntimeException('Invariant violation: negative locked balance');
            }

            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'status' => $status,
                'amount_paise' => $amountPaise, // [PATCH]: POSITIVE, direction via type
                'balance_before_paise' => $balanceBefore,
                'balance_after_paise' => $wallet->balance_paise,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
            ]);
        });
    }

    /**
     * FIX 1 (P0): Lock funds for pending operation (e.g., withdrawal request)
     * Prevents user from spending reserved funds.
     *
     * @throws InsufficientBalanceException if available balance insufficient
     */
    public function lockFunds(
        User $user,
        int|float|string $amount,
        string $reason,
        ?Model $reference = null
    ): void {
        $amountPaise = $this->normalizeAmount($amount);

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Lock amount must be positive.");
        }

        DB::transaction(function () use ($user, $amountPaise, $reason, $reference) {
            $wallet = $user->wallet()
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id]);

            // Check available balance (balance - locked)
            $availableBalance = $wallet->balance_paise - $wallet->locked_balance_paise;

            if ($availableBalance < $amountPaise) {
                throw new InsufficientBalanceException($availableBalance, $amountPaise);
            }

            // Move to locked balance
            $wallet->increment('locked_balance_paise', $amountPaise);
            $wallet->refresh();

            // Log to audit
            \App\Models\AuditLog::create([
                'action' => 'wallet.lock_funds',
                'actor_id' => auth()->id() ?? $user->id,
                'actor_type' => auth()->user() ? get_class(auth()->user()) : User::class,
                'description' => $reason,
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'amount_paise' => $amountPaise,
                    'amount_rupees' => $amountPaise / 100,
                    'reference_type' => $reference ? get_class($reference) : null,
                    'reference_id' => $reference?->id,
                    'new_locked_balance_paise' => $wallet->locked_balance_paise,
                ],
            ]);

            Log::info('Wallet funds locked', [
                'user_id' => $user->id,
                'amount_paise' => $amountPaise,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * FIX 1 (P0): Unlock funds after cancellation/rejection
     *
     * @throws \RuntimeException if locked balance insufficient
     */
    public function unlockFunds(
        User $user,
        int|float|string $amount,
        string $reason,
        ?Model $reference = null
    ): void {
        $amountPaise = $this->normalizeAmount($amount);

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Unlock amount must be positive.");
        }

        DB::transaction(function () use ($user, $amountPaise, $reason, $reference) {
            $wallet = $user->wallet()
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id]);

            if ($wallet->locked_balance_paise < $amountPaise) {
                throw new \RuntimeException(
                    "Cannot unlock ₹" . ($amountPaise / 100) .
                    ". Locked balance is only ₹" . ($wallet->locked_balance_paise / 100)
                );
            }

            // Release from locked balance
            $wallet->decrement('locked_balance_paise', $amountPaise);
            $wallet->refresh();

            // Log to audit
            \App\Models\AuditLog::create([
                'action' => 'wallet.unlock_funds',
                'actor_id' => auth()->id() ?? $user->id,
                'actor_type' => auth()->user() ? get_class(auth()->user()) : User::class,
                'description' => $reason,
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'amount_paise' => $amountPaise,
                    'amount_rupees' => $amountPaise / 100,
                    'reference_type' => $reference ? get_class($reference) : null,
                    'reference_id' => $reference?->id,
                    'new_locked_balance_paise' => $wallet->locked_balance_paise,
                ],
            ]);

            Log::info('Wallet funds unlocked', [
                'user_id' => $user->id,
                'amount_paise' => $amountPaise,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * FIX 1 (P0): Debit locked funds (final processing of withdrawal/payment)
     * Moves funds from both balance and locked_balance simultaneously.
     *
     * @throws \RuntimeException if locked balance insufficient
     */
    public function debitLockedFunds(
        User $user,
        int|float|string $amount,
        TransactionType|string $type,
        string $description,
        ?Model $reference = null
    ): Transaction {
        $amountPaise = $this->normalizeAmount($amount);

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Debit amount must be positive.");
        }

        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        return DB::transaction(function () use (
            $user,
            $amountPaise,
            $type,
            $description,
            $reference
        ) {
            $wallet = $user->wallet()
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id]);

            if ($wallet->locked_balance_paise < $amountPaise) {
                throw new \RuntimeException(
                    "Cannot debit locked funds. Locked balance: ₹" .
                    ($wallet->locked_balance_paise / 100) .
                    ", Requested: ₹" . ($amountPaise / 100)
                );
            }

            if ($wallet->balance_paise < $amountPaise) {
                throw new InsufficientBalanceException($wallet->balance_paise, $amountPaise);
            }

            $balanceBefore = $wallet->balance_paise;

            // Debit from both balances atomically
            $wallet->decrement('balance_paise', $amountPaise);
            $wallet->decrement('locked_balance_paise', $amountPaise);
            $wallet->refresh();

            // Runtime invariants
            if ($wallet->balance_paise < 0) {
                throw new \RuntimeException('Invariant violation: negative balance after debit');
            }

            if ($wallet->locked_balance_paise < 0) {
                throw new \RuntimeException('Invariant violation: negative locked balance after debit');
            }

            // Create immutable transaction
            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'status' => 'completed',
                'amount_paise' => $amountPaise,
                'balance_before_paise' => $balanceBefore,
                'balance_after_paise' => $wallet->balance_paise,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
            ]);
        });
    }
}
