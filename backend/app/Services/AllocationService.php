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
use App\Enums\InvestmentSource; 
use App\Enums\TransactionType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    protected $inventoryService;
    protected $walletService;

    public function __construct(InventoryService $inventoryService, WalletService $walletService)
    {
        $this->inventoryService = $inventoryService;
        $this->walletService = $walletService;
    }

    /**
     * Allocate shares using the "Bucket Fill" (FIFO) algorithm.
     * [AUDIT FIX]: Added lockForUpdate() to prevent two users from claiming the same fragmented batch.
     */
    public function allocateShares(Payment $payment, float $totalInvestmentValue, string $source = null)
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
     * Reverse allocations (Refunds/Failures)
     */
    public function reverseAllocation(Payment $payment, string $reason): void
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