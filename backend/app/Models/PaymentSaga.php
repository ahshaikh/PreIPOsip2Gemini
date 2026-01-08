<?php
/**
 * FIX 44, 45: Payment Saga Execution Tracking & Rollback Mechanism
 *
 * Implements the Saga pattern for complex multi-step payment operations.
 *
 * A payment saga typically involves:
 * 1. Reserve funds in wallet (lock balance)
 * 2. Process payment gateway (Razorpay/Stripe)
 * 3. Allocate inventory/shares
 * 4. Create bonus transactions
 * 5. Send notifications
 *
 * If any step fails, the saga tracks completed steps and rolls them back in reverse order.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSaga extends Model
{
    protected $fillable = [
        'payment_id',
        'user_id',
        'saga_type',            // payment_processing, refund, allocation
        'status',               // pending, in_progress, completed, failed, rolled_back
        'current_step',         // Current step being executed
        'total_steps',          // Total number of steps
        'completed_steps',      // JSON array of completed step names
        'failed_step',          // Name of step that failed
        'failure_reason',       // Error message from failed step
        'rollback_steps',       // JSON array of rollback steps executed
        'saga_data',            // JSON data passed between steps
        'started_at',
        'completed_at',
        'failed_at',
        'rolled_back_at',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'rollback_steps' => 'array',
        'saga_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    /**
     * Saga steps cannot be modified once created (append-only log)
     */
    protected static function booted()
    {
        static::updating(function ($saga) {
            // Allow status updates and step progression, but prevent data tampering
            $allowedUpdates = ['status', 'current_step', 'completed_steps', 'failed_step',
                             'failure_reason', 'rollback_steps', 'completed_at', 'failed_at', 'rolled_back_at'];

            $changedFields = array_keys($saga->getDirty());
            $invalidChanges = array_diff($changedFields, $allowedUpdates);

            if (!empty($invalidChanges)) {
                \Log::warning('Attempt to modify protected saga fields', [
                    'saga_id' => $saga->id,
                    'invalid_changes' => $invalidChanges,
                ]);

                throw new \RuntimeException(
                    "Cannot modify protected saga fields: " . implode(', ', $invalidChanges)
                );
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- SAGA EXECUTION METHODS ---

    /**
     * FIX 44: Mark step as completed
     */
    public function completeStep(string $stepName, array $stepData = []): void
    {
        $completedSteps = $this->completed_steps ?? [];
        $completedSteps[] = [
            'step' => $stepName,
            'completed_at' => now()->toISOString(),
            'data' => $stepData,
        ];

        $this->update([
            'completed_steps' => $completedSteps,
            'current_step' => $stepName,
        ]);

        \Log::info('Saga step completed', [
            'saga_id' => $this->id,
            'step' => $stepName,
            'total_completed' => count($completedSteps),
        ]);
    }

    /**
     * FIX 44: Mark saga as completed
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        \Log::info('Saga completed successfully', [
            'saga_id' => $this->id,
            'payment_id' => $this->payment_id,
            'steps_completed' => count($this->completed_steps ?? []),
        ]);
    }

    /**
     * FIX 44: Mark saga as failed
     */
    public function fail(string $failedStep, string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failed_step' => $failedStep,
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);

        \Log::error('Saga failed', [
            'saga_id' => $this->id,
            'payment_id' => $this->payment_id,
            'failed_step' => $failedStep,
            'reason' => $reason,
        ]);
    }

    /**
     * FIX 45: Execute rollback for failed saga
     * Rolls back completed steps in reverse order
     */
    public function rollback(): void
    {
        if ($this->status !== 'failed') {
            throw new \RuntimeException("Cannot rollback saga that is not failed");
        }

        \Log::info('Starting saga rollback', [
            'saga_id' => $this->id,
            'completed_steps' => $this->completed_steps,
        ]);

        $this->update(['status' => 'rolling_back']);

        $completedSteps = $this->completed_steps ?? [];
        $rollbackSteps = [];

        // Execute rollback in reverse order (LIFO)
        foreach (array_reverse($completedSteps) as $step) {
            $stepName = $step['step'];

            try {
                $this->rollbackStep($stepName, $step['data'] ?? []);

                $rollbackSteps[] = [
                    'step' => $stepName,
                    'rolled_back_at' => now()->toISOString(),
                    'success' => true,
                ];

                \Log::info('Saga step rolled back', [
                    'saga_id' => $this->id,
                    'step' => $stepName,
                ]);
            } catch (\Exception $e) {
                $rollbackSteps[] = [
                    'step' => $stepName,
                    'rolled_back_at' => now()->toISOString(),
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                \Log::error('Saga step rollback failed', [
                    'saga_id' => $this->id,
                    'step' => $stepName,
                    'error' => $e->getMessage(),
                ]);

                // Continue with other rollbacks even if one fails
            }
        }

        $this->update([
            'status' => 'rolled_back',
            'rollback_steps' => $rollbackSteps,
            'rolled_back_at' => now(),
        ]);

        \Log::info('Saga rollback completed', [
            'saga_id' => $this->id,
            'steps_rolled_back' => count($rollbackSteps),
        ]);
    }

    /**
     * FIX 45: Rollback a specific step
     * Override this in saga-specific implementations or use event-driven rollback
     */
    protected function rollbackStep(string $stepName, array $stepData): void
    {
        // Dispatch rollback event that listeners can handle
        event(new \App\Events\SagaStepRollback($this, $stepName, $stepData));

        // Default rollback logic based on step name
        switch ($stepName) {
            case 'lock_funds':
                $this->rollbackLockFunds($stepData);
                break;

            case 'allocate_inventory':
                $this->rollbackAllocateInventory($stepData);
                break;

            case 'create_investments':
                $this->rollbackCreateInvestments($stepData);
                break;

            case 'credit_bonus':
                $this->rollbackCreditBonus($stepData);
                break;

            default:
                \Log::warning('No rollback handler for step', [
                    'saga_id' => $this->id,
                    'step' => $stepName,
                ]);
        }
    }

    /**
     * FIX 45: Rollback fund locking
     */
    protected function rollbackLockFunds(array $stepData): void
    {
        $walletService = app(\App\Services\WalletService::class);
        $amount = $stepData['amount'] ?? 0;

        if ($amount > 0) {
            $walletService->unlockFunds(
                $this->user,
                $amount,
                "Rollback saga #{$this->id} - unlock funds",
                $this->payment
            );
        }
    }

    /**
     * FIX 45: Rollback inventory allocation
     */
    protected function rollbackAllocateInventory(array $stepData): void
    {
        // Mark allocations as reversed and restore inventory
        $investmentIds = $stepData['investment_ids'] ?? [];

        foreach ($investmentIds as $investmentId) {
            $investment = \App\Models\UserInvestment::find($investmentId);

            if ($investment && !$investment->is_reversed) {
                // Restore inventory to bulk purchase
                $bulkPurchase = $investment->bulkPurchase;
                if ($bulkPurchase) {
                    $bulkPurchase->increment('value_remaining', $investment->value_allocated);
                }

                // Mark investment as reversed
                $investment->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => "Saga rollback #{$this->id}",
                ]);
            }
        }
    }

    /**
     * FIX 45: Rollback investment records
     */
    protected function rollbackCreateInvestments(array $stepData): void
    {
        // Same as rollbackAllocateInventory for now
        $this->rollbackAllocateInventory($stepData);
    }

    /**
     * FIX 45: Rollback bonus credits
     */
    protected function rollbackCreditBonus(array $stepData): void
    {
        $bonusIds = $stepData['bonus_ids'] ?? [];

        foreach ($bonusIds as $bonusId) {
            $bonus = \App\Models\BonusTransaction::find($bonusId);

            if ($bonus) {
                // Create reversal bonus transaction
                $bonus->reverse("Saga rollback #{$this->id}");
            }
        }
    }

    // --- HELPER METHODS ---

    /**
     * Check if saga is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    /**
     * Check if saga can be rolled back
     */
    public function canRollback(): bool
    {
        return $this->status === 'failed' && !empty($this->completed_steps);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        $completedCount = count($this->completed_steps ?? []);
        return (int) (($completedCount / $this->total_steps) * 100);
    }

    /**
     * Create a new saga for payment processing
     */
    public static function createForPayment(Payment $payment, array $sagaData = []): self
    {
        return self::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'saga_type' => 'payment_processing',
            'status' => 'pending',
            'current_step' => null,
            'total_steps' => 5, // lock_funds, process_gateway, allocate, bonus, notify
            'completed_steps' => [],
            'saga_data' => $sagaData,
            'started_at' => now(),
        ]);
    }
}
