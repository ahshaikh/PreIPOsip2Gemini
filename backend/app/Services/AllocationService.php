<?php
// V-PHASE3-1730-084 (Created) | V-FINAL-1730-351 | V-FINAL-1730-414 (InventoryService Integrated) | V-FINAL-1730-585 (Reversal Logic Added)

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
     */
    public function allocateShares(Payment $payment, float $totalInvestmentValue)
    {
        if ($totalInvestmentValue <= 0) {
            Log::info("Allocation skipped for Payment #{$payment->id}: Value is zero.");
            return;
        }

        $user = $payment->user;

        $allocationSuccess = DB::transaction(function () use ($user, $payment, $totalInvestmentValue) {
            
            $purchase = BulkPurchase::where('value_remaining', '>=', $totalInvestmentValue)
                ->whereHas('product', fn($q) => $q->where('status', 'active'))
                ->orderBy('purchase_date', 'asc')
                ->lockForUpdate() 
                ->first();

            if (!$purchase) {
                return false; // Signal failure
            }

            $product = $purchase->product;
            if ($product->face_value_per_unit <= 0) return false;

            $units = $totalInvestmentValue / $product->face_value_per_unit;

            // 1. Create User Investment Record
            UserInvestment::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'payment_id' => $payment->id,
                'bulk_purchase_id' => $purchase->id,
                'units_allocated' => $units,
                'value_allocated' => $totalInvestmentValue,
                'source' => 'investment_and_bonus',
            ]);

            // 2. Deduct from Inventory
            $purchase->decrement('value_remaining', $totalInvestmentValue);
            
            // 3. Audit Log
            ActivityLog::create([
                'user_id' => $user->id, 'action' => 'allocation_success', 'target_type' => Payment::class,
                'target_id' => $payment->id, 'description' => "Allocated {$units} units of {$product->name} (₹{$totalInvestmentValue})",
            ]);

            // 4. Low Stock Check
            if ($this->inventoryService->checkLowStock($product)) {
                Log::critical("LOW INVENTORY ALERT: Product {$product->name} (ID: {$product->id}) is now below 10% capacity.");
            }
            
            return true;
        });

        if (!$allocationSuccess) {
            Log::critical("INVENTORY ALERT: Could not allocate ₹{$totalInvestmentValue} for Payment #{$payment->id}. Insufficient inventory.");
            $payment->update(['is_flagged' => true, 'flag_reason' => 'Allocation Failed: Insufficient Inventory.']);
            ActivityLog::create([
                'user_id' => $user->id, 'action' => 'allocation_failed', 'target_type' => Payment::class,
                'target_id' => $payment->id, 'description' => "Failed to allocate ₹{$totalInvestmentValue}: Insufficient Inventory.",
            ]);
        }
    }

    /**
     * NEW: Reverse all allocations associated with a payment.
     * FSD-PAY-007: reverse_allocation
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
                
                // 3. Create a reversal investment (negative)
                UserInvestment::create([
                    'user_id' => $investment->user_id,
                    'product_id' => $investment->product_id,
                    'payment_id' => $investment->payment_id,
                    'bulk_purchase_id' => $investment->bulk_purchase_id,
                    'units_allocated' => -$investment->units_allocated,
                    'value_allocated' => -$investment->value_allocated,
                    'source' => 'reversal',
                ]);

                // 4. Log it
                ActivityLog::create([
                    'user_id' => $payment->user_id,
                    'action' => 'allocation_reversed',
                    'target_type' => Payment::class, 'target_id' => $payment->id,
                    'description' => "Reversed allocation of {$investment->units_allocated} units. Reason: {$reason}",
                ]);
            }
        });
    }
}