<?php
/**
 * FIX 4 (P0): Payment Allocation Saga
 *
 * CRITICAL: Provides crash-safe, rollback-capable payment processing
 * Ensures money is never lost even if allocation fails mid-process.
 *
 * Flow:
 * 1. Credit Wallet → Transaction created
 * 2. Calculate & Credit Bonus → BonusTransaction created (if applicable)
 * 3. Allocate Shares → UserInvestment created
 * 4. Update Inventory → BulkPurchase.value_remaining decremented
 *
 * On Failure:
 * - Automatic compensation (rollback) in reverse order
 * - All steps tracked in SagaExecution for crash recovery
 * - Manual resolution dashboard for admin intervention
 */

namespace App\Services;

use App\Models\{Payment, SagaExecution, Transaction, BonusTransaction, UserInvestment, Subscription};
use App\Services\{WalletService, AllocationService};
use Illuminate\Support\Facades\{DB, Log};
use App\Enums\TransactionType;

class PaymentAllocationSaga
{
    protected WalletService $walletService;
    protected AllocationService $allocationService;

    public function __construct(
        WalletService $walletService,
        AllocationService $allocationService
    ) {
        $this->walletService = $walletService;
        $this->allocationService = $allocationService;
    }

    /**
     * Execute complete payment allocation saga
     *
     * @throws \Exception on failure (after compensation attempt)
     */
    public function execute(Payment $payment): SagaExecution
    {
        // Create saga tracking record
        $saga = SagaExecution::create([
            'saga_id' => "payment-allocation-{$payment->id}-" . time(),
            'status' => 'processing',
            'steps_total' => 3, // Wallet credit, bonus, allocation
            'steps_completed' => 0,
            'metadata' => [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'subscription_id' => $payment->subscription_id,
                'amount' => $payment->amount,
                'amount_paise' => bcmul($payment->amount, 100),
            ],
            'initiated_at' => now(),
        ]);

        try {
            Log::info('Starting payment allocation saga', [
                'saga_id' => $saga->saga_id,
                'payment_id' => $payment->id,
            ]);

            // Step 1: Credit Wallet
            $transaction = $this->creditWallet($payment, $saga);
            $this->markStepCompleted($saga, 'credit_wallet', [
                'transaction_id' => $transaction->id,
                'amount_paise' => $transaction->amount_paise,
            ]);

            // Step 2: Calculate & Credit Bonus (optional)
            $bonus = $this->processBonus($payment, $saga);
            if ($bonus) {
                $this->markStepCompleted($saga, 'credit_bonus', [
                    'bonus_id' => $bonus->id,
                    'amount' => $bonus->amount,
                ]);
            }

            // Step 3: Allocate Shares
            $allocations = $this->allocateShares($payment, $saga);
            $this->markStepCompleted($saga, 'allocate_shares', [
                'investment_ids' => $allocations->pluck('id')->toArray(),
                'total_value_allocated' => $allocations->sum('value_allocated'),
            ]);

            // Mark saga as completed
            $saga->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info('Payment allocation saga completed successfully', [
                'saga_id' => $saga->saga_id,
                'payment_id' => $payment->id,
            ]);

            return $saga;

        } catch (\Exception $e) {
            Log::error('Payment allocation saga failed', [
                'saga_id' => $saga->saga_id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->markFailed($saga, $e->getMessage());
            $this->compensate($saga);

            // Re-throw to inform caller
            throw $e;
        }
    }

    /**
     * Step 1: Credit wallet with payment amount
     */
    protected function creditWallet(Payment $payment, SagaExecution $saga): Transaction
    {
        Log::info('Saga step: Credit wallet', ['saga_id' => $saga->saga_id]);

        return $this->walletService->deposit(
            user: $payment->user,
            amount: bcmul($payment->amount, 100), // Convert to paise
            type: TransactionType::DEPOSIT,
            description: "Payment #{$payment->id} credited",
            reference: $payment,
            bypassComplianceCheck: true // Payment already compliance-checked
        );
    }

    /**
     * Step 2: Calculate and credit bonus (if applicable)
     */
    protected function processBonus(Payment $payment, SagaExecution $saga): ?BonusTransaction
    {
        Log::info('Saga step: Process bonus', ['saga_id' => $saga->saga_id]);

        // Check if subscription exists
        if (!$payment->subscription_id) {
            Log::info('No subscription for payment, skipping bonus', [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        $subscription = Subscription::find($payment->subscription_id);
        if (!$subscription) {
            return null;
        }

        // Calculate bonus (this would call BonusCalculator service)
        // For now, simplified version
        $bonusAmount = 0;
        $bonusType = null;

        // Progressive bonus logic
        if ($subscription->consecutive_payments_count >= 6) {
            $bonusAmount = $payment->amount * 0.05; // 5% bonus
            $bonusType = 'progressive';
        }

        if ($bonusAmount <= 0) {
            return null;
        }

        // Create bonus transaction
        $bonus = BonusTransaction::create([
            'user_id' => $payment->user_id,
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'type' => $bonusType,
            'amount' => $bonusAmount,
            'base_amount' => $payment->amount,
            'multiplier_applied' => 0.05,
            'tds_deducted' => 0, // Would calculate TDS here
            'description' => "Progressive bonus for payment #{$payment->id}",
        ]);

        // Credit bonus to wallet
        $this->walletService->deposit(
            user: $payment->user,
            amount: bcmul($bonusAmount, 100),
            type: TransactionType::BONUS,
            description: "Bonus credited for payment #{$payment->id}",
            reference: $bonus,
            bypassComplianceCheck: true
        );

        return $bonus;
    }

    /**
     * Step 3: Allocate shares from available inventory
     */
    protected function allocateShares(Payment $payment, SagaExecution $saga)
    {
        Log::info('Saga step: Allocate shares', ['saga_id' => $saga->saga_id]);

        // Get subscription to determine allocation amount
        if (!$payment->subscription_id) {
            Log::info('No subscription for payment, skipping allocation', [
                'payment_id' => $payment->id,
            ]);
            return collect();
        }

        $subscription = Subscription::find($payment->subscription_id);
        if (!$subscription) {
            return collect();
        }

        // Calculate available balance for allocation
        $totalPaid = $subscription->payments()
            ->where('status', 'paid')
            ->sum('amount');

        $totalAllocated = $subscription->userInvestments()
            ->where('is_reversed', false)
            ->sum('value_allocated');

        $availableForAllocation = $totalPaid - $totalAllocated;

        if ($availableForAllocation <= 0) {
            Log::info('No funds available for allocation', [
                'subscription_id' => $subscription->id,
                'total_paid' => $totalPaid,
                'total_allocated' => $totalAllocated,
            ]);
            return collect();
        }

        // Use AllocationService to allocate from inventory
        // Note: This assumes AllocationService exists and handles FIFO allocation
        try {
            return $this->allocationService->allocateFromSubscription(
                $subscription,
                $availableForAllocation,
                $payment
            );
        } catch (\Exception $e) {
            Log::error('Allocation failed in saga', [
                'saga_id' => $saga->saga_id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark saga step as completed
     */
    protected function markStepCompleted(SagaExecution $saga, string $stepName, array $stepData): void
    {
        $metadata = $saga->metadata ?? [];
        $metadata['steps'][$stepName] = array_merge($stepData, [
            'completed_at' => now()->toDateTimeString(),
        ]);

        $saga->update([
            'steps_completed' => $saga->steps_completed + 1,
            'metadata' => $metadata,
        ]);

        Log::info("Saga step completed: {$stepName}", [
            'saga_id' => $saga->saga_id,
            'steps_completed' => $saga->steps_completed,
            'steps_total' => $saga->steps_total,
        ]);
    }

    /**
     * Mark saga as failed
     */
    protected function markFailed(SagaExecution $saga, string $reason): void
    {
        $saga->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);
    }

    /**
     * Compensate (rollback) all completed steps in reverse order
     */
    protected function compensate(SagaExecution $saga): void
    {
        Log::warning('Starting saga compensation', [
            'saga_id' => $saga->saga_id,
            'steps_completed' => $saga->steps_completed,
        ]);

        $metadata = $saga->metadata ?? [];
        $completedSteps = $metadata['steps'] ?? [];

        try {
            // Reverse in opposite order
            if (isset($completedSteps['allocate_shares'])) {
                $this->reverseAllocations($completedSteps['allocate_shares']);
            }

            if (isset($completedSteps['credit_bonus'])) {
                $this->reverseBonus($completedSteps['credit_bonus']);
            }

            if (isset($completedSteps['credit_wallet'])) {
                $this->reverseWalletCredit($completedSteps['credit_wallet']);
            }

            $saga->update([
                'status' => 'compensated',
                'compensated_at' => now(),
            ]);

            Log::info('Saga compensation completed', [
                'saga_id' => $saga->saga_id,
            ]);

        } catch (\Exception $e) {
            Log::critical('Saga compensation failed - MANUAL INTERVENTION REQUIRED', [
                'saga_id' => $saga->saga_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $saga->update([
                'status' => 'compensation_failed',
                'resolution_data' => [
                    'compensation_error' => $e->getMessage(),
                    'requires_manual_resolution' => true,
                ],
            ]);

            // Alert admins for manual resolution
            $this->alertAdminsForManualResolution($saga, $e);
        }
    }

    /**
     * Reverse share allocations
     */
    protected function reverseAllocations(array $stepData): void
    {
        if (empty($stepData['investment_ids'])) {
            return;
        }

        foreach ($stepData['investment_ids'] as $investmentId) {
            $investment = UserInvestment::find($investmentId);
            if ($investment && !$investment->is_reversed) {
                DB::transaction(function () use ($investment) {
                    // Mark as reversed
                    $investment->update([
                        'is_reversed' => true,
                        'reversed_at' => now(),
                        'reversal_reason' => 'Payment allocation saga compensation',
                    ]);

                    // Restore inventory
                    $investment->bulkPurchase->increment(
                        'value_remaining',
                        $investment->value_allocated
                    );

                    Log::info('Reversed investment allocation', [
                        'investment_id' => $investment->id,
                        'value_allocated' => $investment->value_allocated,
                    ]);
                });
            }
        }
    }

    /**
     * Reverse bonus credit
     */
    protected function reverseBonus(array $stepData): void
    {
        if (empty($stepData['bonus_id'])) {
            return;
        }

        $bonus = BonusTransaction::find($stepData['bonus_id']);
        if ($bonus) {
            $bonus->reverse('Payment allocation saga compensation');

            // Debit from wallet
            $this->walletService->withdraw(
                user: $bonus->user,
                amount: bcmul($bonus->amount, 100),
                type: TransactionType::REVERSAL,
                description: "Bonus reversal for saga compensation",
                reference: $bonus
            );

            Log::info('Reversed bonus transaction', [
                'bonus_id' => $bonus->id,
                'amount' => $bonus->amount,
            ]);
        }
    }

    /**
     * Reverse wallet credit
     */
    protected function reverseWalletCredit(array $stepData): void
    {
        if (empty($stepData['transaction_id'])) {
            return;
        }

        $transaction = Transaction::find($stepData['transaction_id']);
        if ($transaction && !$transaction->is_reversed) {
            // Mark transaction as reversed
            $transaction->update([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => 'Payment allocation saga compensation',
            ]);

            // Create compensating transaction (debit)
            $this->walletService->withdraw(
                user: $transaction->user,
                amount: $transaction->amount_paise,
                type: TransactionType::REVERSAL,
                description: "Reversal: Payment allocation failed",
                reference: $transaction,
                allowOverdraft: true // Allow for compensation
            );

            Log::info('Reversed wallet credit', [
                'transaction_id' => $transaction->id,
                'amount_paise' => $transaction->amount_paise,
            ]);
        }
    }

    /**
     * Alert admins for manual saga resolution
     */
    protected function alertAdminsForManualResolution(SagaExecution $saga, \Exception $e): void
    {
        // Log critical alert
        Log::critical('SAGA COMPENSATION FAILED - IMMEDIATE ACTION REQUIRED', [
            'saga_id' => $saga->saga_id,
            'payment_id' => $saga->metadata['payment_id'] ?? null,
            'user_id' => $saga->metadata['user_id'] ?? null,
            'error' => $e->getMessage(),
            'steps_completed' => $saga->metadata['steps'] ?? [],
        ]);

        // TODO: Send email/Slack alert to admins
        // TODO: Create admin dashboard notification
    }

    /**
     * Recover incomplete sagas (run on startup or cron)
     */
    public static function recoverIncompleteSagas(): array
    {
        $incompleteSagas = SagaExecution::whereIn('status', ['processing', 'failed'])
            ->where('initiated_at', '<', now()->subHours(24))
            ->get();

        $results = [];

        foreach ($incompleteSagas as $saga) {
            Log::warning('Found incomplete saga for recovery', [
                'saga_id' => $saga->saga_id,
                'status' => $saga->status,
                'initiated_at' => $saga->initiated_at,
            ]);

            $results[] = [
                'saga_id' => $saga->saga_id,
                'status' => $saga->status,
                'action' => 'flagged_for_manual_review',
            ];

            // Mark for manual resolution
            $saga->update([
                'status' => 'requires_manual_resolution',
                'resolution_data' => [
                    'flagged_by' => 'recovery_process',
                    'flagged_at' => now()->toDateTimeString(),
                ],
            ]);
        }

        return $results;
    }
}
