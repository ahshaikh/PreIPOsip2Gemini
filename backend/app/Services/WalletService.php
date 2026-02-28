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
 *
 * V-PHASE4.1 (2026-02):
 * - Integrated double-entry ledger for platform-level accounting
 * - Deposit: DEBIT BANK, CREDIT USER_WALLET_LIABILITY
 * - Withdrawal: DEBIT USER_WALLET_LIABILITY, CREDIT BANK
 */

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Model; // [AUDIT FIX]: Use Eloquent base model for broader compatibility
use App\Enums\TransactionType; // [AUDIT FIX]: Use strict Enums
use App\Exceptions\Financial\InsufficientBalanceException;
use App\Exceptions\Financial\ComplianceBlockedException; // [C.8]: KYC enforcement
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * PHASE 4.1: Double-entry ledger service for platform accounting.
     */
    private DoubleEntryLedgerService $ledgerService;

    /**
     * Constructor with double-entry ledger injection.
     */
    public function __construct(DoubleEntryLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

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
            // V-FIX-WALLET-LOOKUP-2026: Fixed wallet lookup to prevent creating duplicate wallets.
            // Use explicit query by user_id instead of relationship accessor to ensure
            // we find the wallet regardless of relationship caching state.
            //
            // The previous issue: $user->wallet relationship accessor may not find existing
            // wallet when called on fresh User instances in some contexts.
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance_paise' => 0,
                    'locked_balance_paise' => 0,
                ]);
                // Re-acquire lock after creation
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            $balanceBefore = $wallet->balance_paise;

            // [AUDIT FIX]: Atomic increment at the database level.
            $wallet->increment('balance_paise', $amountPaise);
            $wallet->refresh();

            // [PATCH]: Runtime invariant
            if ($wallet->balance_paise < 0) {
                throw new \RuntimeException('Invariant violation: negative balance after deposit');
            }

            // 3. Create the immutable ledger entry (user wallet transaction)
            $transaction = $wallet->transactions()->create([
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

            // PHASE 4.1: Record in double-entry ledger
            // DEBIT BANK (cash received), CREDIT USER_WALLET_LIABILITY (we owe user)
            // Only for actual deposits (not internal transfers like bonus conversions)
            //
            // V-AUDIT-REVENUE-2026: Subscription payments are NOT revenue.
            // Treat SUBSCRIPTION_PAYMENT exactly like DEPOSIT (user-owned capital).
            if ($type === TransactionType::DEPOSIT || $type === TransactionType::SUBSCRIPTION_PAYMENT) {
                $amountRupees = $amountPaise / 100;
                $payment = $reference instanceof Payment ? $reference : null;
                $this->ledgerService->recordUserDeposit($user, $payment ?? $transaction->id, $amountRupees);
            }

            // PHASE 4.1: Record refund in ledger
            // DEBIT SHARE_SALE_INCOME (reverse revenue), CREDIT USER_WALLET_LIABILITY
            if ($type === TransactionType::REFUND) {
                $amountRupees = $amountPaise / 100;
                $this->ledgerService->recordRefund($reference?->id ?? $transaction->id, $amountRupees);
            }

            // PHASE 4 SECTION 7.2: Step 7.2 - Bonus credit to wallet
            // Transfer from BONUS_LIABILITY to USER_WALLET_LIABILITY
            // recordBonusWithTds() has already credited BONUS_LIABILITY, now we transfer to wallet
            if ($type === TransactionType::BONUS_CREDIT) {
                $amountRupees = $amountPaise / 100;
                $bonusType = 'bonus_credit'; // Generic type, actual type tracked in BonusTransaction
                $this->ledgerService->recordBonusToWallet($transaction->id, $amountRupees, $bonusType);
            }

            return $transaction;
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
     *
     * PHASE 4 SECTION 7.2: Added $bonusAmountPaise parameter for bonus usage accounting.
     * When shares are purchased using bonus funds, the caller must specify the bonus portion.
     * This triggers the required ledger entry: DEBIT BONUS_LIABILITY, CREDIT COST_OF_SHARES.
     *
     * V-WAVE3-REVERSAL: Added $bypassRecoveryCheck parameter for internal operations.
     */
    public function withdraw(
        User $user,
        int|float|string $amount,
        TransactionType|string $type,
        string $description,
        ?Model $reference = null,
        bool $lockBalance = false,
        bool $allowOverdraft = false,
        int $bonusAmountPaise = 0, // PHASE 4 SECTION 7.2: Portion of withdrawal from bonus funds
        bool $bypassRecoveryCheck = false // V-WAVE3-REVERSAL: For internal reversal operations
    ): Transaction {
        $amountPaise = $this->normalizeAmount($amount);

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be positive.");
        }

        if (is_string($type)) {
            $type = TransactionType::from($type);
        }

        // V-WAVE3-REVERSAL: Block withdrawals if account is in recovery mode
        // Recovery mode is set when user owes money after bonus reversal shortfall
        // Only deposits are allowed until receivable is settled
        if (!$bypassRecoveryCheck) {
            $wallet = Wallet::where('user_id', $user->id)->first();
            if ($wallet && $wallet->is_recovery_mode) {
                throw new \App\Exceptions\Financial\AccountRecoveryModeException(
                    "Account is in financial recovery mode. Withdrawals are blocked until outstanding receivables are settled."
                );
            }
        }

        // PHASE 4 SECTION 7.2: Validate bonus amount doesn't exceed total
        if ($bonusAmountPaise > $amountPaise) {
            throw new \InvalidArgumentException("Bonus amount cannot exceed withdrawal amount.");
        }

        return DB::transaction(function () use (
            $user,
            $amountPaise,
            $type,
            $description,
            $reference,
            $lockBalance,
            $allowOverdraft,
            $bonusAmountPaise
        ) {
            // V-FIX-WALLET-LOOKUP-2026: Use explicit query by user_id (see deposit method comment)
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance_paise' => 0,
                    'locked_balance_paise' => 0,
                ]);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            if (!$allowOverdraft && $wallet->balance_paise < $amountPaise) {
                $availableRupees = (string) ($wallet->balance_paise / 100);
                $requestedRupees = (string) ($amountPaise / 100);
                throw new InsufficientBalanceException($availableRupees, $requestedRupees);
            }

            $balanceBefore = $wallet->balance_paise;
            $status = 'completed';

            if ($lockBalance) {
                // PHASE 4.1 FIX: balance_paise is TOTAL balance (including locked).
                // When locking funds, we do NOT decrement balance_paise (total),
                // we only increment locked_balance_paise.
                // This ensures balance_paise remains the SSOT for "total funds owned by user".
                $wallet->increment('locked_balance_paise', $amountPaise);
                $status = 'pending';
            } else {
                // Direct withdrawal: decrement total balance
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

            $transaction = $wallet->transactions()->create([
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

            // PHASE 4.1: Record in double-entry ledger for completed withdrawals
            // DEBIT USER_WALLET_LIABILITY (we owe less), CREDIT BANK (cash paid out)
            // Only for actual withdrawals that are completed (not locked/pending)
            if ($type === TransactionType::WITHDRAWAL && $status === 'completed') {
                $amountRupees = $amountPaise / 100;
                $withdrawal = $reference instanceof Withdrawal ? $reference : null;
                // Use transaction ID as fallback if Withdrawal model is missing
                $this->ledgerService->recordWithdrawal($withdrawal ?? $transaction->id, $amountRupees);
            }

            // PHASE 4 SECTION 7.2: Record investment ledger entries
            // Subscription grants entitlement (access rights of the PreIPOsip platform),
            // but subscription payments do not constitute platform revenue.
            // All subscription funds remain user-owned capital until used for investments.

            if ($type === TransactionType::INVESTMENT && $status === 'completed') {
                $cashAmountPaise = $amountPaise - $bonusAmountPaise;

                // Record share sale income for cash portion
                // DEBIT USER_WALLET_LIABILITY, CREDIT SHARE_SALE_INCOME
                if ($cashAmountPaise > 0) {
                    $cashAmountRupees = $cashAmountPaise / 100;
                    $this->ledgerService->recordShareSaleFromWallet(
                        $transaction->id,
                        $cashAmountRupees
                    );
                }

                // Step 7.3: Record bonus usage for bonus portion
                // DEBIT BONUS_LIABILITY, CREDIT COST_OF_SHARES
                // Note: Does NOT credit SHARE_SALE_INCOME (as per requirement)
                if ($bonusAmountPaise > 0) {
                    $bonusAmountRupees = $bonusAmountPaise / 100;
                    $this->ledgerService->recordBonusUsage(
                        $transaction->id,
                        $bonusAmountRupees,
                        'investment' // Bonus used for share purchase
                    );
                }

                Log::info('PHASE 4 SECTION 7.2: Investment ledger entries recorded', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'total_amount_paise' => $amountPaise,
                    'bonus_amount_paise' => $bonusAmountPaise,
                    'cash_amount_paise' => $cashAmountPaise,
                ]);
            }

            // V-DISPUTE-REMEDIATION-2026: Record chargeback in double-entry ledger
            // Chargebacks are BANK-INITIATED reversals that reduce both:
            // - USER_WALLET_LIABILITY (we no longer owe user)
            // - BANK (funds clawed back by gateway)
            //
            // This MUST be recorded inside the transaction to ensure atomicity.
            // If ledger entry fails, the entire withdrawal is rolled back.
            if ($type === TransactionType::CHARGEBACK && $status === 'completed') {
                // Reference must be a Payment for chargebacks
                $payment = $reference instanceof Payment ? $reference : null;
                $amountRupees = $amountPaise / 100;

                // Use transaction ID as fallback if Payment model is missing
                $this->ledgerService->recordChargeback($payment ?? $transaction->id, $amountRupees);

                Log::channel('financial_contract')->info('V-DISPUTE-REMEDIATION-2026: Chargeback ledger entry recorded', [
                    'user_id' => $user->id,
                    'payment_id' => $payment?->id,
                    'transaction_id' => $transaction->id,
                    'amount_paise' => $amountPaise,
                ]);
            }

            // PHASE 4.1: Record TDS deduction in ledger
            // DEBIT USER_WALLET_LIABILITY (decrease user debt), CREDIT TDS_PAYABLE
            if ($type === TransactionType::TDS_DEDUCTION && $status === 'completed') {
                $amountRupees = $amountPaise / 100;
                $this->ledgerService->recordTdsDeduction($reference?->id ?? $transaction->id, $amountRupees);
            }

            return $transaction;
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
            // V-FIX-WALLET-LOOKUP-2026: Use explicit query by user_id (see deposit method comment)
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance_paise' => 0,
                    'locked_balance_paise' => 0,
                ]);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            // Check available balance (balance - locked)
            $availableBalance = $wallet->balance_paise - $wallet->locked_balance_paise;

            if ($availableBalance < $amountPaise) {
                $availableRupees = (string) ($availableBalance / 100);
                $requestedRupees = (string) ($amountPaise / 100);
                throw new InsufficientBalanceException($availableRupees, $requestedRupees);
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
            // V-FIX-WALLET-LOOKUP-2026: Use explicit query by user_id (see deposit method comment)
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance_paise' => 0,
                    'locked_balance_paise' => 0,
                ]);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

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
     * V-CHARGEBACK-HARDENING-2026: Process chargeback wallet adjustment.
     *
     * This method handles wallet adjustments during chargeback processing.
     * It supports both debits (when chargeback > investment) and credits
     * (when investment > chargeback, rare partial chargeback scenario).
     *
     * ATOMICITY: This method MUST be called within an existing DB::transaction().
     * It does NOT start its own transaction to ensure atomic execution with
     * the chargeback ledger entries.
     *
     * OVERDRAFT POLICY: Overdraft is NOT allowed. If the debit exceeds wallet
     * balance, wallet is debited to ZERO and shortfall is returned for the
     * caller to record as receivable. Transaction MUST commit (no rollback).
     *
     * LEDGER INTEGRATION: This method handles ALL ledger entries internally.
     * - recordRefund(): For credit restorations
     * - recordChargeback(): For bank clawbacks
     * - recordChargebackReceivable(): For shortfalls
     *
     * @param User $user The user whose wallet to adjust
     * @param int $netChangePaise Net change in paise (negative = debit, positive = credit)
     * @param Payment $payment The payment being charged back (for audit trail)
     * @return array{transaction: Transaction|null, shortfall_paise: int} Result with optional shortfall
     * @throws \RuntimeException If wallet cannot be found
     */
    public function processChargebackAdjustment(
        User $user,
        int $netChangePaise,
        Payment $payment
    ): array {
        if ($netChangePaise === 0) {
            // No adjustment needed (most common: full unwind where investment == chargeback)
            Log::channel('financial_contract')->debug('Chargeback wallet adjustment: no change needed', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
            ]);
            return ['transaction' => null, 'shortfall_paise' => 0];
        }

        // CRITICAL: Acquire row-level lock on wallet
        // V-FIX-WALLET-LOOKUP-2026: Use explicit query by user_id (see deposit method comment)
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
        if (!$wallet) {
            throw new \RuntimeException("Wallet not found for user #{$user->id} during chargeback adjustment");
        }

        $balanceBefore = $wallet->balance_paise;

        if ($netChangePaise < 0) {
            // DEBIT: Chargeback exceeds investment reversal
            // User owes more than what was returned from investment reversal
            $debitAmountPaise = abs($netChangePaise);
            $shortfallPaise = 0;
            $actualDebitPaise = $debitAmountPaise;

            // V-CHARGEBACK-HARDENING-2026: NO OVERDRAFT - DEBIT TO ZERO
            // Chargeback MUST complete (bank finality). If insufficient balance:
            // 1. Debit wallet to zero
            // 2. Record shortfall as receivable
            if ($wallet->balance_paise < $debitAmountPaise) {
                $shortfallPaise = $debitAmountPaise - $wallet->balance_paise;
                $actualDebitPaise = $wallet->balance_paise; // Only debit what's available

                Log::channel('financial_contract')->warning('CHARGEBACK SHORTFALL: Wallet insufficient', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'wallet_balance_paise' => $wallet->balance_paise,
                    'required_debit_paise' => $debitAmountPaise,
                    'actual_debit_paise' => $actualDebitPaise,
                    'shortfall_paise' => $shortfallPaise,
                    'action' => 'DEBIT_TO_ZERO_RECORD_RECEIVABLE',
                ]);

                // Create escalation record for admin review
                \App\Models\AuditLog::create([
                    'action' => 'chargeback.shortfall.receivable_created',
                    'actor_id' => $user->id,
                    'actor_type' => User::class,
                    'description' => "Chargeback shortfall: User owes ₹" . ($shortfallPaise / 100) . " for Payment #{$payment->id}",
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'user_id' => $user->id,
                        'wallet_balance_paise' => $wallet->balance_paise,
                        'required_debit_paise' => $debitAmountPaise,
                        'actual_debit_paise' => $actualDebitPaise,
                        'shortfall_paise' => $shortfallPaise,
                        'chargeback_gateway_id' => $payment->chargeback_gateway_id,
                    ],
                ]);
            }

            // A. Record bank clawback in ledger (DEBIT USER_WALLET_LIABILITY, CREDIT BANK)
            // This represents the bank taking the full amount from us.
            $this->ledgerService->recordChargeback($payment, $debitAmountPaise / 100);

            // B. Record receivable if shortfall exists (DEBIT ACCOUNTS_RECEIVABLE, CREDIT USER_WALLET_LIABILITY)
            // This balances the liability side for the portion we couldn't debit from wallet.
            if ($shortfallPaise > 0) {
                $this->ledgerService->recordChargebackReceivable($payment, $shortfallPaise / 100, $user->id);
            }

            // Debit wallet (to zero if insufficient)
            $transaction = null;
            if ($actualDebitPaise > 0) {
                $wallet->decrement('balance_paise', $actualDebitPaise);
                $wallet->refresh();

                $transaction = $wallet->transactions()->create([
                    'user_id' => $user->id,
                    'type' => TransactionType::CHARGEBACK->value,
                    'status' => 'completed',
                    'amount_paise' => $actualDebitPaise,
                    'balance_before_paise' => $balanceBefore,
                    'balance_after_paise' => $wallet->balance_paise,
                    'description' => $shortfallPaise > 0
                        ? "Chargeback (partial - shortfall ₹" . ($shortfallPaise / 100) . " recorded as receivable) for Payment #{$payment->id}"
                        : "Chargeback adjustment for Payment #{$payment->id}",
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                ]);

                Log::channel('financial_contract')->info('CHARGEBACK WALLET DEBIT via WalletService', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'requested_debit_paise' => $debitAmountPaise,
                    'actual_debit_paise' => $actualDebitPaise,
                    'shortfall_paise' => $shortfallPaise,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance_paise,
                ]);
            }

            return ['transaction' => $transaction, 'shortfall_paise' => $shortfallPaise];
        } else {
            // CREDIT: Investment reversal exceeds chargeback (partial chargeback)
            // User gets back the excess from investment reversal
            $creditAmountPaise = $netChangePaise;

            // Record revenue reversal in ledger (DEBIT SHARE_SALE_INCOME, CREDIT USER_WALLET_LIABILITY)
            $this->ledgerService->recordRefund($payment->id, $creditAmountPaise / 100);

            $wallet->increment('balance_paise', $creditAmountPaise);
            $wallet->refresh();

            // Runtime invariant check
            if ($wallet->balance_paise < 0) {
                throw new \RuntimeException('Invariant violation: negative balance after chargeback credit');
            }

            $transaction = $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::REFUND->value, // Credit uses REFUND type
                'status' => 'completed',
                'amount_paise' => $creditAmountPaise,
                'balance_before_paise' => $balanceBefore,
                'balance_after_paise' => $wallet->balance_paise,
                'description' => "Chargeback partial restoration for Payment #{$payment->id}",
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
            ]);

            Log::channel('financial_contract')->info('CHARGEBACK WALLET CREDIT via WalletService', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'credit_paise' => $creditAmountPaise,
                'balance_before' => $balanceBefore,
                'after_paise' => $wallet->balance_paise,
            ]);

            return ['transaction' => $transaction, 'shortfall_paise' => 0];
        }
    }

    /**
     * P0 FIX: Debit funds for investment/purchase operations.
     *
     * This method provides a simplified interface for immediate fund deduction,
     * returning a structured result array for controller consumption.
     *
     * @param int $userId User ID to debit from
     * @param int|float|string $amount Amount to debit
     * @param string $description Transaction description
     * @param string $type Transaction type string
     * @param array $metadata Additional metadata for audit trail
     * @return array{success: bool, transaction_id?: int, error?: string}
     */
    public function debit(
        int $userId,
        int|float|string $amount,
        string $description,
        string $type = 'company_investment',
        array $metadata = []
    ): array {
        try {
            $user = User::findOrFail($userId);

            // Map string type to TransactionType enum
            $transactionType = match ($type) {
                'company_investment' => TransactionType::INVESTMENT,
                'investment' => TransactionType::INVESTMENT,
                'withdrawal' => TransactionType::WITHDRAWAL,
                'tds_deduction' => TransactionType::TDS_DEDUCTION,
                default => TransactionType::from($type),
            };

            // Create a reference model if metadata contains identifiable info
            $reference = null;
            if (!empty($metadata['company_id'])) {
                $reference = \App\Models\Company::find($metadata['company_id']);
            }

            // Call withdraw internally
            $transaction = $this->withdraw(
                user: $user,
                amount: $amount,
                type: $transactionType,
                description: $description,
                reference: $reference,
                lockBalance: false,
                allowOverdraft: false
            );

            Log::info('WALLET DEBIT SUCCESS', [
                'user_id' => $userId,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
                'type' => $type,
                'metadata' => $metadata,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'balance_after' => $transaction->balance_after_paise,
            ];

        } catch (InsufficientBalanceException $e) {
            Log::warning('WALLET DEBIT FAILED: Insufficient balance', [
                'user_id' => $userId,
                'amount' => $amount,
                'available' => $e->getAvailableBalance(),
            ]);

            return [
                'success' => false,
                'error' => 'Insufficient balance',
                'available_balance' => $e->getAvailableBalance(),
            ];

        } catch (\Exception $e) {
            Log::error('WALLET DEBIT FAILED', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
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
            // V-FIX-WALLET-LOOKUP-2026: Use explicit query by user_id (see deposit method comment)
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance_paise' => 0,
                    'locked_balance_paise' => 0,
                ]);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            if ($wallet->locked_balance_paise < $amountPaise) {
                throw new \RuntimeException(
                    "Cannot debit locked funds. Locked balance: ₹" .
                    ($wallet->locked_balance_paise / 100) .
                    ", Requested: ₹" . ($amountPaise / 100)
                );
            }

            if ($wallet->balance_paise < $amountPaise) {
                $availableRupees = (string) ($wallet->balance_paise / 100);
                $requestedRupees = (string) ($amountPaise / 100);
                throw new InsufficientBalanceException($availableRupees, $requestedRupees);
            }

            $balanceBefore = $wallet->balance_paise;

            // Debit from both balances atomically
            // balance_paise = Total, locked_balance_paise = portion of total that is locked
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
            $transaction = $wallet->transactions()->create([
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

            // PHASE 4.1: Record in double-entry ledger for completed withdrawals
            // DEBIT USER_WALLET_LIABILITY (gross amount - we owe less)
            // CREDIT BANK (net payout), CREDIT TDS_PAYABLE (government liability)
            if ($type === TransactionType::WITHDRAWAL) {
                $amountRupees = $amountPaise / 100;
                $withdrawal = $reference instanceof Withdrawal ? $reference : null;
                $tdsRupees = 0;
                if ($withdrawal) {
                    $tdsRupees = ($withdrawal->tds_deducted_paise ?? 0) / 100;
                }
                // Use transaction ID as fallback if Withdrawal model is missing
                $this->ledgerService->recordWithdrawal($withdrawal ?? $transaction->id, $amountRupees, $tdsRupees);
            }

            return $transaction;
        });
    }
}
