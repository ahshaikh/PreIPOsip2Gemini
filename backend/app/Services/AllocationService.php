<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-ATOMIC-INVENTORY | V-BUCKET-FILL-FIFO
 * V-DISPUTE-RISK-2026-006: Added RiskGuard integration
 *
 * Refactored to address Module 5 Audit Gaps:
 * 1. Atomic Locks: Uses lockForUpdate() to prevent race conditions during multi-batch allocation.
 * 2. Data Integrity: Enforces strict DB transactions for the "Bucket Fill" algorithm.
 * 3. Fractional Logic: Handles whole vs fractional shares at the engine level with automated refunds.
 * 4. Risk Guard: Blocked users cannot receive share allocations.
 */

namespace App\Services;

use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\AuditLog;
use App\Services\InventoryService;
use App\Services\WalletService;
use App\Services\InventoryConservationService;
use App\Services\RiskGuardService;
use App\Enums\InvestmentSource;
use App\Enums\TransactionType;
use App\Enums\ReversalSource;
use App\Exceptions\RiskBlockedException;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    protected $inventoryService;
    protected $walletService;
    protected $conservationService;
    protected $riskGuard;

    public function __construct(
        InventoryService $inventoryService,
        WalletService $walletService,
        InventoryConservationService $conservationService,
        RiskGuardService $riskGuard
    ) {
        $this->inventoryService = $inventoryService;
        $this->walletService = $walletService;
        $this->conservationService = $conservationService;
        $this->riskGuard = $riskGuard;
    }

    /**
     * Allocate shares for specific product with conservation guarantees
     *
     * [ORCHESTRATION-COMPATIBLE]: Used by saga-based allocation
     * [CONSERVATION-ENFORCED]: Integrates with InventoryConservationService
     * [RISK-GUARDED]: Blocked users cannot receive allocations (V-DISPUTE-RISK-2026-006)
     * [V-AUDIT-FIX-2026]: Explicit pre-allocation balance check with typed exception
     * [V-WAVE2-STRICT]: Payment is REQUIRED - financial systems cannot guess payment context
     *
     * @param \App\Models\User $user
     * @param \App\Models\Product $product
     * @param float $amount
     * @param \App\Models\Investment $investment
     * @param \App\Models\Payment $payment Payment is REQUIRED for user_investments.payment_id FK
     * @param string $source
     * @param bool $allowFractional
     * @return array Result summary including success status and refund_due
     * @throws RiskBlockedException If user is risk-blocked
     * @throws InsufficientInventoryException If insufficient inventory
     * @throws \InvalidArgumentException If payment is null
     */
    public function allocateShares($user, $product, float $amount, $investment, Payment $payment, string $source = 'investment', bool $allowFractional = true): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'refund_due' => 0];
        }

        // V-DISPUTE-RISK-2026-006: Risk Guard - Block allocations to risk-blocked users
        $this->riskGuard->assertUserCanInvest($user, 'share_allocation', [
            'product_id' => $product->id,
            'amount' => $amount,
            'investment_id' => $investment->id ?? null,
            'source' => $source,
        ]);

        // V-AUDIT-FIX-2026: Explicit pre-allocation balance check
        $this->assertSufficientInventory($product, $amount, $source);

        // V-ORCHESTRATION-2026: Removed DB::transaction. Caller (Orchestrator) must wrap this.

        // CONSERVATION CHECK
        $canAllocate = $this->conservationService->canAllocate($product, $amount);
        if (!$canAllocate['can_allocate']) {
            throw InsufficientInventoryException::forProduct($product, $amount, $canAllocate['available'] ?? 0, $source);
        }

        // V-ORCHESTRATION-2026: Caller should have already locked the inventory.
        // We still fetch the batches here but without lockForUpdate.
        $batches = $this->conservationService->getInventoryForAllocation($product);

        $available = $batches->sum('value_remaining');
        if ($available < $amount) {
            throw InsufficientInventoryException::forProduct($product, $amount, $available, $source);
        }

        $remainingNeeded = $amount;
        $totalRefundDue = 0;

        foreach ($batches as $batch) {
            if ($remainingNeeded <= 0.01) break;

            $amountToTake = min($batch->value_remaining, $remainingNeeded);
            if ($amountToTake < 0.01) continue;

            $unitsCalculated = $amountToTake / $product->face_value_per_unit;

            if (!$allowFractional) {
                $unitsToAllocate = floor($unitsCalculated);
                $actualAmountToDeduct = $unitsToAllocate * $product->face_value_per_unit;
                $totalRefundDue += ($amountToTake - $actualAmountToDeduct);

                if ($unitsToAllocate < 1) continue;
                $amountToTake = $actualAmountToDeduct;
            } else {
                $unitsToAllocate = $unitsCalculated;
            }

            $userInvestment = UserInvestment::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
                'bulk_purchase_id' => $batch->id,
                'units_allocated' => $unitsToAllocate,
                'value_allocated' => $amountToTake,
                'source' => $source,
                'status' => 'active',
                'is_reversed' => false,
            ]);

            $batch->decrement('value_remaining', $amountToTake);
            $remainingNeeded -= $amountToTake;
        }

        return ['success' => true, 'refund_due' => $totalRefundDue];
    }

    /**
     * Legacy: Allocate shares using Payment object (FIFO algorithm)
     *
     * [AUDIT FIX]: Added lockForUpdate() to prevent two users from claiming the same fragmented batch.
     * [RISK-GUARDED]: Blocked users cannot receive allocations (V-DISPUTE-RISK-2026-006)
     * [DEPRECATED]: Use allocateShares(user, product, amount, investment, ...) for new code
     * [V-AUDIT-FIX-2026]: Now throws InsufficientInventoryException for consistency
     *
     * V-ORCHESTRATION-2026: Supports two call patterns:
     * 1. Legacy: allocateSharesLegacy($payment, $amount, $source) - acquires locks internally
     * 2. Orchestrator: allocateSharesLegacy($payment, $lockedBatches, $amount) - uses pre-locked batches
     *
     * @param Payment $payment
     * @param \Illuminate\Support\Collection|float $batchesOrValue Pre-locked batches (Collection) or amount (float)
     * @param float|string|null $valueOrSource Amount when batches provided, or source when amount provided
     * @return array Result summary including success status and refund_due
     * @throws RiskBlockedException If user is risk-blocked
     * @throws InsufficientInventoryException If insufficient global inventory
     */
    public function allocateSharesLegacy(
        Payment $payment,
        \Illuminate\Support\Collection|float $batchesOrValue,
        float|string|null $valueOrSource = null
    ): array
    {
        // V-ORCHESTRATION-2026: Detect call pattern
        $orchestratorMode = $batchesOrValue instanceof \Illuminate\Support\Collection;

        if ($orchestratorMode) {
            // Pattern 2: Orchestrator - batches already locked
            $batches = $batchesOrValue;
            $totalInvestmentValue = (float) $valueOrSource;
            $source = InvestmentSource::INVESTMENT_AND_BONUS->value;
        } else {
            // Pattern 1: Legacy - float amount passed
            $totalInvestmentValue = (float) $batchesOrValue;
            $source = is_string($valueOrSource) ? $valueOrSource : InvestmentSource::INVESTMENT_AND_BONUS->value;
            $batches = null; // Will be fetched and locked below
        }

        if ($totalInvestmentValue <= 0) {
            return ['success' => false, 'refund_due' => 0];
        }

        $user = $payment->user;

        // V-DISPUTE-RISK-2026-006: Risk Guard - Block allocations to risk-blocked users
        // This check happens BEFORE any DB transaction to prevent partial mutations
        $this->riskGuard->assertUserCanInvest($user, 'share_allocation_legacy', [
            'payment_id' => $payment->id,
            'amount' => $totalInvestmentValue,
        ]);

        $allowFractional = setting('allow_fractional_shares', true);

        // V-ORCHESTRATION-2026: Core allocation logic extracted for reuse
        $doAllocation = function ($batches) use ($user, $payment, $totalInvestmentValue, $source, $allowFractional) {
            $available = $batches->sum('value_remaining');
            if ($available < $totalInvestmentValue) {
                throw InsufficientInventoryException::forGlobalInventory($totalInvestmentValue, $available, 'legacy_allocation');
            }

            $remainingNeeded = $totalInvestmentValue;
            $totalRefundDue = 0;

            foreach ($batches as $batch) {
                if ($remainingNeeded <= 0.01) break;

                $amountToTake = min($batch->value_remaining, $remainingNeeded);
                if ($amountToTake < 0.01) continue;

                $product = $batch->product;

                // Calculate Units
                $unitsCalculated = $amountToTake / $product->face_value_per_unit;

                // [AUDIT FIX]: Precision Gating - Handle Non-Fractional Assets
                if (!$allowFractional) {
                    $unitsToAllocate = floor($unitsCalculated);
                    $actualAmountToDeduct = $unitsToAllocate * $product->face_value_per_unit;
                    $totalRefundDue += ($amountToTake - $actualAmountToDeduct);

                    if ($unitsToAllocate < 1) continue;
                    $amountToTake = $actualAmountToDeduct;
                } else {
                    $unitsToAllocate = $unitsCalculated;
                }

                // 2. Create the Investment Record (Linked to specific batch)
                // [P0.1 FIX]: Added subscription_id to enable querying investments by subscription
                $userInvestment = UserInvestment::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'payment_id' => $payment->id,
                    'subscription_id' => $payment->subscription_id, // [P0.1 FIX]: Link to subscription
                    'bulk_purchase_id' => $batch->id,
                    'units_allocated' => $unitsToAllocate,
                    'value_allocated' => $amountToTake,
                    'source' => $source,
                    'status' => 'active'
                ]);

                // 3. Atomic Deduction
                $batch->decrement('value_remaining', $amountToTake);
                $remainingNeeded -= $amountToTake;

                // 4. DB Audit Record (inside transaction - only persists on success)
                AuditLog::create([
                    'actor_id' => $user->id,
                    'actor_type' => 'user',
                    'action' => 'share_allocation',
                    'module' => 'investments',
                    'target_type' => 'user_investment',
                    'target_id' => $userInvestment->id,
                    'description' => "Allocated {$unitsToAllocate} units ({$amountToTake} value) from bulk purchase #{$batch->id}",
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'product_id' => $product->id,
                        'bulk_purchase_id' => $batch->id,
                        'value_allocated' => $amountToTake,
                        'units_allocated' => $unitsToAllocate,
                        'value_remaining' => $batch->value_remaining,
                    ],
                ]);
            }

            return ['success' => true, 'refund_due' => $totalRefundDue];
        };

        // V-ORCHESTRATION-2026: Execute based on mode
        if ($orchestratorMode) {
            // Orchestrator mode: batches already locked, no new transaction needed
            return $doAllocation($batches);
        } else {
            // Legacy mode: wrap in transaction with locking
            return DB::transaction(function () use ($doAllocation) {
                $batches = BulkPurchase::where('value_remaining', '>', 0)
                    ->whereHas('product', fn($q) => $q->whereIn('status', ['approved', 'active']))
                    ->orderBy('purchase_date', 'asc')
                    ->lockForUpdate()
                    ->get();

                return $doAllocation($batches);
            });
        }
    }

    /**
     * @deprecated V-AUDIT-FIX-2026: No longer auto-called. allocateSharesLegacy now throws exception.
     * Kept for manual flagging if needed.
     */
    private function flagFailedAllocation($payment, $value) {
        Log::critical("INVENTORY DEPLETION: Failed allocation for Payment #{$payment->id}");
        $payment->update([
            'is_flagged' => true,
            'flag_reason' => 'Insufficient Inventory to fulfill order.'
        ]);
    }

    /**
     * Reverse allocations for an investment (with conservation verification)
     */
    public function reverseAllocation($investment, string $reason): void
    {
        // Get all non-reversed user investments for this investment
        $userInvestments = UserInvestment::where('investment_id', $investment->id)
            ->where('is_reversed', false)
            ->get();

        if ($userInvestments->isEmpty()) {
            Log::warning("REVERSAL SKIPPED: No active allocations found", [
                'investment_id' => $investment->id,
            ]);
            return;
        }

        foreach ($userInvestments as $userInvestment) {
            // Lock the bulk purchase and restore inventory
            $bulkPurchase = $userInvestment->bulkPurchase; // Caller (orchestrator) should have locked this
            if ($bulkPurchase) {
                $bulkPurchase->increment('value_remaining', $userInvestment->value_allocated);

                Log::info("REVERSAL: Inventory restored", [
                    'bulk_purchase_id' => $bulkPurchase->id,
                    'amount_restored' => $userInvestment->value_allocated,
                ]);
            }

            $userInvestment->update([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_source' => ReversalSource::REFUND->value,
            ]);
        }
    }

    /**
     * Legacy: Reverse allocations using Payment object
     *
     * V-CHARGEBACK-SEMANTICS-2026: Now accepts ReversalSource enum for explicit semantics.
     *
     * FINANCIAL CONTRACT:
     * This method is SHARE-ONLY. It returns shares to inventory and marks
     * investments as reversed. It does NOT mutate wallet.
     *
     * Wallet implications are handled by the CALLING SERVICE:
     * - handleRefundProcessed(): Credits wallet AFTER calling this method
     * - handleChargebackConfirmed(): Debits wallet AFTER calling this method
     *
     * V-ORCHESTRATION-2026: Supports two call patterns:
     * 1. Legacy: reverseAllocationLegacy($payment, $reason, $source) - uses internal transaction
     * 2. Orchestrator: reverseAllocationLegacy($payment, $investments, $lockedBatches, $reason, $source)
     *
     * @param Payment $payment The payment whose investments to reverse
     * @param \Illuminate\Support\Collection|string $investmentsOrReason Pre-locked investments or reason string
     * @param \Illuminate\Support\Collection|ReversalSource|null $batchesOrSource Pre-locked batches or source enum
     * @param string|ReversalSource|null $reasonOrSource Reason (orchestrator) or source (legacy)
     * @param ReversalSource|null $source Explicit reversal source (orchestrator only)
     */
    public function reverseAllocationLegacy(
        Payment $payment,
        \Illuminate\Support\Collection|string $investmentsOrReason,
        \Illuminate\Support\Collection|ReversalSource|null $batchesOrSource = null,
        string|ReversalSource|null $reasonOrSource = null,
        ?ReversalSource $source = null
    ): void {
        // V-ORCHESTRATION-2026: Detect call pattern
        $orchestratorMode = $investmentsOrReason instanceof \Illuminate\Support\Collection;

        if ($orchestratorMode) {
            $investments = $investmentsOrReason;
            $lockedBatches = $batchesOrSource instanceof \Illuminate\Support\Collection ? $batchesOrSource : collect();
            $reason = is_string($reasonOrSource) ? $reasonOrSource : 'Reversal via orchestrator';
            $reversalSource = $source ?? ($reasonOrSource instanceof ReversalSource ? $reasonOrSource : ReversalSource::REFUND);
            
            $this->executeReverseAllocationInternal($investments, $lockedBatches, $reason, $reversalSource);
        } else {
            $reason = $investmentsOrReason;
            $reversalSource = $batchesOrSource instanceof ReversalSource ? $batchesOrSource : ReversalSource::REFUND;
            
            DB::transaction(function () use ($payment, $reason, $reversalSource) {
                $investments = $payment->investments()->where('is_reversed', false)->lockForUpdate()->get();
                $this->executeReverseAllocationInternal($investments, null, $reason, $reversalSource);
            });
        }
    }

    protected function executeReverseAllocationInternal($investments, $lockedBatches, $reason, $reversalSource)
    {
        foreach ($investments as $investment) {
            if ($lockedBatches !== null && isset($lockedBatches[$investment->bulk_purchase_id])) {
                $purchase = $lockedBatches[$investment->bulk_purchase_id];
            } else {
                // If not in orchestrator mode, acquire lock manually
                $purchase = $investment->bulkPurchase()->lockForUpdate()->first();
            }

            if ($purchase) {
                $purchase->increment('value_remaining', $investment->value_allocated);
            }

            $investment->update([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_source' => $reversalSource->value,
            ]);
        }
    }

    /**
     * V-AUDIT-FIX-2026: Explicit pre-allocation balance check
     *
     * Called BEFORE transaction to provide clear exception typing.
     * This is a fast check using sum query (not locked).
     *
     * @param \App\Models\Product $product
     * @param float $amount
     * @param string $source
     * @throws InsufficientInventoryException
     */
    protected function assertSufficientInventory($product, float $amount, string $source): void
    {
        $available = BulkPurchase::where('product_id', $product->id)
            ->where('value_remaining', '>', 0)
            ->sum('value_remaining');

        if ($available < $amount) {
            Log::warning("PRE-ALLOCATION CHECK: Insufficient inventory", [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'requested' => $amount,
                'available' => $available,
                'source' => $source,
            ]);

            throw InsufficientInventoryException::forProduct($product, $amount, $available, $source);
        }
    }
}