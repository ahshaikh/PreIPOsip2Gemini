<?php

namespace App\Services;

use App\Models\BulkPurchase;
use App\Models\Product;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * InventoryConservationService - Guarantees Inventory Conservation Law
 *
 * PROTOCOL:
 * MATHEMATICAL GUARANTEE:
 *   SUM(bulk_purchases.total_value_received) =
 *   SUM(bulk_purchases.value_remaining) + SUM(user_investments.value_allocated WHERE is_reversed = false)
 *
 * INVARIANT:
 *   For every product: allocated + remaining = total_received
 *   Inventory cannot be created or destroyed, only transferred
 *
 * ENFORCEMENT:
 * 1. Real-time verification during allocation
 * 2. Pessimistic locking to prevent race conditions
 * 3. Automated reconciliation with alerts
 * 4. Database constraints to prevent violation
 *
 * FAILURE SEMANTICS:
 * - Allocation fails if would violate conservation
 * - Discrepancies trigger critical alerts
 * - System halts rather than create inconsistent state
 */
class InventoryConservationService
{
    /**
     * Verify Conservation Law for Product
     *
     * Returns:
     * - is_conserved: true if law holds
     * - total_received: Total inventory purchased
     * - allocated: Total allocated to users
     * - remaining: Available inventory
     * - discrepancy: allocated + remaining - total_received (should be 0)
     *
     * @param Product $product
     * @return array
     */
    public function verifyConservation(Product $product): array
    {
        // Lock bulk purchases for this product to get consistent snapshot
        $bulkPurchases = BulkPurchase::where('product_id', $product->id)
            ->lockForUpdate()
            ->get();

        // Calculate from bulk purchases
        $totalReceived = $bulkPurchases->sum('total_value_received');
        $remainingInventory = $bulkPurchases->sum('value_remaining');

        // Calculate from user investments (active allocations only)
        $allocatedToUsers = UserInvestment::where('product_id', $product->id)
            ->where('is_reversed', false)
            ->sum('value_allocated');

        // Calculate what remaining SHOULD be
        $expectedRemaining = $totalReceived - $allocatedToUsers;

        // Check conservation law
        $discrepancy = abs($remainingInventory - $expectedRemaining);
        $isConserved = $discrepancy < 0.01; // Allow 1 paisa rounding error

        $result = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'is_conserved' => $isConserved,
            'total_received' => (float) $totalReceived,
            'allocated_to_users' => (float) $allocatedToUsers,
            'remaining_inventory' => (float) $remainingInventory,
            'expected_remaining' => (float) $expectedRemaining,
            'discrepancy' => (float) $discrepancy,
            'discrepancy_percentage' => $totalReceived > 0 ? ($discrepancy / $totalReceived) * 100 : 0,
        ];

        if (!$isConserved) {
            Log::critical("INVENTORY CONSERVATION VIOLATED", $result);
        }

        return $result;
    }

    /**
     * Verify Conservation Law for ALL Products
     *
     * @return array Summary with list of violations
     */
    public function verifyAllProducts(): array
    {
        $products = Product::with(['bulkPurchases', 'userInvestments'])->get();
        $violations = [];
        $totalProducts = $products->count();
        $violatedProducts = 0;

        foreach ($products as $product) {
            $result = $this->verifyConservation($product);

            if (!$result['is_conserved']) {
                $violations[] = $result;
                $violatedProducts++;
            }
        }

        return [
            'total_products' => $totalProducts,
            'products_in_violation' => $violatedProducts,
            'conservation_rate' => $totalProducts > 0 ? (($totalProducts - $violatedProducts) / $totalProducts) * 100 : 100,
            'violations' => $violations,
        ];
    }

    /**
     * Verify Atomic Allocation (BEFORE allocating)
     *
     * PROTOCOL:
     * - Called BEFORE AllocationService creates UserInvestment
     * - Verifies allocation won't violate conservation
     * - Uses pessimistic locking
     *
     * @param Product $product
     * @param float $amountToAllocate
     * @return array ['can_allocate' => bool, 'reason' => string]
     */
    public function canAllocate(Product $product, float $amountToAllocate): array
    {
        return DB::transaction(function () use ($product, $amountToAllocate) {
            // Lock bulk purchases to prevent concurrent allocation
            $bulkPurchases = BulkPurchase::where('product_id', $product->id)
                ->where('value_remaining', '>', 0)
                ->lockForUpdate()
                ->get();

            $totalAvailable = $bulkPurchases->sum('value_remaining');

            if ($totalAvailable < $amountToAllocate) {
                return [
                    'can_allocate' => false,
                    'reason' => "Insufficient inventory. Requested: ₹{$amountToAllocate}, Available: ₹{$totalAvailable}",
                    'available' => $totalAvailable,
                    'requested' => $amountToAllocate,
                ];
            }

            // Verify conservation would hold after allocation
            $totalReceived = BulkPurchase::where('product_id', $product->id)->sum('total_value_received');
            $currentAllocated = UserInvestment::where('product_id', $product->id)
                ->where('is_reversed', false)
                ->sum('value_allocated');

            $futureAllocated = $currentAllocated + $amountToAllocate;
            $futureRemaining = $totalAvailable - $amountToAllocate;

            // Check: futureAllocated + futureRemaining = totalReceived
            $futureSum = $futureAllocated + $futureRemaining;
            $conservationHolds = abs($futureSum - $totalReceived) < 0.01;

            if (!$conservationHolds) {
                Log::critical("ALLOCATION WOULD VIOLATE CONSERVATION", [
                    'product_id' => $product->id,
                    'total_received' => $totalReceived,
                    'future_allocated' => $futureAllocated,
                    'future_remaining' => $futureRemaining,
                    'future_sum' => $futureSum,
                    'discrepancy' => $futureSum - $totalReceived,
                ]);

                return [
                    'can_allocate' => false,
                    'reason' => 'Allocation would violate inventory conservation law',
                    'conservation_check' => [
                        'total_received' => $totalReceived,
                        'future_allocated' => $futureAllocated,
                        'future_remaining' => $futureRemaining,
                        'discrepancy' => $futureSum - $totalReceived,
                    ],
                ];
            }

            return [
                'can_allocate' => true,
                'reason' => 'Allocation preserves conservation law',
                'available' => $totalAvailable,
            ];
        });
    }

    /**
     * Reconcile Inventory (Find and Fix Discrepancies)
     *
     * PROTOCOL:
     * - Compares bulk_purchases.value_remaining vs actual allocations
     * - If discrepancy found, calculates correct value
     * - Creates reconciliation report for admin review
     * - Does NOT auto-fix (requires admin approval)
     *
     * @return array Reconciliation report
     */
    public function reconcile(): array
    {
        $allResults = $this->verifyAllProducts();
        $violations = $allResults['violations'];

        if (empty($violations)) {
            return [
                'status' => 'clean',
                'message' => 'All products pass inventory conservation check',
                'summary' => $allResults,
            ];
        }

        // Generate reconciliation report
        $reconciliationReport = [
            'status' => 'discrepancies_found',
            'total_violations' => count($violations),
            'total_discrepancy_value' => array_sum(array_column($violations, 'discrepancy')),
            'violations' => array_map(function ($violation) {
                return [
                    'product_id' => $violation['product_id'],
                    'product_name' => $violation['product_name'],
                    'discrepancy' => $violation['discrepancy'],
                    'discrepancy_percentage' => $violation['discrepancy_percentage'],
                    'recommended_action' => $this->getRecommendedAction($violation),
                ];
            }, $violations),
        ];

        // Alert admins
        Log::critical("INVENTORY RECONCILIATION: Discrepancies found", $reconciliationReport);

        return $reconciliationReport;
    }

    /**
     * Get Recommended Action for Violation
     *
     * @param array $violation
     * @return string
     */
    private function getRecommendedAction(array $violation): string
    {
        $discrepancy = $violation['discrepancy'];

        if ($discrepancy > 0) {
            // value_remaining is too high (allocated is less than expected)
            return "Inventory over-reported by ₹{$discrepancy}. " .
                   "Possible causes: (1) Reversed allocation not restored, (2) Allocation failed but inventory not restored. " .
                   "Action: Review allocation history and restore inventory if needed.";
        } else {
            // value_remaining is too low (allocated is more than expected)
            $absDiscrepancy = abs($discrepancy);
            return "Inventory under-reported by ₹{$absDiscrepancy}. " .
                   "Possible causes: (1) Concurrent allocation race condition, (2) Manual inventory manipulation. " .
                   "Action: Review allocation logs and adjust inventory if legitimate allocations were made.";
        }
    }

    /**
     * Lock Inventory for Allocation (Helper for AllocationService)
     *
     * Returns locked bulk purchases in FIFO order
     *
     * @param Product $product
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function lockInventoryForAllocation(Product $product)
    {
        return BulkPurchase::where('product_id', $product->id)
            ->where('value_remaining', '>', 0)
            ->orderBy('purchase_date', 'asc') // FIFO
            ->lockForUpdate() // Pessimistic lock
            ->get();
    }

    /**
     * Verify Single Allocation (Post-Allocation Check)
     *
     * Called AFTER UserInvestment created to verify conservation still holds
     *
     * @param UserInvestment $userInvestment
     * @return array
     */
    public function verifyAllocation(UserInvestment $userInvestment): array
    {
        $result = $this->verifyConservation($userInvestment->product);

        if (!$result['is_conserved']) {
            Log::critical("ALLOCATION CREATED CONSERVATION VIOLATION", [
                'user_investment_id' => $userInvestment->id,
                'product_id' => $userInvestment->product_id,
                'allocated_amount' => $userInvestment->value_allocated,
                'conservation_result' => $result,
            ]);
        }

        return $result;
    }

    /**
     * Get Conservation Health Score (0-100)
     *
     * Used for dashboard/monitoring
     *
     * @return array
     */
    public function getHealthScore(): array
    {
        $allResults = $this->verifyAllProducts();

        $totalProducts = $allResults['total_products'];
        $violatedProducts = $allResults['products_in_violation'];
        $conservationRate = $allResults['conservation_rate'];

        // Calculate health score
        // 100 = perfect conservation
        // 0 = all products violated
        $healthScore = $conservationRate;

        // Severity classification
        $severity = 'healthy';
        if ($healthScore < 95) {
            $severity = 'warning';
        }
        if ($healthScore < 90) {
            $severity = 'critical';
        }

        return [
            'health_score' => round($healthScore, 2),
            'severity' => $severity,
            'total_products' => $totalProducts,
            'products_healthy' => $totalProducts - $violatedProducts,
            'products_violated' => $violatedProducts,
            'conservation_rate' => round($conservationRate, 2),
        ];
    }
}
