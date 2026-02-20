<?php
/**
 * V-AUDIT-FIX-2026: Inventory Reconciliation Job
 *
 * PURPOSE:
 * Scheduled job to verify inventory conservation law holds across all products.
 * Detects and alerts on any discrepancies between:
 * - SUM(bulk_purchases.value_remaining)
 * - SUM(user_investments.value_allocated WHERE is_reversed = false)
 * - SUM(bulk_purchases.total_value_received)
 *
 * INVARIANT:
 * allocated + remaining = total_received (for each product)
 *
 * SCHEDULE:
 * Recommended: Run hourly or daily via Laravel scheduler
 * php artisan schedule:run
 *
 * ALERTS:
 * - Discrepancies logged as CRITICAL
 * - Notification sent to admin channel if violations found
 */

namespace App\Jobs;

use App\Models\BulkPurchase;
use App\Models\Product;
use App\Models\UserInvestment;
use App\Services\InventoryConservationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Don't retry - reconciliation is read-only
    public $timeout = 300; // 5 minutes max

    protected ?int $productId;

    /**
     * Create a new job instance.
     *
     * @param int|null $productId Optional: reconcile specific product, null for all
     */
    public function __construct(?int $productId = null)
    {
        $this->productId = $productId;
    }

    /**
     * Execute the job.
     */
    public function handle(InventoryConservationService $conservationService): void
    {
        $startTime = now();
        Log::info('[INVENTORY RECONCILIATION] Job started', [
            'product_id' => $this->productId ?? 'ALL',
            'started_at' => $startTime->toIso8601String(),
        ]);

        try {
            if ($this->productId) {
                // Reconcile specific product
                $result = $this->reconcileProduct($this->productId, $conservationService);
                $results = [$result];
            } else {
                // Reconcile all products
                $results = $this->reconcileAllProducts($conservationService);
            }

            // Analyze results
            $this->analyzeAndAlert($results, $startTime);

        } catch (\Exception $e) {
            Log::critical('[INVENTORY RECONCILIATION] Job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Reconcile a specific product.
     */
    protected function reconcileProduct(int $productId, InventoryConservationService $conservationService): array
    {
        $product = Product::find($productId);
        if (!$product) {
            return [
                'product_id' => $productId,
                'status' => 'not_found',
                'is_conserved' => null,
            ];
        }

        return $conservationService->verifyConservation($product);
    }

    /**
     * Reconcile all products.
     */
    protected function reconcileAllProducts(InventoryConservationService $conservationService): array
    {
        $results = [];

        // Get all products with inventory
        $productIds = BulkPurchase::distinct()->pluck('product_id');

        foreach ($productIds as $productId) {
            $results[] = $this->reconcileProduct($productId, $conservationService);
        }

        // Also verify ledger balance consistency
        $ledgerResult = $this->verifyLedgerConsistency();
        $results[] = $ledgerResult;

        return $results;
    }

    /**
     * Verify ledger balance consistency.
     *
     * Checks that ledger entries for inventory purchases balance correctly.
     */
    protected function verifyLedgerConsistency(): array
    {
        // Sum all inventory purchase ledger entries
        $totalInventoryPurchased = DB::table('ledger_entries')
            ->where('entry_type', 'inventory_purchase')
            ->sum('amount');

        // Sum all bulk purchases cost
        $totalBulkPurchaseCost = DB::table('bulk_purchases')
            ->sum('actual_cost_paid');

        // These should match (allowing for floating point tolerance)
        $discrepancy = abs($totalInventoryPurchased - $totalBulkPurchaseCost);
        $isConsistent = $discrepancy < 1.0; // Allow 1 rupee tolerance

        return [
            'check_type' => 'ledger_consistency',
            'total_ledger_inventory' => (float) $totalInventoryPurchased,
            'total_bulk_purchase_cost' => (float) $totalBulkPurchaseCost,
            'discrepancy' => (float) $discrepancy,
            'is_conserved' => $isConsistent,
            'severity' => $isConsistent ? 'ok' : 'critical',
        ];
    }

    /**
     * Analyze results and alert on violations.
     */
    protected function analyzeAndAlert(array $results, \DateTimeInterface $startTime): void
    {
        $violations = array_filter($results, fn($r) => isset($r['is_conserved']) && !$r['is_conserved']);
        $totalProducts = count(array_filter($results, fn($r) => isset($r['product_id'])));
        $violationCount = count($violations);

        $summary = [
            'job' => 'InventoryReconciliationJob',
            'started_at' => $startTime->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'duration_seconds' => $startTime->diffInSeconds(now()),
            'total_products_checked' => $totalProducts,
            'violations_found' => $violationCount,
            'conservation_rate' => $totalProducts > 0
                ? round((($totalProducts - $violationCount) / $totalProducts) * 100, 2)
                : 100,
        ];

        if ($violationCount === 0) {
            Log::info('[INVENTORY RECONCILIATION] All products pass conservation check', $summary);
            return;
        }

        // CRITICAL: Violations found
        Log::critical('[INVENTORY RECONCILIATION] Conservation violations detected', array_merge($summary, [
            'violations' => array_map(fn($v) => [
                'product_id' => $v['product_id'] ?? 'N/A',
                'product_name' => $v['product_name'] ?? 'N/A',
                'discrepancy' => $v['discrepancy'] ?? 0,
                'discrepancy_percentage' => $v['discrepancy_percentage'] ?? 0,
            ], $violations),
        ]));

        // Create admin alert
        $this->createAdminAlert($violations, $summary);
    }

    /**
     * Create admin alert for violations.
     */
    protected function createAdminAlert(array $violations, array $summary): void
    {
        // Insert into admin_alerts table if it exists
        try {
            DB::table('admin_alerts')->insert([
                'alert_type' => 'inventory_conservation_violation',
                'severity' => 'critical',
                'title' => 'Inventory Conservation Violation Detected',
                'message' => sprintf(
                    '%d product(s) have inventory discrepancies. Conservation rate: %.2f%%. Immediate review required.',
                    count($violations),
                    $summary['conservation_rate']
                ),
                'context' => json_encode([
                    'violations' => $violations,
                    'summary' => $summary,
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table may not exist - log instead
            Log::critical('[INVENTORY RECONCILIATION] Failed to create admin alert', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
