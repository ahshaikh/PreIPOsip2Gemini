<?php
/**
 * V-AUDIT-FIX-2026: Inventory Traceability Report Service
 *
 * PURPOSE:
 * Answers the critical audit question:
 * "Admin bought 100,000 shares on Day 1. Today balance shows 1,000.
 *  Where did the other 99,000 shares go, and did the platform receive money for each?"
 *
 * REPORT STRUCTURE:
 * For each BulkPurchase, generates complete audit trail showing:
 * - Original purchase details
 * - All allocations to investors
 * - Current remaining balance
 * - Linked ledger entries
 * - Conservation verification
 *
 * INVARIANT VERIFIED:
 * total_value_received = SUM(allocations) + value_remaining
 */

namespace App\Services;

use App\Models\BulkPurchase;
use App\Models\Product;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryTraceabilityReportService
{
    /**
     * Generate traceability report for a single BulkPurchase.
     *
     * @param int $bulkPurchaseId
     * @return array Complete audit trail
     */
    public function generateReport(int $bulkPurchaseId): array
    {
        $bulkPurchase = BulkPurchase::with(['product', 'admin', 'approvedByAdmin'])->find($bulkPurchaseId);

        if (!$bulkPurchase) {
            return [
                'error' => 'BulkPurchase not found',
                'bulk_purchase_id' => $bulkPurchaseId,
            ];
        }

        // Get all allocations from this batch
        $allocations = UserInvestment::where('bulk_purchase_id', $bulkPurchaseId)
            ->with(['user:id,name,email', 'payment:id,status,paid_at'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate totals
        $activeAllocations = $allocations->where('is_reversed', false);
        $reversedAllocations = $allocations->where('is_reversed', true);

        $totalAllocated = $activeAllocations->sum('value_allocated');
        $totalReversed = $reversedAllocations->sum('value_allocated');

        // Get ledger entries
        $ledgerEntries = $this->getLedgerEntries($bulkPurchase);

        // Verify conservation
        $expectedRemaining = $bulkPurchase->total_value_received - $totalAllocated;
        $actualRemaining = $bulkPurchase->value_remaining;
        $discrepancy = abs($expectedRemaining - $actualRemaining);
        $conservationVerified = $discrepancy < 0.01; // 1 paisa tolerance

        return [
            'bulk_purchase_id' => $bulkPurchaseId,
            'generated_at' => now()->toIso8601String(),

            // Purchase details
            'purchase' => [
                'product_id' => $bulkPurchase->product_id,
                'product_name' => $bulkPurchase->product->name ?? 'Unknown',
                'company_id' => $bulkPurchase->company_id,
                'admin_id' => $bulkPurchase->admin_id,
                'admin_name' => $bulkPurchase->admin->name ?? 'Unknown',
                'approved_by_admin_id' => $bulkPurchase->approved_by_admin_id,
                'purchase_date' => $bulkPurchase->purchase_date,
                'created_at' => $bulkPurchase->created_at,
                'source_type' => $bulkPurchase->source_type ?? 'unknown',
                'source_id' => $bulkPurchase->source_id ?? null,
            ],

            // Financial summary
            'financials' => [
                'face_value_purchased' => (float) $bulkPurchase->face_value_purchased,
                'actual_cost_paid' => (float) $bulkPurchase->actual_cost_paid,
                'discount_percentage' => (float) $bulkPurchase->discount_percentage,
                'extra_allocation_percentage' => (float) $bulkPurchase->extra_allocation_percentage,
                'total_value_received' => (float) $bulkPurchase->total_value_received,
            ],

            // Original value (what we started with)
            'original_value' => (float) $bulkPurchase->total_value_received,

            // Current state
            'value_remaining' => (float) $actualRemaining,

            // Allocation summary
            'allocation_summary' => [
                'total_allocations' => $allocations->count(),
                'active_allocations' => $activeAllocations->count(),
                'reversed_allocations' => $reversedAllocations->count(),
                'total_allocated' => (float) $totalAllocated,
                'total_reversed' => (float) $totalReversed,
                'unique_investors' => $activeAllocations->unique('user_id')->count(),
            ],

            // Total allocated (for quick audit)
            'total_allocated' => (float) $totalAllocated,

            // Detailed allocations
            'allocated_investments' => $activeAllocations->map(function ($inv) {
                return [
                    'investment_id' => $inv->id,
                    'user_id' => $inv->user_id,
                    'user_name' => $inv->user->name ?? 'Unknown',
                    'user_email' => $inv->user->email ?? null,
                    'units_allocated' => (float) $inv->units_allocated,
                    'value_allocated' => (float) $inv->value_allocated,
                    'payment_id' => $inv->payment_id,
                    'payment_status' => $inv->payment->status ?? null,
                    'source' => $inv->source,
                    'allocated_at' => $inv->created_at->toIso8601String(),
                ];
            })->values()->toArray(),

            // Reversed allocations (for audit trail)
            'reversed_investments' => $reversedAllocations->map(function ($inv) {
                return [
                    'investment_id' => $inv->id,
                    'user_id' => $inv->user_id,
                    'value_allocated' => (float) $inv->value_allocated,
                    'reversal_reason' => $inv->reversal_reason,
                    'reversed_at' => $inv->reversed_at,
                ];
            })->values()->toArray(),

            // Ledger entries
            'ledger_entries' => $ledgerEntries,

            // Conservation verification
            'conservation_verified' => $conservationVerified,
            'conservation_check' => [
                'expected_remaining' => (float) $expectedRemaining,
                'actual_remaining' => (float) $actualRemaining,
                'discrepancy' => (float) $discrepancy,
                'formula' => 'total_value_received - SUM(active_allocations) = value_remaining',
                'status' => $conservationVerified ? 'PASS' : 'FAIL',
            ],
        ];
    }

    /**
     * Generate traceability report for all batches of a product.
     *
     * @param int $productId
     * @return array
     */
    public function generateProductReport(int $productId): array
    {
        $product = Product::with('company')->find($productId);

        if (!$product) {
            return [
                'error' => 'Product not found',
                'product_id' => $productId,
            ];
        }

        $bulkPurchases = BulkPurchase::where('product_id', $productId)
            ->orderBy('purchase_date', 'asc')
            ->get();

        $batchReports = [];
        $totalOriginal = 0;
        $totalAllocated = 0;
        $totalRemaining = 0;
        $allConserved = true;

        foreach ($bulkPurchases as $bp) {
            $report = $this->generateReport($bp->id);
            $batchReports[] = $report;

            $totalOriginal += $report['original_value'] ?? 0;
            $totalAllocated += $report['total_allocated'] ?? 0;
            $totalRemaining += $report['value_remaining'] ?? 0;

            if (!($report['conservation_verified'] ?? false)) {
                $allConserved = false;
            }
        }

        return [
            'product_id' => $productId,
            'product_name' => $product->name,
            'company_id' => $product->company_id,
            'company_name' => $product->company->name ?? 'Unknown',
            'generated_at' => now()->toIso8601String(),

            'summary' => [
                'total_batches' => $bulkPurchases->count(),
                'total_original_value' => $totalOriginal,
                'total_allocated' => $totalAllocated,
                'total_remaining' => $totalRemaining,
                'conservation_verified' => $allConserved,
            ],

            'batches' => $batchReports,
        ];
    }

    /**
     * Get ledger entries for a BulkPurchase.
     */
    protected function getLedgerEntries(BulkPurchase $bulkPurchase): array
    {
        $entries = [];

        // Get the purchase ledger entry
        if ($bulkPurchase->ledger_entry_id) {
            $purchaseEntry = DB::table('ledger_entries')
                ->where('id', $bulkPurchase->ledger_entry_id)
                ->first();

            if ($purchaseEntry) {
                $lines = DB::table('ledger_lines')
                    ->where('ledger_entry_id', $purchaseEntry->id)
                    ->get();

                $entries[] = [
                    'entry_id' => $purchaseEntry->id,
                    'entry_type' => $purchaseEntry->entry_type,
                    'description' => $purchaseEntry->description,
                    'amount' => (float) $purchaseEntry->amount,
                    'created_at' => $purchaseEntry->created_at,
                    'lines' => $lines->map(function ($line) {
                        return [
                            'account_id' => $line->account_id,
                            'debit' => (float) ($line->debit ?? 0),
                            'credit' => (float) ($line->credit ?? 0),
                        ];
                    })->toArray(),
                ];
            }
        }

        // Get allocation-related entries
        $allocationEntries = DB::table('ledger_entries')
            ->where('reference_type', 'bulk_purchase')
            ->where('reference_id', $bulkPurchase->id)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($allocationEntries as $entry) {
            $lines = DB::table('ledger_lines')
                ->where('ledger_entry_id', $entry->id)
                ->get();

            $entries[] = [
                'entry_id' => $entry->id,
                'entry_type' => $entry->entry_type,
                'description' => $entry->description,
                'amount' => (float) $entry->amount,
                'created_at' => $entry->created_at,
                'lines' => $lines->map(function ($line) {
                    return [
                        'account_id' => $line->account_id,
                        'debit' => (float) ($line->debit ?? 0),
                        'credit' => (float) ($line->credit ?? 0),
                    ];
                })->toArray(),
            ];
        }

        return $entries;
    }

    /**
     * Generate quick summary for admin dashboard.
     *
     * @return array Platform-wide inventory summary
     */
    public function generatePlatformSummary(): array
    {
        $totalInventory = BulkPurchase::sum('total_value_received');
        $totalRemaining = BulkPurchase::sum('value_remaining');
        $totalAllocated = UserInvestment::where('is_reversed', false)->sum('value_allocated');

        $expectedRemaining = $totalInventory - $totalAllocated;
        $discrepancy = abs($expectedRemaining - $totalRemaining);
        $conserved = $discrepancy < 1.0; // 1 rupee platform-wide tolerance

        return [
            'generated_at' => now()->toIso8601String(),
            'platform_inventory' => [
                'total_purchased' => (float) $totalInventory,
                'total_allocated' => (float) $totalAllocated,
                'total_remaining' => (float) $totalRemaining,
                'utilization_rate' => $totalInventory > 0
                    ? round(($totalAllocated / $totalInventory) * 100, 2)
                    : 0,
            ],
            'conservation_check' => [
                'expected_remaining' => (float) $expectedRemaining,
                'actual_remaining' => (float) $totalRemaining,
                'discrepancy' => (float) $discrepancy,
                'status' => $conserved ? 'PASS' : 'FAIL',
            ],
            'statistics' => [
                'total_batches' => BulkPurchase::count(),
                'total_products' => BulkPurchase::distinct()->count('product_id'),
                'total_allocations' => UserInvestment::where('is_reversed', false)->count(),
                'total_investors' => UserInvestment::where('is_reversed', false)->distinct()->count('user_id'),
            ],
        ];
    }
}
