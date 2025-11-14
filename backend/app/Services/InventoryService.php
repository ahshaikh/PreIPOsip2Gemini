<?php
// V-FINAL-1730-413 (Created)

namespace App\Services;

use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InventoryService
{
    /**
     * Get a full inventory status for a specific product.
     */
    public function getProductInventoryStats(Product $product)
    {
        $stats = $product->bulkPurchases()
            ->select(
                DB::raw('SUM(total_value_received) as total_inventory'),
                DB::raw('SUM(value_remaining) as available_inventory')
            )
            ->first();

        $total = (float) $stats->total_inventory;
        $available = (float) $stats->available_inventory;
        $allocated = $total - $available;
        $soldPercentage = ($total > 0) ? ($allocated / $total) * 100 : 0;

        return (object) [
            'total' => $total,
            'available' => $available,
            'allocated' => $allocated,
            'sold_percentage' => round($soldPercentage, 2)
        ];
    }

    /**
     * Test: test_check_available_inventory_calculates_correctly
     */
    public function getAvailableInventory(Product $product): float
    {
        return (float) $product->bulkPurchases()->sum('value_remaining');
    }

    /**
     * Test: test_low_stock_alert_triggers_at_10_percent
     */
    public function checkLowStock(Product $product): bool
    {
        $stats = $this->getProductInventoryStats($product);
        if ($stats->total == 0) return false; // No stock to be low on

        // FSD-BULK-008: Alert at 10%
        return $stats->sold_percentage >= 90; 
    }

    /**
     * Test: test_reorder_suggestion_based_on_allocation_rate
     * Calculates days of inventory remaining.
     */
    public function getReorderSuggestion(Product $product): string
    {
        // 1. Get current available inventory
        $stats = $this->getProductInventoryStats($product);
        $available = $stats->available;

        if ($available <= 0) {
            return "Out of stock. Reorder immediately.";
        }

        // 2. Calculate daily allocation rate (burn rate) over last 30 days
        $allocatedIn30Days = $product->investments()
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('value_allocated');
            
        if ($allocatedIn30Days <= 0) {
            return "No recent allocations. Inventory is stable.";
        }

        $dailyBurnRate = $allocatedIn30Days / 30;

        // 3. Forecast
        $daysRemaining = (int) floor($available / $dailyBurnRate);
        $reorderDate = now()->addDays($daysRemaining - 7)->toDateString(); // Suggest reorder 7 days before empty

        return "At current rate (₹{$dailyBurnRate}/day), inventory will last {$daysRemaining} days. Suggest reorder by {$reorderDate}.";
    }
}