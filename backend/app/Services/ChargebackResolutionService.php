<?php
// V-WAVE3-REVERSAL-2026: Chargeback/Refund Resolution Service
// V-WAVE3-REVERSAL-HARDENING: Added idempotency, automated recovery exit, receivable tracking
// V-WAVE3-REVERSAL-AUDIT: Dedicated receivable table, relational link for reversals
// Orchestrates atomic reversal of all financial effects caused by a payment.
// Ensures no silent monetary inflation and proper receivable tracking.

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\ChargebackReceivable;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ChargebackResolutionService - Atomic Financial Reversal Orchestrator
 *
 * This service handles the complete financial reversal when a payment is
 * refunded or charged back. It ensures:
 *
 * 1. All bonuses tied to the payment are reversed
 * 2. Wallet is debited for bonus amounts (partial if insufficient)
 * 3. Shortfalls are recorded as receivables (dedicated table)
 * 4. Account is frozen when recovery is needed
 * 5. All operations are atomic (single transaction)
 * 6. Operations are idempotent (safe to retry)
 *
 * INVARIANTS (must hold after resolution):
 * - Payment financial impact = 0
 * - Bonus financial impact = 0
 * - TDS financial impact = 0
 * - Wallet delta correctly reconciled
 * - Ledger balanced (debits == credits)
 * - No silent monetary inflation
 *
 * @see WalletService::processChargebackAdjustment() for wallet debit logic
 * @see DoubleEntryLedgerService::recordChargebackReceivable() for receivable recording
 */
class ChargebackResolutionService
{
    public function __construct(
        protected WalletService $walletService,
        protected AllocationService $allocationService,
        protected DoubleEntryLedgerService $ledgerService
    ) {}

    /**
     * Resolve an admin-initiated refund.
     *
     * IDEMPOTENCY: This method is idempotent. If called twice with the same
     * payment, the second call will return early with the previous result.
     * Idempotency is enforced via:
     * 1. Payment status check (must be 'paid')
     * 2. Cache lock during processing
     * 3. Payment status change to 'refunded' atomically
     * 4. Relational link for bonus reversals (reversal_of_bonus_id)
     *
     * @param Payment $payment The payment to refund
     * @param string $reason Reason for refund
     * @param array $options {
     *     @type bool $reverse_bonuses Whether to reverse bonuses (default: true)
     *     @type bool $reverse_allocations Whether to reverse share allocations (default: true)
     *     @type bool $refund_payment Whether to refund payment amount to wallet (default: true)
     *     @type bool $process_gateway_refund Whether to process gateway refund (default: true)
     *     @type string|null $idempotency_key Optional idempotency key for retry safety
     * }
     * @return array Resolution result with details
     * @throws \RuntimeException If resolution fails
     */
    public function resolveRefund(
        Payment $payment,
        string $reason,
        array $options = []
    ): array {
        $options = array_merge([
            'reverse_bonuses' => true,
            'reverse_allocations' => true,
            'refund_payment' => true,
            'process_gateway_refund' => true,
            'idempotency_key' => null,
        ], $options);

        // V-WAVE3-REVERSAL-HARDENING: Idempotency check via payment status
        if ($payment->status === Payment::STATUS_REFUNDED) {
            Log::channel('financial_contract')->info('REFUND ALREADY PROCESSED (idempotent)', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ]);
            return [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'type' => 'refund',
                'reason' => $reason,
                'already_processed' => true,
                'message' => 'Payment was already refunded',
            ];
        }

        if ($payment->status !== Payment::STATUS_PAID) {
            throw new \RuntimeException("Only paid payments can be refunded. Current status: {$payment->status}");
        }

        // V-WAVE3-REVERSAL-HARDENING: Idempotency lock to prevent concurrent processing
        $lockKey = "refund_processing:{$payment->id}";
        $lock = Cache::lock($lockKey, 60);

        if (!$lock->get()) {
            throw new \RuntimeException("Payment #{$payment->id} is currently being processed. Please retry.");
        }

        try {
            $result = $this->executeRefundResolution($payment, $reason, $options);
        } finally {
            $lock->release();
        }

        return $result;
    }

    /**
     * Execute the actual refund resolution within a lock.
     */
    protected function executeRefundResolution(
        Payment $payment,
        string $reason,
        array $options
    ): array {
        $result = [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'type' => 'refund',
            'reason' => $reason,
            'bonuses_reversed' => [],
            'bonus_wallet_debited_paise' => 0,
            'bonus_shortfall_paise' => 0,
            'allocations_reversed' => false,
            'payment_refunded_paise' => 0,
            'account_frozen' => false,
            'receivable_created' => false,
            'receivable_id' => null,
            'already_processed' => false,
        ];

        DB::transaction(function () use ($payment, $reason, $options, &$result) {
            // Re-check status inside transaction with lock
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            // Double-check idempotency inside transaction
            if ($payment->status === Payment::STATUS_REFUNDED) {
                $result['already_processed'] = true;
                $result['message'] = 'Payment was already refunded (race condition avoided)';
                return;
            }

            $user = $payment->user;

            // 1. Reverse Bonuses (if requested)
            if ($options['reverse_bonuses']) {
                $bonusResult = $this->reverseBonusesWithWalletReconciliation($payment, $user, $reason);
                $result['bonuses_reversed'] = $bonusResult['reversed_bonuses'];
                $result['bonus_wallet_debited_paise'] = $bonusResult['actual_debit_paise'];
                $result['bonus_shortfall_paise'] = $bonusResult['shortfall_paise'];
                $result['receivable_created'] = $bonusResult['shortfall_paise'] > 0;
                $result['receivable_id'] = $bonusResult['receivable_id'] ?? null;
                $result['account_frozen'] = $bonusResult['account_frozen'];
            }

            // 2. Reverse Allocations (if requested)
            if ($options['reverse_allocations']) {
                $this->allocationService->reverseAllocationLegacy($payment, $reason);
                $result['allocations_reversed'] = true;
            }

            // 3. Refund Payment Amount to Wallet (if requested)
            if ($options['refund_payment']) {
                $refundPaise = (int) ($payment->amount * 100);
                $this->walletService->deposit(
                    $user,
                    $refundPaise,
                    TransactionType::REFUND,
                    "Refund for Payment #{$payment->id}: {$reason}",
                    $payment
                );
                $result['payment_refunded_paise'] = $refundPaise;
            }

            // 4. Mark Payment as Refunded (idempotency marker)
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ]);

            // 5. Audit Log
            AuditLog::create([
                'action' => 'payment.refund.resolved',
                'actor_id' => auth()->id() ?? $user->id,
                'actor_type' => User::class,
                'description' => "Refund resolved for Payment #{$payment->id}: {$reason}",
                'metadata' => $result,
            ]);

            Log::channel('financial_contract')->info('REFUND RESOLVED', $result);
        });

        return $result;
    }

    /**
     * Reverse all bonuses tied to a payment with proper wallet reconciliation.
     *
     * DOCTRINE:
     * - Bonuses are credited to wallet (NET = gross - TDS)
     * - Bonuses are fully spendable
     * - If payment is refunded, bonus must be recovered
     * - If wallet insufficient, debit to zero and record receivable
     * - Account is frozen when receivable is created
     *
     * IDEMPOTENCY: Uses relational link (reversal_of_bonus_id) to detect existing reversals.
     * This is deterministic and does not depend on string formatting.
     *
     * @param Payment $payment The payment whose bonuses to reverse
     * @param User $user The user
     * @param string $reason Reason for reversal
     * @return array{reversed_bonuses: array, actual_debit_paise: int, shortfall_paise: int, account_frozen: bool, receivable_id: int|null}
     */
    protected function reverseBonusesWithWalletReconciliation(
        Payment $payment,
        User $user,
        string $reason
    ): array {
        $result = [
            'reversed_bonuses' => [],
            'total_net_to_recover_paise' => 0,
            'actual_debit_paise' => 0,
            'shortfall_paise' => 0,
            'account_frozen' => false,
            'receivable_id' => null,
        ];

        // V-WAVE3-REVERSAL-AUDIT: Get non-reversal bonuses that haven't been reversed
        // Uses relational link (reversal_of_bonus_id) instead of description matching
        $bonuses = $payment->bonuses()
            ->where('type', '!=', 'reversal')
            ->whereDoesntHave('reversal') // Only bonuses without a reversal record
            ->get();

        if ($bonuses->isEmpty()) {
            Log::channel('financial_contract')->debug('No bonuses to reverse for payment (or already reversed)', [
                'payment_id' => $payment->id,
            ]);
            return $result;
        }

        // Calculate total NET amount to recover
        // NET = gross - TDS (what was actually credited to wallet)
        $totalNetToRecoverPaise = 0;
        foreach ($bonuses as $bonus) {
            $netAmount = $bonus->amount - ($bonus->tds_deducted ?? 0);
            $totalNetToRecoverPaise += (int) round($netAmount * 100);

            // Create reversal bonus transaction (with relational link)
            $reversalBonus = $bonus->reverse($reason);
            $result['reversed_bonuses'][] = [
                'original_id' => $bonus->id,
                'reversal_id' => $reversalBonus->id,
                'type' => $bonus->type,
                'gross_amount' => $bonus->amount,
                'tds_deducted' => $bonus->tds_deducted,
                'net_amount' => $netAmount,
            ];
        }

        $result['total_net_to_recover_paise'] = $totalNetToRecoverPaise;

        if ($totalNetToRecoverPaise <= 0) {
            Log::channel('financial_contract')->debug('No net bonus amount to recover', [
                'payment_id' => $payment->id,
                'total_gross' => $bonuses->sum('amount'),
                'total_tds' => $bonuses->sum('tds_deducted'),
            ]);
            return $result;
        }

        // Attempt wallet debit with shortfall handling
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
        if (!$wallet) {
            throw new \RuntimeException("Wallet not found for user #{$user->id}");
        }

        $walletBalancePaise = $wallet->balance_paise;
        $actualDebitPaise = min($walletBalancePaise, $totalNetToRecoverPaise);
        $shortfallPaise = $totalNetToRecoverPaise - $actualDebitPaise;

        // Perform wallet debit (partial if insufficient)
        if ($actualDebitPaise > 0) {
            // V-WAVE3-REVERSAL-FIX: Use WalletService::withdraw instead of direct decrement
            // This ensures DOUBLE-ENTRY LEDGER is automatically updated via WalletService
            $this->walletService->withdraw(
                user: $user,
                amount: $actualDebitPaise,
                type: TransactionType::CHARGEBACK,
                description: $shortfallPaise > 0
                    ? "Bonus recovery (partial) for refunded Payment #{$payment->id}: {$reason}"
                    : "Bonus recovery for refunded Payment #{$payment->id}: {$reason}",
                reference: $payment,
                allowOverdraft: true // Bank-initiated, must complete
            );

            Log::channel('financial_contract')->info('BONUS REVERSAL WALLET DEBIT via WalletService', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'total_to_recover_paise' => $totalNetToRecoverPaise,
                'actual_debit_paise' => $actualDebitPaise,
                'shortfall_paise' => $shortfallPaise,
            ]);
        }

        $result['actual_debit_paise'] = $actualDebitPaise;
        $result['shortfall_paise'] = $shortfallPaise;

        // Handle shortfall: create receivable and freeze account
        if ($shortfallPaise > 0) {
            $receivable = $this->handleBonusRecoveryShortfall($payment, $user, $wallet, $shortfallPaise, $reason);
            $result['account_frozen'] = true;
            $result['receivable_id'] = $receivable->id;
        }

        return $result;
    }

    /**
     * Handle bonus recovery shortfall by creating receivable and freezing account.
     *
     * V-WAVE3-REVERSAL-AUDIT: Creates dedicated receivable record instead of wallet-level aggregation.
     *
     * @param Payment $payment The payment being refunded
     * @param User $user The user
     * @param Wallet $wallet The wallet (already locked)
     * @param int $shortfallPaise The amount still owed (in paise)
     * @param string $reason Reason for the shortfall
     * @return ChargebackReceivable The created receivable record
     */
    protected function handleBonusRecoveryShortfall(
        Payment $payment,
        User $user,
        Wallet $wallet,
        int $shortfallPaise,
        string $reason
    ): ChargebackReceivable {
        // 1. Record receivable in ledger
        $shortfallRupees = $shortfallPaise / 100;
        $ledgerEntry = $this->ledgerService->recordChargebackReceivable($payment, $shortfallRupees, $user->id);

        // 2. V-WAVE3-REVERSAL-AUDIT: Create dedicated receivable record
        $receivable = ChargebackReceivable::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'ledger_entry_id' => $ledgerEntry->id,
            'amount_paise' => $shortfallPaise,
            'paid_paise' => 0,
            'balance_paise' => $shortfallPaise,
            'status' => ChargebackReceivable::STATUS_PENDING,
            'source_type' => ChargebackReceivable::SOURCE_REFUND,
            'reason' => $reason,
        ]);

        // 3. Set wallet to recovery mode
        $wallet->update(['is_recovery_mode' => true]);

        // 4. Create audit log
        AuditLog::create([
            'action' => 'bonus.reversal.shortfall',
            'actor_id' => $user->id,
            'actor_type' => User::class,
            'description' => "Bonus reversal shortfall: User owes ₹{$shortfallRupees} for Payment #{$payment->id}",
            'metadata' => [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'receivable_id' => $receivable->id,
                'shortfall_paise' => $shortfallPaise,
                'shortfall_rupees' => $shortfallRupees,
                'reason' => $reason,
                'account_frozen' => true,
                'recovery_mode_enabled' => true,
            ],
        ]);

        Log::channel('financial_contract')->warning('BONUS REVERSAL SHORTFALL - ACCOUNT FROZEN', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'receivable_id' => $receivable->id,
            'shortfall_paise' => $shortfallPaise,
            'action' => 'RECEIVABLE_CREATED_ACCOUNT_FROZEN',
        ]);

        return $receivable;
    }

    /**
     * Resolve a bank-initiated chargeback.
     */
    public function resolveChargeback(Payment $payment, string $reason): array
    {
        return $this->resolveRefund($payment, "Chargeback: {$reason}", [
            'reverse_bonuses' => true,
            'reverse_allocations' => true,
            'refund_payment' => false,
            'process_gateway_refund' => false,
        ]);
    }

    /**
     * Check if a user's account is in recovery mode.
     */
    public function isInRecoveryMode(User $user): bool
    {
        return $user->wallet?->is_recovery_mode ?? false;
    }

    /**
     * V-WAVE3-REVERSAL-AUDIT: Get total outstanding receivable balance for a user.
     * Uses dedicated receivable table for accurate multi-receivable tracking.
     */
    public function getReceivableBalance(User $user): int
    {
        return ChargebackReceivable::forUser($user->id)
            ->outstanding()
            ->sum('balance_paise');
    }

    /**
     * V-WAVE3-REVERSAL-AUDIT: Get all outstanding receivables for a user.
     */
    public function getOutstandingReceivables(User $user)
    {
        return ChargebackReceivable::forUser($user->id)
            ->outstanding()
            ->orderBy('created_at')
            ->get();
    }

    /**
     * V-WAVE3-REVERSAL-AUDIT: Apply deposit towards receivable settlement.
     *
     * Applies payment to oldest receivables first (FIFO).
     * When all receivables are settled, recovery mode is cleared.
     *
     * @param User $user The user
     * @param int $depositPaise Amount deposited in paise
     * @return array Settlement result
     */
    public function applyDepositToReceivable(User $user, int $depositPaise): array
    {
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

        if (!$wallet || !$wallet->is_recovery_mode) {
            return [
                'applied_to_receivable_paise' => 0,
                'remaining_receivable_paise' => 0,
                'recovery_mode_cleared' => false,
                'receivables_settled' => [],
            ];
        }

        // Get outstanding receivables (oldest first - FIFO)
        $receivables = ChargebackReceivable::forUser($user->id)
            ->outstanding()
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        if ($receivables->isEmpty()) {
            // No receivables but in recovery mode - clear it
            $wallet->update(['is_recovery_mode' => false]);
            return [
                'applied_to_receivable_paise' => 0,
                'remaining_receivable_paise' => 0,
                'recovery_mode_cleared' => true,
                'receivables_settled' => [],
            ];
        }

        $remainingDeposit = $depositPaise;
        $totalApplied = 0;
        $settledReceivables = [];

        foreach ($receivables as $receivable) {
            if ($remainingDeposit <= 0) {
                break;
            }

            $applied = $receivable->applyPayment($remainingDeposit);
            $totalApplied += $applied;
            $remainingDeposit -= $applied;

            if ($receivable->isSettled()) {
                $settledReceivables[] = $receivable->id;
            }
        }

        // Check if all receivables are settled
        $remainingBalance = ChargebackReceivable::forUser($user->id)
            ->outstanding()
            ->sum('balance_paise');

        $recoveryModeCleared = $remainingBalance <= 0;

        if ($recoveryModeCleared) {
            $wallet->update(['is_recovery_mode' => false]);
        }

        // Audit log
        if ($totalApplied > 0) {
            AuditLog::create([
                'action' => $recoveryModeCleared ? 'receivable.fully_settled' : 'receivable.partial_payment',
                'actor_id' => $user->id,
                'actor_type' => User::class,
                'description' => $recoveryModeCleared
                    ? "All receivables settled for User #{$user->id}"
                    : "Partial receivable payment for User #{$user->id}",
                'metadata' => [
                    'user_id' => $user->id,
                    'applied_paise' => $totalApplied,
                    'remaining_balance_paise' => $remainingBalance,
                    'receivables_settled' => $settledReceivables,
                    'recovery_mode_cleared' => $recoveryModeCleared,
                ],
            ]);

            Log::channel('financial_contract')->info(
                $recoveryModeCleared ? 'ALL RECEIVABLES SETTLED - RECOVERY MODE CLEARED' : 'PARTIAL RECEIVABLE PAYMENT',
                [
                    'user_id' => $user->id,
                    'applied_paise' => $totalApplied,
                    'remaining_balance_paise' => $remainingBalance,
                ]
            );
        }

        return [
            'applied_to_receivable_paise' => $totalApplied,
            'remaining_receivable_paise' => $remainingBalance,
            'recovery_mode_cleared' => $recoveryModeCleared,
            'receivables_settled' => $settledReceivables,
        ];
    }

    /**
     * Clear recovery mode after receivable is settled.
     *
     * V-WAVE3-REVERSAL-AUDIT: Validates that all receivables are settled
     * before clearing recovery mode.
     *
     * @param User $user
     * @param string $settlementReference Reference to settlement
     * @param bool $forceOverride Admin override to force clear
     * @throws \RuntimeException If receivables exist and forceOverride is false
     */
    public function clearRecoveryMode(
        User $user,
        string $settlementReference,
        bool $forceOverride = false
    ): void {
        $wallet = $user->wallet;

        if (!$wallet || !$wallet->is_recovery_mode) {
            return;
        }

        // V-WAVE3-REVERSAL-AUDIT: Check outstanding receivables from dedicated table
        $outstandingBalance = $this->getReceivableBalance($user);

        if (!$forceOverride && $outstandingBalance > 0) {
            throw new \RuntimeException(
                "Cannot clear recovery mode: Outstanding receivables of ₹" .
                ($outstandingBalance / 100) . " exist. " .
                "Use forceOverride=true for admin override."
            );
        }

        // If force override, write off remaining receivables
        if ($forceOverride && $outstandingBalance > 0) {
            $receivables = ChargebackReceivable::forUser($user->id)->outstanding()->get();
            foreach ($receivables as $receivable) {
                $receivable->writeOff(auth()->id() ?? $user->id, "Admin override: {$settlementReference}");
            }
        }

        $wallet->update(['is_recovery_mode' => false]);

        AuditLog::create([
            'action' => $forceOverride ? 'recovery.mode.admin_override' : 'recovery.mode.cleared',
            'actor_id' => auth()->id() ?? $user->id,
            'actor_type' => User::class,
            'description' => $forceOverride
                ? "Recovery mode ADMIN OVERRIDE for User #{$user->id}"
                : "Recovery mode cleared for User #{$user->id}",
            'metadata' => [
                'user_id' => $user->id,
                'settlement_reference' => $settlementReference,
                'force_override' => $forceOverride,
                'written_off_balance_paise' => $forceOverride ? $outstandingBalance : 0,
            ],
        ]);

        Log::channel('financial_contract')->info(
            $forceOverride ? 'RECOVERY MODE ADMIN OVERRIDE' : 'RECOVERY MODE CLEARED',
            [
                'user_id' => $user->id,
                'settlement_reference' => $settlementReference,
                'force_override' => $forceOverride,
            ]
        );
    }
}
