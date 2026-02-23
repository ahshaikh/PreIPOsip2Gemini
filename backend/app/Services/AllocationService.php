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
use App\Models\ActivityLog;
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
     * [V-WAVE2-DOCTRINE]: Added payment parameter for user_investments.payment_id requirement
     *
     * @param \App\Models\User $user
     * @param \App\Models\Product $product
     * @param float $amount
     * @param \App\Models\Investment $investment
     * @param \App\Models\Payment|null $payment Payment for FK requirement (optional for backward compat)
     * @param string $source
     * @param bool $allowFractional
     * @return bool Success status
     * @throws RiskBlockedException If user is risk-blocked
     * @throws InsufficientInventoryException If insufficient inventory
     */
    public function allocateShares($user, $product, float $amount, $investment, $payment = null, string $source = 'investment', bool $allowFractional = true): bool
    {
        if ($amount <= 0) {
            return false;
        }

        // V-DISPUTE-RISK-2026-006: Risk Guard - Block allocations to risk-blocked users
        // This check happens BEFORE any DB transaction to prevent partial mutations
        $this->riskGuard->assertUserCanInvest($user, 'share_allocation', [
            'product_id' => $product->id,
            'amount' => $amount,
            'investment_id' => $investment->id ?? null,
            'source' => $source,
        ]);

        // V-AUDIT-FIX-2026: Explicit pre-allocation balance check BEFORE transaction
        // This provides clear exception typing for callers
        $this->assertSufficientInventory($product, $amount, $source);

        return DB::transaction(function () use ($user, $product, $amount, $investment, $payment, $source, $allowFractional) {

            // CONSERVATION CHECK: Verify allocation won't violate conservation law
            $canAllocate = $this->conservationService->canAllocate($product, $amount);
            if (!$canAllocate['can_allocate']) {
                Log::warning("ALLOCATION BLOCKED: Conservation check failed", $canAllocate);
                // V-AUDIT-FIX-2026: Throw typed exception instead of returning false
                throw InsufficientInventoryException::forProduct(
                    $product,
                    $amount,
                    $canAllocate['available'] ?? 0,
                    $source
                );
            }

            // Lock inventory for this product (prevents concurrent allocation)
            $batches = $this->conservationService->lockInventoryForAllocation($product);

            $available = $batches->sum('value_remaining');
            if ($available < $amount) {
                Log::warning("ALLOCATION BLOCKED: Insufficient inventory", [
                    'product_id' => $product->id,
                    'requested' => $amount,
                    'available' => $available,
                ]);
                // V-AUDIT-FIX-2026: Throw typed exception instead of returning false
                throw InsufficientInventoryException::forProduct($product, $amount, $available, $source);
            }

            $remainingNeeded = $amount;
            $totalRefundDue = 0;

            foreach ($batches as $batch) {
                if ($remainingNeeded <= 0.01) break;

                $amountToTake = min($batch->value_remaining, $remainingNeeded);
                if ($amountToTake < 0.01) continue;

                // Calculate Units
                $unitsCalculated = $amountToTake / $product->face_value_per_unit;

                // Handle fractional shares
                if (!$allowFractional) {
                    $unitsToAllocate = floor($unitsCalculated);
                    $actualAmountToDeduct = $unitsToAllocate * $product->face_value_per_unit;
                    $totalRefundDue += ($amountToTake - $actualAmountToDeduct);

                    if ($unitsToAllocate < 1) continue;
                    $amountToTake = $actualAmountToDeduct;
                } else {
                    $unitsToAllocate = $unitsCalculated;
                }

                // Create UserInvestment record
                // V-WAVE2-DOCTRINE: Include payment_id and subscription_id for FK requirements
                $userInvestment = UserInvestment::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'payment_id' => $payment?->id ?? $investment->subscription?->payments()->latest()->first()?->id,
                    'subscription_id' => $investment->subscription_id,
                    'bulk_purchase_id' => $batch->id,
                    'units_allocated' => $unitsToAllocate,
                    'value_allocated' => $amountToTake,
                    'source' => $source,
                    'status' => 'active',
                    'is_reversed' => false,
                ]);

                // Atomic decrement of inventory
                $batch->decrement('value_remaining', $amountToTake);
                $remainingNeeded -= $amountToTake;
            }

            // Handle fractional refund if needed
            if (!$allowFractional && $totalRefundDue > 0) {
                $this->walletService->deposit(
                    $user,
                    $totalRefundDue,
                    TransactionType::REFUND->value,
                    "Refund for fractional remainder (Investment #{$investment->id})",
                    'investment',
                    $investment->id
                );
            }

            // CONSERVATION VERIFICATION: Ensure conservation law still holds
            $verificationResult = $this->conservationService->verifyConservation($product);
            if (!$verificationResult['is_conserved']) {
                // This should NEVER happen due to locking, but check anyway
                Log::critical("CRITICAL: Allocation violated conservation law", $verificationResult);
                throw new \Exception('Allocation violated inventory conservation law');
            }

            return true;
        });
    }

    /**
     * Legacy: Allocate shares using Payment object (FIFO algorithm)
     *
     * [AUDIT FIX]: Added lockForUpdate() to prevent two users from claiming the same fragmented batch.
     * [RISK-GUARDED]: Blocked users cannot receive allocations (V-DISPUTE-RISK-2026-006)
     * [DEPRECATED]: Use allocateShares(user, product, amount, investment, ...) for new code
     * [V-AUDIT-FIX-2026]: Now throws InsufficientInventoryException for consistency
     *
     * @throws RiskBlockedException If user is risk-blocked
     * @throws InsufficientInventoryException If insufficient global inventory
     */
    public function allocateSharesLegacy(Payment $payment, float $totalInvestmentValue, string $source = null)
    {
        if ($totalInvestmentValue <= 0) {
            return;
        }

        $user = $payment->user;

        // V-DISPUTE-RISK-2026-006: Risk Guard - Block allocations to risk-blocked users
        // This check happens BEFORE any DB transaction to prevent partial mutations
        $this->riskGuard->assertUserCanInvest($user, 'share_allocation_legacy', [
            'payment_id' => $payment->id,
            'amount' => $totalInvestmentValue,
        ]);

        $source = $source ?? InvestmentSource::INVESTMENT_AND_BONUS->value;
        $allowFractional = setting('allow_fractional_shares', true);

        // [AUDIT FIX]: Wrap in transaction to ensure either ALL batches are updated or NONE.
        DB::transaction(function () use ($user, $payment, $totalInvestmentValue, $source, $allowFractional) {

            // 1. Fetch available inventory batches oldest first (FIFO)
            // [AUDIT FIX]: lockForUpdate() prevents other requests from reading these rows until commit.
            // V-WAVE2-DOCTRINE: Accept products in valid states from state machine.
            // State machine: draft → submitted → approved → locked
            // 'active' is legacy status, 'approved' is from current state machine.
            // Accept both for backward compatibility.
            $batches = BulkPurchase::where('value_remaining', '>', 0)
                ->whereHas('product', fn($q) => $q->whereIn('status', ['active', 'approved', 'draft']))
                ->orderBy('purchase_date', 'asc')
                ->lockForUpdate()
                ->get();

            $available = $batches->sum('value_remaining');
            if ($available < $totalInvestmentValue) {
                // V-AUDIT-FIX-2026: Throw typed exception instead of return false
                Log::warning("LEGACY ALLOCATION BLOCKED: Insufficient global inventory", [
                    'payment_id' => $payment->id,
                    'requested' => $totalInvestmentValue,
                    'available' => $available,
                ]);
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
                UserInvestment::create([
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
            }

            // [AUDIT FIX]: Automated Fractional Refund
            if (!$allowFractional && $totalRefundDue > 0) {
                $this->walletService->deposit(
                    $user,
                    (string) $totalRefundDue,
                    TransactionType::REFUND->value,
                    "Refund for fractional remainder (Payment #{$payment->id})",
                    $payment
                );
            }
        });
        // V-AUDIT-FIX-2026: Removed $allocationSuccess check - now throws exception on failure
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
     *
     * [ORCHESTRATION-COMPATIBLE]: Used by saga compensation
     * [CONSERVATION-ENFORCED]: Verifies conservation after reversal
     *
     * @param \App\Models\Investment $investment
     * @param string $reason
     * @return void
     */
    public function reverseAllocation($investment, string $reason): void
    {
        DB::transaction(function () use ($investment, $reason) {
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
                $bulkPurchase = $userInvestment->bulkPurchase()->lockForUpdate()->first();
                if ($bulkPurchase) {
                    $bulkPurchase->increment('value_remaining', $userInvestment->value_allocated);

                    Log::info("REVERSAL: Inventory restored", [
                        'bulk_purchase_id' => $bulkPurchase->id,
                        'amount_restored' => $userInvestment->value_allocated,
                    ]);
                }

                // V-CHARGEBACK-SEMANTICS-2026: Mark as reversed with explicit source
                // Default to REFUND for non-legacy reversal method
                $userInvestment->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason,
                    'reversal_source' => ReversalSource::REFUND->value,
                ]);
            }

            // CONSERVATION VERIFICATION: Ensure conservation holds after reversal
            $product = $userInvestments->first()->product;
            $verificationResult = $this->conservationService->verifyConservation($product);

            if (!$verificationResult['is_conserved']) {
                Log::critical("CRITICAL: Reversal violated conservation law", $verificationResult);
                // Don't throw - compensation must complete even if verification fails
                // Alert will be raised by conservation service
            }

            Log::info("REVERSAL COMPLETE", [
                'investment_id' => $investment->id,
                'user_investments_reversed' => $userInvestments->count(),
                'total_value_restored' => $userInvestments->sum('value_allocated'),
            ]);
        });
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
     * @param Payment $payment The payment whose investments to reverse
     * @param string $reason Human-readable reason for audit trail
     * @param ReversalSource|null $source Explicit reversal source (default: REFUND for backward compat)
     */
    public function reverseAllocationLegacy(
        Payment $payment,
        string $reason,
        ?ReversalSource $source = null
    ): void {
        // Default to REFUND for backward compatibility with existing callers
        $source = $source ?? ReversalSource::REFUND;

        // NOTE: No DB::transaction here - caller is responsible for transaction context
        // This method is typically called from handleChargebackConfirmed which already wraps in transaction
        $investments = $payment->investments()->where('is_reversed', false)->get();

        foreach ($investments as $investment) {
            $purchase = $investment->bulkPurchase()->lockForUpdate()->first();
            if ($purchase) {
                $purchase->increment('value_remaining', $investment->value_allocated);
            }

            // V-CHARGEBACK-SEMANTICS-2026: Store explicit reversal source
            $investment->update([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_source' => $source->value,
            ]);
        }

        \Illuminate\Support\Facades\Log::info('ALLOCATION REVERSAL COMPLETE (share-only)', [
            'payment_id' => $payment->id,
            'investments_reversed' => $investments->count(),
            'total_value_restored' => $investments->sum('value_allocated'),
            'reversal_source' => $source->value,
            'note' => 'Wallet mutations handled by calling service',
        ]);
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