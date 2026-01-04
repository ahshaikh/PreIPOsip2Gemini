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
     * Safely deposit funds into a user's wallet.
     * [AUDIT FIX]: Uses integer-based Paise math to eliminate float errors.
     * [BACKWARD COMPATIBLE]: Accepts both float (Rupees) and int (Paise) amounts
     * [C.8 FIX]: KYC enforcement for external cash ingress
     *
     * @param User $user
     * @param int|float $amount Amount in Paise (int) or Rupees (float)
     * @param TransactionType|string $type Transaction type (enum or string)
     * @param string $description
     * @param Model|null $reference
     * @param bool $bypassComplianceCheck If true, skips KYC check (for internal operations)
     * @return Transaction
     */
    public function deposit(
        User $user,
        int|float|string $amount,
        TransactionType|string $type,
        string $description = '',
        ?Model $reference = null,
        bool $bypassComplianceCheck = false
    ): Transaction {
        // V-FIX-WALLET-NOT-REFLECTING: Handle string amounts from decimal database columns
        // Payment amounts are stored as decimal(10,2) and retrieved as strings like "1000.00"
        // Must convert string Rupees to int Paise correctly
        if (is_string($amount)) {
            // String from database decimal column - treat as Rupees, convert to Paise
            $amountPaise = (int)round((float)$amount * 100);
        } elseif (is_float($amount)) {
            // Float Rupees - convert to Paise
            $amountPaise = (int)round($amount * 100);
        } else {
            // Already in Paise (integer)
            $amountPaise = $amount;
        }

        // [BACKWARD COMPATIBLE]: Convert string to TransactionType enum if needed
        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be positive.");
        }

        // [C.8 FIX]: COMPLIANCE GATE - Block external cash ingress before KYC
        if (!$bypassComplianceCheck) {
            $this->enforceComplianceGate($user, $type);
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
     * [C.8]: Enforce compliance gate for cash ingress operations
     *
     * @param User $user
     * @param TransactionType $type
     * @return void
     * @throws ComplianceBlockedException
     */
    private function enforceComplianceGate(User $user, TransactionType $type): void
    {
        // V-FIX-WALLET-NOT-REFLECTING: Fix non-existent enum values
        // PAYMENT_RECEIVED and WALLET_DEPOSIT don't exist in TransactionType enum
        // Correct value is DEPOSIT for external cash ingress
        // Only enforce for external cash ingress (not internal operations like bonuses, refunds)
        $externalCashTypes = [
            TransactionType::DEPOSIT->value,  // V-FIX: Use DEPOSIT (actual enum value)
        ];

        if (!in_array($type->value, $externalCashTypes)) {
            // Internal operation (bonus, refund, admin credit) - bypass KYC
            return;
        }

        // External cash ingress - enforce KYC
        $complianceGate = app(ComplianceGateService::class);
        $canReceiveFunds = $complianceGate->canReceiveFunds($user);

        if (!$canReceiveFunds['allowed']) {
            Log::warning("WALLET DEPOSIT BLOCKED: KYC incomplete", [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $canReceiveFunds['reason'],
            ]);

            // Log compliance block for audit trail
            $complianceGate->logComplianceBlock($user, 'wallet_deposit', $canReceiveFunds);

            throw new ComplianceBlockedException(
                $canReceiveFunds['reason'],
                $canReceiveFunds['requirements'] ?? []
            );
        }
    }

    /**
     * [PROTOCOL 1 FIX]: Deposit taxable funds with TDS enforcement.
     *
     * WHY: Makes TDS bypass STRUCTURALLY IMPOSSIBLE.
     * - Cannot pass raw amount (must use TdsResult from TdsCalculationService)
     * - TdsResult has private constructor (only service can create it)
     * - Wallet ledger automatically includes TDS metadata
     *
     * BEFORE (BYPASSABLE):
     * ```php
     * $net = $gross * 0.9; // ❌ Hardcoded TDS
     * $walletService->deposit($user, $net, ...);
     * ```
     *
     * AFTER (ENFORCED):
     * ```php
     * $tdsResult = $tdsService->calculate($gross, 'bonus'); // ✓ TDS required
     * $walletService->depositTaxable($user, $tdsResult, ...);
     * ```
     *
     * @param User $user
     * @param TdsResult $tdsResult TDS calculation result (CANNOT be created manually)
     * @param TransactionType|string $type Transaction type
     * @param string $baseDescription Base description (TDS info appended automatically)
     * @param Model|null $reference
     * @return Transaction
     */
    public function depositTaxable(
        User $user,
        TdsResult $tdsResult,
        TransactionType|string $type,
        string $baseDescription = '',
        ?Model $reference = null
    ): Transaction {
        // Convert string to TransactionType enum if needed
        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        // Automatically append TDS information to description
        $description = $tdsResult->getDescription($baseDescription);

        // Use the internal deposit() method with net amount
        return $this->deposit(
            user: $user,
            amount: $tdsResult->netAmount, // Credit net amount only
            type: $type,
            description: $description,
            reference: $reference
        );
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
        int|float|string $amount,
        TransactionType|string $type,
        string $description,
        ?Model $reference = null,
        bool $lockBalance = false,
        bool $allowOverdraft = false // [PROTOCOL 7]: New Argument for Admin Overrides
    ): Transaction
    {
        // V-FIX-WALLET-NOT-REFLECTING: Handle string amounts from decimal database columns
        // Same fix as deposit() - handle string, float, and int amounts correctly
        if (is_string($amount)) {
            $amountPaise = (int)round((float)$amount * 100);
        } elseif (is_float($amount)) {
            $amountPaise = (int)round($amount * 100);
        } else {
            $amountPaise = $amount;
        }

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