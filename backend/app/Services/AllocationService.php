<?php
// V-PHASE3-1730-084

namespace App\Services;

use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\Product;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\Log;

class AllocationService
{
    /**
     * Allocate shares from the bulk purchase pool to the user.
     */
    public function allocateShares(Payment $payment, float $totalInvestmentValue)
    {
        $user = $payment->user;
        
        // 1. Find a product to allocate to.
        // In a real app, this logic would be complex (user choice, diversification)
        // For now, find the first available bulk purchase with enough value.
        $purchase = BulkPurchase::where('value_remaining', '>=', $totalInvestmentValue)
                                ->whereHas('product', fn($q) => $q->where('status', 'active'))
                                ->orderBy('purchase_date', 'asc')
                                ->first();

        if (!$purchase) {
            Log::error("No bulk purchase inventory available for payment {$payment->id}");
            // TODO: Queue this payment for retry when inventory is available
            return;
        }

        $product = $purchase->product;
        $units = $totalInvestmentValue / $product->face_value_per_unit;

        // 2. Create the investment record
        UserInvestment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'payment_id' => $payment->id,
            'bulk_purchase_id' => $purchase->id,
            'units_allocated' => $units,
            'value_allocated' => $totalInvestmentValue,
            'source' => 'investment_and_bonus', // Combined
        ]);

        // 3. Deduct from the bulk purchase pool
        $purchase->decrement('value_remaining', $totalInvestmentValue);
        
        Log::info("Allocated {$units} units of {$product->name} for payment {$payment->id}");
    }
}