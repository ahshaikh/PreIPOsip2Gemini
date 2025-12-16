<?php
// V-PHASE3-1730-084 (Created) | V-FIX-FRAGMENTATION (Gemini)
// Fixes: Critical "Fragmentation" bug where investments failed if no single batch was large enough.
// V-AUDIT-MODULE5-006 (HIGH) - Fractional Shares Handling

namespace App\Services;

use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\ActivityLog;
use App\Services\InventoryService;
use App\Services\WalletService;
use App\Enums\InvestmentSource; // V-AUDIT-MODULE5-007: Replace magic strings
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
     * Allocate shares from the bulk purchase pool to the user.
     *
     * CRITICAL FIX: Implements "Bucket Fill" algorithm.
     * Instead of looking for one batch >= investment value, we now fetch
     * all available batches and fill the order using FIFO (First-In-First-Out).
     *
     * V-AUDIT-MODULE5-006 (HIGH) - Fractional Shares Handling:
     * Configuration-driven approach for whole shares vs fractional shares.
     * - If 'allow_fractional_shares' setting is false, units are floored to whole numbers
     * - Fractional remainder is refunded to user's wallet
     * - Prevents invalid fractional holdings for direct equity shares
     *
     * V-AUDIT-MODULE5-007 (LOW) - Magic String Replacement:
     * - Uses InvestmentSource enum instead of hardcoded 'investment_and_bonus'
     * - Uses TransactionType enum for wallet transactions
     */
    public function allocateShares(Payment $payment, float $totalInvestmentValue, string $source = null)
    {
        if ($totalInvestmentValue <= 0) {
            Log::info("Allocation skipped for Payment #{$payment->id}: Value is zero.");
            return;
        }

        $user = $payment->user;
        $source = $source ?? InvestmentSource::INVESTMENT_AND_BONUS->value;

        // V-AUDIT-MODULE5-006: Check if fractional shares are allowed
        $allowFractional = setting('allow_fractional_shares', true);

        // Wrap the entire allocation process in a transaction to ensure data integrity
        $allocationSuccess = DB::transaction(function () use ($user, $payment, $totalInvestmentValue, $source, $allowFractional) {

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
            $totalRefundDue = 0; // Track fractional refunds

            foreach ($batches as $batch) {
                // V-AUDIT-MODULE5-006: Prevent infinite loop with very small values
                if ($remainingNeeded <= 0.01) break; // Stop if less than 1 paisa remaining

                // Logic: Take what is available in this batch, OR just what we need (whichever is smaller)
                $amountToTake = min($batch->value_remaining, $remainingNeeded);

                // Skip if amount is too small (less than 1 paisa)
                if ($amountToTake < 0.01) {
                    continue;
                }

                $product = $batch->product;
                $productToCheck = $product;

                // Prevent division by zero
                if ($product->face_value_per_unit <= 0) {
                    Log::error("Product {$product->id} has invalid face value. Skipping allocation.");
                    continue;
                }

                // Calculate Units: Value / Face Value
                $unitsCalculated = $amountToTake / $product->face_value_per_unit;

                // V-AUDIT-MODULE5-006: Handle whole vs fractional shares
                if (!$allowFractional) {
                    // Floor to whole units
                    $unitsToAllocate = floor($unitsCalculated);

                    // Calculate actual amount to deduct (whole units only)
                    $actualAmountToDeduct = $unitsToAllocate * $product->face_value_per_unit;

                    // Calculate fractional remainder to refund
                    $fractionalRemainder = $amountToTake - $actualAmountToDeduct;
                    $totalRefundDue += $fractionalRemainder;

                    // If no whole units can be allocated, skip this batch
                    if ($unitsToAllocate < 1) {
                        continue;
                    }

                    $amountToTake = $actualAmountToDeduct;
                } else {
                    $unitsToAllocate = $unitsCalculated;
                }

                // Create distinct Investment Record for this specific batch slice
                UserInvestment::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'payment_id' => $payment->id,
                    'bulk_purchase_id' => $batch->id,
                    'units_allocated' => $unitsToAllocate,
                    'value_allocated' => $amountToTake,
                    'source' => $source, // V-AUDIT-MODULE5-007: Use enum value
                ]);

                // Deduct allocated amount from Inventory
                $batch->decrement('value_remaining', $amountToTake);

                $remainingNeeded -= $amountToTake;
            }

            // V-AUDIT-MODULE5-006: Refund fractional remainder if whole shares mode
            if (!$allowFractional && $totalRefundDue > 0) {
                $this->walletService->deposit(
                    $user,
                    (string) $totalRefundDue,
                    TransactionType::REFUND->value,
                    "Fractional share remainder refund for Payment #{$payment->id}",
                    $payment
                );

                Log::info("Refunded ₹{$totalRefundDue} fractional remainder to user #{$user->id} wallet");
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
                // V-AUDIT-MODULE5-007: Use InvestmentSource enum
                UserInvestment::create([
                    'user_id' => $investment->user_id,
                    'product_id' => $investment->product_id,
                    'payment_id' => $investment->payment_id,
                    'bulk_purchase_id' => $investment->bulk_purchase_id,
                    'units_allocated' => -$investment->units_allocated,
                    'value_allocated' => -$investment->value_allocated,
                    'source' => InvestmentSource::REVERSAL->value,
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