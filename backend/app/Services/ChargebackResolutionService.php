<?php
// V-WAVE3-REVERSAL-2026: Chargeback/Refund Resolution Service
// V-ORCHESTRATION-2026: Consolidated financial mutations into FinancialOrchestrator.

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
 * ChargebackResolutionService - Policy & Routing for Financial Reversals
 *
 * This service determines the policy for reversals and routes the actual
 * financial mutations to the FinancialOrchestrator.
 *
 * @see FinancialOrchestrator::processRefund()
 * @see FinancialOrchestrator::processChargeback()
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
            'idempotency_key' => null,
        ], $options);

        if ($payment->status === Payment::STATUS_REFUNDED) {
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
     * Resolve a bank-initiated chargeback.
     */
    public function resolveChargeback(Payment $payment, string $reason): array
    {
        return $this->resolveRefund($payment, "Chargeback: {$reason}", [
            'reverse_bonuses' => true,
            'reverse_allocations' => true,
            'refund_payment' => false,
        ]);
    }

    /**
     * Execute the actual refund resolution via Orchestrator.
     */
    protected function executeRefundResolution(
        Payment $payment,
        string $reason,
        array $options
    ): array {
        $orchestrator = app(\App\Services\FinancialOrchestrator::class);
        
        if ($options['refund_payment']) {
            return $orchestrator->processRefund($payment, $reason);
        } else {
            return $orchestrator->processChargeback($payment, $reason);
        }
    }

    /**
     * Check if a user's account is in recovery mode.
     */
    public function isInRecoveryMode(User $user): bool
    {
        return $user->wallet?->is_recovery_mode ?? false;
    }

    /**
     * Get total outstanding receivable balance for a user.
     */
    public function getReceivableBalance(User $user): int
    {
        return ChargebackReceivable::forUser($user->id)
            ->outstanding()
            ->sum('balance_paise');
    }

    /**
     * Clear recovery mode after receivable is settled.
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
            ],
        ]);
    }
}
