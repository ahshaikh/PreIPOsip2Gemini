<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-ATOMIC-INVENTORY | V-BUCKET-FILL-FIFO
 * Refactored to address Module 5 Audit Gaps:
 * 1. Atomic Locks: Uses lockForUpdate() to prevent race conditions during multi-batch allocation.
 * 2. Data Integrity: Enforces strict DB transactions for the "Bucket Fill" algorithm.
 * 3. Fractional Logic: Handles whole vs fractional shares at the engine level with automated refunds.
 */

namespace App\Services;

use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\ActivityLog;
use App\Services\InventoryService;
use App\Services\WalletService;
use App\Services\InventoryConservationService;
use App\Enums\InvestmentSource;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    protected $inventoryService;
    protected $walletService;
    protected $conservationService;

    public function __construct(
        InventoryService $inventoryService,
        WalletService $walletService,
        InventoryConservationService $conservationService
    ) {
        $this->inventoryService = $inventoryService;
        $this->walletService = $walletService;
        $this->conservationService = $conservationService;
    }

    /**
     * Allocate shares for specific product with conservation guarantees
     *
     * [ORCHESTRATION-COMPATIBLE]: Used by saga-based allocation
     * [CONSERVATION-ENFORCED]: Integrates with InventoryConservationService
     *
     * @param \App\Models\User $user
     * @param \App\Models\Product $product
     * @param float $amount
     * @param \App\Models\Investment $investment
     * @param string $source
     * @param bool $allowFractional
     * @return bool Success status
     */
    public function allocateShares($user, $product, float $amount, $investment, string $source = 'investment', bool $allowFractional = true): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($user, $product, $amount, $investment, $source, $allowFractional) {

            // CONSERVATION CHECK: Verify allocation won't violate conservation law
            $canAllocate = $this->conservationService->canAllocate($product, $amount);
            if (!$canAllocate['can_allocate']) {
                Log::warning("ALLOCATION BLOCKED: Conservation check failed", $canAllocate);
                return false;
            }

            // Lock inventory for this product (prevents concurrent allocation)
            $batches = $this->conservationService->lockInventoryForAllocation($product);

            if ($batches->sum('value_remaining') < $amount) {
                Log::warning("ALLOCATION BLOCKED: Insufficient inventory", [
                    'product_id' => $product->id,
                    'requested' => $amount,
                    'available' => $batches->sum('value_remaining'),
                ]);
                return false;
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
                $userInvestment = UserInvestment::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'investment_id' => $investment->id,
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
     * [DEPRECATED]: Use allocateShares(user, product, amount, investment, ...) for new code
     */
    public function allocateSharesLegacy(Payment $payment, float $totalInvestmentValue, string $source = null)
    {
        if ($totalInvestmentValue <= 0) {
            return;
        }

        $user = $payment->user;
        $source = $source ?? InvestmentSource::INVESTMENT_AND_BONUS->value;
        $allowFractional = setting('allow_fractional_shares', true);

        // [AUDIT FIX]: Wrap in transaction to ensure either ALL batches are updated or NONE.
        $allocationSuccess = DB::transaction(function () use ($user, $payment, $totalInvestmentValue, $source, $allowFractional) {

            // 1. Fetch available inventory batches olders first (FIFO)
            // [AUDIT FIX]: lockForUpdate() prevents other requests from reading these rows until commit.
            $batches = BulkPurchase::where('value_remaining', '>', 0)
                ->whereHas('product', fn($q) => $q->where('status', 'active'))
                ->orderBy('purchase_date', 'asc')
                ->lockForUpdate() 
                ->get();

            if ($batches->sum('value_remaining') < $totalInvestmentValue) {
                return false; // Insufficient total global inventory
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

            return true;
        });

        if (!$allocationSuccess) {
            $this->flagFailedAllocation($payment, $totalInvestmentValue);
        }
    }

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

                // Mark as reversed
                $userInvestment->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason,
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
     * [DEPRECATED]: Use reverseAllocation(investment, reason) for new code
     */
    public function reverseAllocationLegacy(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $investments = $payment->investments()->where('is_reversed', false)->get();

            foreach ($investments as $investment) {
                $purchase = $investment->bulkPurchase()->lockForUpdate()->first();
                if ($purchase) {
                    $purchase->increment('value_remaining', $investment->value_allocated);
                }

                $investment->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason
                ]);
            }
        });
    }
}