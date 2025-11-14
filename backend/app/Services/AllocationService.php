<?php
// V-FINAL-1730-351 (Created) | V-FINAL-1730-414 (InventoryService Integrated)

namespace App\Services;

use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\ActivityLog;
use App\Services\InventoryService; // <-- IMPORT
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    // Inject the new service
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
        if ($totalInvestmentValue <= 0) return;
        $user = $payment->user;

        $allocationSuccess = DB::transaction(function () use ($user, $payment, $totalInvestmentValue) {
            
            $purchase = BulkPurchase::where('value_remaining', '>=', $totalInvestmentValue)
                ->whereHas('product', fn($q) => $q->where('status', 'active'))
                ->orderBy('purchase_date', 'asc')
                ->lockForUpdate() 
                ->first();

            if (!$purchase) return false;

            $product = $purchase->product;
            if ($product->face_value_per_unit <= 0) return false;

            $units = $totalInvestmentValue / $product->face_value_per_unit;

            UserInvestment::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'payment_id' => $payment->id,
                'bulk_purchase_id' => $purchase->id,
                'units_allocated' => $units,
                'value_allocated' => $totalInvestmentValue,
                'source' => 'investment_and_bonus',
            ]);

            $purchase->decrement('value_remaining', $totalInvestmentValue);
            
            // --- AUDIT LOG ---
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'allocation_success',
                'target_type' => Payment::class, 'target_id' => $payment->id,
                'description' => "Allocated {$units} units of {$product->name} (₹{$totalInvestmentValue})",
            ]);

            // --- NEW: LOW STOCK CHECK ---
            // After allocation, check if this product is now low on stock
            if ($this->inventoryService->checkLowStock($product)) {
                Log::critical("LOW INVENTORY ALERT: Product {$product->name} (ID: {$product->id}) is now below 10% capacity.");
                // TODO: Dispatch admin notification job
            }
            // --------------------------
            
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
}