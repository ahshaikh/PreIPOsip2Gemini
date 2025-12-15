<?php
// V-PHASE3-1730-084 (Created) | V-FIX-FRAGMENTATION (Gemini)
// Fixes: Critical "Fragmentation" bug where investments failed if no single batch was large enough.

namespace App\Services;

use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\ActivityLog;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Allocate shares from the bulk purchase pool to the user.
     * * CRITICAL FIX: Implements "Bucket Fill" algorithm.
     * Instead of looking for one batch >= investment value, we now fetch
     * all available batches and fill the order using FIFO (First-In-First-Out).
     */
    public function allocateShares(Payment $payment, float $totalInvestmentValue)
    {
        if ($totalInvestmentValue <= 0) {
            Log::info("Allocation skipped for Payment #{$payment->id}: Value is zero.");
            return;
        }

        $user = $payment->user;

        // Wrap the entire allocation process in a transaction to ensure data integrity
        $allocationSuccess = DB::transaction(function () use ($user, $payment, $totalInvestmentValue) {
            
            // 1. Fetch ALL candidates ordered by FIFO (Oldest purchase first)
            // We do NOT filter by '>= value' anymore to allow splitting across fragmented batches.
            $batches = BulkPurchase::where('value_remaining', '>', 0)
                ->whereHas('product', fn($q) => $q->where('status', 'active')) // Only allocate active products
                ->orderBy('purchase_date', 'asc')
                ->lockForUpdate() // Critical: Lock rows to prevent race conditions (double spending)
                ->get();

            // 2. Fail early if total global inventory is insufficient
            if ($batches->sum('value_remaining') < $totalInvestmentValue) {
                return false; // Insufficient total inventory
            }

            $remainingNeeded = $totalInvestmentValue;
            $productToCheck = null; // Store product reference for low stock check later

            foreach ($batches as $batch) {
                if ($remainingNeeded <= 0) break;

                // Logic: Take what is available in this batch, OR just what we need (whichever is smaller)
                $amountToTake = min($batch->value_remaining, $remainingNeeded);
                
                $product = $batch->product;
                $productToCheck = $product;
                
                // Prevent division by zero
                if ($product->face_value_per_unit <= 0) {
                    Log::error("Product {$product->id} has invalid face value. Skipping allocation.");
                    continue;
                }

                // Calculate Units: Value / Face Value
                $units = $amountToTake / $product->face_value_per_unit;

                // Create distinct Investment Record for this specific batch slice
                UserInvestment::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'payment_id' => $payment->id,
                    'bulk_purchase_id' => $batch->id,
                    'units_allocated' => $units,
                    'value_allocated' => $amountToTake,
                    'source' => 'investment_and_bonus',
                ]);

                // Deduct allocated amount from Inventory
                $batch->decrement('value_remaining', $amountToTake);
                
                $remainingNeeded -= $amountToTake;
            }

            // 3. Log Success
            ActivityLog::create([
                'user_id' => $user->id, 
                'action' => 'allocation_success', 
                'target_type' => Payment::class,
                'target_id' => $payment->id, 
                'description' => "Allocated shares for value ₹{$totalInvestmentValue} (Multi-batch split)",
            ]);

            // 4. Low Stock Check (Triggered after successful deduction)
            if ($productToCheck && $this->inventoryService->checkLowStock($productToCheck)) {
                Log::critical("LOW INVENTORY ALERT: Product {$productToCheck->name} (ID: {$productToCheck->id}) is now below 10% capacity.");
            }
            
            return true;
        });

        // Handle failure case (Rollback handled by DB::transaction, this handles the return false)
        if (!$allocationSuccess) {
            Log::critical("INVENTORY ALERT: Could not allocate ₹{$totalInvestmentValue} for Payment #{$payment->id}. Insufficient inventory.");
            
            // Flag the payment for admin review
            $payment->update([
                'is_flagged' => true, 
                'flag_reason' => 'Allocation Failed: Insufficient Inventory.'
            ]);
            
            ActivityLog::create([
                'user_id' => $user->id, 
                'action' => 'allocation_failed', 
                'target_type' => Payment::class,
                'target_id' => $payment->id, 
                'description' => "Failed to allocate ₹{$totalInvestmentValue}: Insufficient Inventory.",
            ]);
        }
    }

    /**
     * Reverse all allocations associated with a payment.
     * Useful for refunds or failed payments.
     */
    public function reverseAllocation(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $investments = $payment->investments()->get();
            
            if ($investments->isEmpty()) {
                return; // Nothing to reverse
            }

            foreach ($investments as $investment) {
                // 1. Find the inventory pool it came from
                $purchase = $investment->bulkPurchase()->lockForUpdate()->first();

                if ($purchase) {
                    // 2. Add the value back to the pool
                    $purchase->increment('value_remaining', $investment->value_allocated);
                }
                
                // 3. Create a reversal investment record (negative values for accounting)
                UserInvestment::create([
                    'user_id' => $investment->user_id,
                    'product_id' => $investment->product_id,
                    'payment_id' => $investment->payment_id,
                    'bulk_purchase_id' => $investment->bulk_purchase_id,
                    'units_allocated' => -$investment->units_allocated,
                    'value_allocated' => -$investment->value_allocated,
                    'source' => 'reversal',
                    'is_reversed' => true,
                    'reversal_reason' => $reason,
                    'reversed_at' => now(),
                ]);

                // Mark original as reversed
                $investment->update(['is_reversed' => true]);

                // 4. Log it
                ActivityLog::create([
                    'user_id' => $payment->user_id,
                    'action' => 'allocation_reversed',
                    'target_type' => Payment::class, 
                    'target_id' => $payment->id,
                    'description' => "Reversed allocation of {$investment->units_allocated} units. Reason: {$reason}",
                ]);
            }
        });
    }
}