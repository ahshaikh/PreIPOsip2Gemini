<?php
// V-PHASE2-1730-057 | V-BULK-PURCHASE-ENHANCEMENT-005
// Enhanced with full CRUD, allocation history, inventory dashboard, and low stock management

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BulkPurchaseController extends Controller
{
    /**
     * List all bulk purchases with optional filters
     */
    public function index(Request $request)
    {
        $query = BulkPurchase::with('product:id,name');

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by filter type (active = has remaining value)
        if ($request->filter === 'active') {
            $query->where('value_remaining', '>', 0);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('seller_name', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Calculate summary statistics
        $allPurchases = BulkPurchase::all();
        $summary = [
            'total_inventory_value' => $allPurchases->sum('total_value_received'),
            'total_allocated' => $allPurchases->sum(function ($p) {
                return $p->total_value_received - $p->value_remaining;
            }),
            'allocation_rate' => 0,
        ];

        if ($summary['total_inventory_value'] > 0) {
            $summary['allocation_rate'] = ($summary['total_allocated'] / $summary['total_inventory_value']) * 100;
        }

        return response()->json([
            'data' => $query->latest()->paginate(25),
            'summary' => $summary,
        ]);
    }

    /**
     * Create a new bulk purchase
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'face_value_purchased' => 'required|numeric|min:0',
            'actual_cost_paid' => 'required|numeric|min:0',
            'extra_allocation_percentage' => 'required|numeric|min:0',
            'seller_name' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $discount = ($validated['face_value_purchased'] - $validated['actual_cost_paid']) / $validated['face_value_purchased'];
        $totalValue = $validated['face_value_purchased'] * (1 + ($validated['extra_allocation_percentage'] / 100));

        $purchase = BulkPurchase::create($validated + [
            'admin_id' => $request->user()->id,
            'discount_percentage' => $discount * 100,
            'total_value_received' => $totalValue,
            'value_remaining' => $totalValue,
        ]);

        Log::info('Bulk purchase created', [
            'purchase_id' => $purchase->id,
            'product_id' => $purchase->product_id,
            'total_value' => $totalValue,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json($purchase->load('product'), 201);
    }

    /**
     * Show a specific bulk purchase
     */
    public function show(BulkPurchase $bulkPurchase)
    {
        return $bulkPurchase->load('product');
    }

    /**
     * Update an existing bulk purchase
     */
    public function update(Request $request, BulkPurchase $bulkPurchase)
    {
        // Prevent updates if allocation has already started
        $allocated = $bulkPurchase->total_value_received - $bulkPurchase->value_remaining;
        if ($allocated > 0) {
            throw ValidationException::withMessages([
                'error' => 'Cannot edit bulk purchase after allocation has started. Allocated: ₹' . number_format($allocated, 2),
            ]);
        }

        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'face_value_purchased' => 'sometimes|numeric|min:0',
            'actual_cost_paid' => 'sometimes|numeric|min:0',
            'extra_allocation_percentage' => 'sometimes|numeric|min:0',
            'seller_name' => 'nullable|string|max:255',
            'purchase_date' => 'sometimes|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Recalculate values if financial fields changed
        if (isset($validated['face_value_purchased']) || isset($validated['actual_cost_paid']) || isset($validated['extra_allocation_percentage'])) {
            $faceValue = $validated['face_value_purchased'] ?? $bulkPurchase->face_value_purchased;
            $actualCost = $validated['actual_cost_paid'] ?? $bulkPurchase->actual_cost_paid;
            $extraAlloc = $validated['extra_allocation_percentage'] ?? $bulkPurchase->extra_allocation_percentage;

            $validated['discount_percentage'] = (($faceValue - $actualCost) / $faceValue) * 100;
            $validated['total_value_received'] = $faceValue * (1 + ($extraAlloc / 100));
            $validated['value_remaining'] = $validated['total_value_received'];
        }

        $bulkPurchase->update($validated);

        Log::info('Bulk purchase updated', [
            'purchase_id' => $bulkPurchase->id,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json($bulkPurchase->load('product'));
    }

    /**
     * Delete a bulk purchase
     */
    public function destroy(BulkPurchase $bulkPurchase)
    {
        // Prevent deletion if allocation has already started
        $allocated = $bulkPurchase->total_value_received - $bulkPurchase->value_remaining;
        if ($allocated > 0) {
            throw ValidationException::withMessages([
                'error' => 'Cannot delete bulk purchase after allocation has started. Allocated: ₹' . number_format($allocated, 2),
            ]);
        }

        $bulkPurchase->delete();

        Log::warning('Bulk purchase deleted', [
            'purchase_id' => $bulkPurchase->id,
            'product_id' => $bulkPurchase->product_id,
        ]);

        return response()->json(['message' => 'Bulk purchase deleted successfully']);
    }

    /**
     * Manually allocate shares from bulk purchase to a specific user
     */
    public function manualAllocate(Request $request, BulkPurchase $bulkPurchase)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'allocation_value' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $allocationValue = $validated['allocation_value'];

        // Check if bulk purchase has sufficient remaining value
        if ($bulkPurchase->value_remaining < $allocationValue) {
            throw ValidationException::withMessages([
                'allocation_value' => 'Insufficient remaining value. Available: ₹' . number_format($bulkPurchase->value_remaining, 2),
            ]);
        }

        DB::transaction(function () use ($bulkPurchase, $user, $allocationValue, $validated, $request) {
            // Create investment record
            $investment = UserInvestment::create([
                'user_id' => $user->id,
                'product_id' => $bulkPurchase->product_id,
                'bulk_purchase_id' => $bulkPurchase->id,
                'invested_amount' => $allocationValue,
                'shares_allocated' => $allocationValue, // 1:1 for face value
                'allocation_date' => now(),
                'allocation_type' => 'manual',
                'allocated_by_admin_id' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
            ]);

            // Deduct from bulk purchase
            $bulkPurchase->decrement('value_remaining', $allocationValue);

            Log::info('Manual allocation completed', [
                'investment_id' => $investment->id,
                'user_id' => $user->id,
                'bulk_purchase_id' => $bulkPurchase->id,
                'allocation_value' => $allocationValue,
                'admin_id' => $request->user()->id,
            ]);
        });

        return response()->json([
            'message' => 'Shares allocated successfully',
            'allocation_value' => $allocationValue,
            'remaining_value' => $bulkPurchase->fresh()->value_remaining,
        ]);
    }

    /**
     * Get inventory summary per product
     */
    public function inventorySummary(Request $request)
    {
        $products = Product::where('status', 'active')->get();
        $inventoryData = [];

        foreach ($products as $product) {
            $purchases = BulkPurchase::where('product_id', $product->id)->get();

            $totalInventory = $purchases->sum('total_value_received');
            $valueRemaining = $purchases->sum('value_remaining');
            $allocated = $totalInventory - $valueRemaining;
            $allocationPercentage = $totalInventory > 0 ? ($allocated / $totalInventory) * 100 : 0;

            // Calculate average daily allocation (last 30 days)
            $thirtyDaysAgo = now()->subDays(30);
            $recentAllocations = UserInvestment::where('product_id', $product->id)
                ->where('allocation_date', '>=', $thirtyDaysAgo)
                ->sum('invested_amount');
            $averageDailyAllocation = $recentAllocations / 30;

            // Calculate days remaining
            $daysRemaining = $averageDailyAllocation > 0 ? $valueRemaining / $averageDailyAllocation : 999;

            // Low stock alert
            $lowStockConfig = $this->getLowStockConfig();
            $lowStockAlert = ($allocationPercentage >= $lowStockConfig['threshold_percentage']) ||
                             ($daysRemaining <= $lowStockConfig['days_remaining_threshold']);

            // Reorder suggestion
            $reorderSuggestion = $daysRemaining <= 30;

            $inventoryData[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'total_inventory' => number_format($totalInventory, 2, '.', ''),
                'allocated' => number_format($allocated, 2, '.', ''),
                'available' => number_format($valueRemaining, 2, '.', ''),
                'allocation_percentage' => round($allocationPercentage, 2),
                'purchase_count' => $purchases->count(),
                'average_daily_allocation' => number_format($averageDailyAllocation, 2, '.', ''),
                'days_remaining' => (int) $daysRemaining,
                'reorder_suggestion' => $reorderSuggestion,
                'low_stock_alert' => $lowStockAlert,
            ];
        }

        return response()->json(['data' => $inventoryData]);
    }

    /**
     * Get allocation trends over time
     */
    public function allocationTrends(Request $request)
    {
        $days = $request->input('days', 30);
        $productId = $request->input('product_id');

        $startDate = now()->subDays($days)->startOfDay();
        $trends = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $query = UserInvestment::whereDate('allocation_date', $date);

            if ($productId) {
                $query->where('product_id', $productId);
            }

            $dailyAllocation = $query->sum('invested_amount');

            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'allocation' => number_format($dailyAllocation, 2, '.', ''),
            ];
        }

        return response()->json(['data' => $trends]);
    }

    /**
     * Get allocation history with filters
     */
    public function allocationHistory(Request $request)
    {
        $query = UserInvestment::with(['user:id,name,email', 'product:id,name', 'bulkPurchase:id,purchase_date', 'allocatedByAdmin:id,name'])
            ->orderBy('created_at', 'desc');

        // Date filters
        if ($request->filled('date_from')) {
            $query->whereDate('allocation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('allocation_date', '<=', $request->date_to);
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Bulk purchase filter
        if ($request->filled('bulk_purchase_id')) {
            $query->where('bulk_purchase_id', $request->bulk_purchase_id);
        }

        // Allocation type filter
        if ($request->filled('allocation_type')) {
            $query->where('allocation_type', $request->allocation_type);
        }

        // Status filter
        if ($request->status === 'active') {
            $query->where('is_reversed', false);
        } elseif ($request->status === 'reversed') {
            $query->where('is_reversed', true);
        }

        // Search by user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get summary statistics
        $allInvestments = UserInvestment::all();
        $summary = [
            'total_allocations' => $allInvestments->count(),
            'total_value_allocated' => number_format($allInvestments->sum('invested_amount'), 2, '.', ''),
            'active_allocations' => $allInvestments->where('is_reversed', false)->count(),
            'reversed_allocations' => $allInvestments->where('is_reversed', true)->count(),
            'automatic_allocations' => $allInvestments->where('allocation_type', 'automatic')->count(),
            'manual_allocations' => $allInvestments->where('allocation_type', 'manual')->count(),
        ];

        $records = $query->paginate(50)->through(function ($investment) {
            return [
                'id' => $investment->id,
                'user_investment_id' => $investment->id,
                'bulk_purchase_id' => $investment->bulk_purchase_id,
                'user_id' => $investment->user_id,
                'username' => $investment->user->name ?? 'N/A',
                'email' => $investment->user->email ?? 'N/A',
                'product_name' => $investment->product->name ?? 'N/A',
                'allocated_value' => number_format($investment->invested_amount, 2, '.', ''),
                'allocation_type' => $investment->allocation_type ?? 'automatic',
                'allocated_by_admin_id' => $investment->allocated_by_admin_id,
                'allocated_by_admin_name' => $investment->allocatedByAdmin->name ?? null,
                'payment_id' => $investment->payment_id,
                'subscription_id' => $investment->subscription_id,
                'is_reversed' => $investment->is_reversed ?? false,
                'reversed_at' => $investment->reversed_at,
                'reversal_reason' => $investment->reversal_reason,
                'created_at' => $investment->created_at->toISOString(),
                'notes' => $investment->notes,
            ];
        });

        return response()->json([
            'data' => $records->items(),
            'summary' => $summary,
            'pagination' => [
                'current_page' => $records->currentPage(),
                'total_pages' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * Export allocation history in various formats
     */
    public function exportAllocationHistory(Request $request)
    {
        $format = $request->input('format', 'csv');

        // Build query with same filters as allocationHistory
        $query = UserInvestment::with(['user:id,name,email', 'product:id,name', 'allocatedByAdmin:id,name'])
            ->orderBy('created_at', 'desc');

        // Apply all filters (same as allocationHistory method)
        if ($request->filled('date_from')) {
            $query->whereDate('allocation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('allocation_date', '<=', $request->date_to);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('bulk_purchase_id')) {
            $query->where('bulk_purchase_id', $request->bulk_purchase_id);
        }
        if ($request->filled('allocation_type')) {
            $query->where('allocation_type', $request->allocation_type);
        }
        if ($request->status === 'active') {
            $query->where('is_reversed', false);
        } elseif ($request->status === 'reversed') {
            $query->where('is_reversed', true);
        }

        $records = $query->get();

        if ($format === 'csv') {
            return $this->exportAsCsv($records);
        } elseif ($format === 'excel') {
            return $this->exportAsExcel($records);
        } elseif ($format === 'pdf') {
            return $this->exportAsPdf($records);
        }

        return response()->json(['error' => 'Invalid format'], 400);
    }

    /**
     * Get low stock alert configuration
     */
    public function getLowStockConfigEndpoint()
    {
        return response()->json($this->getLowStockConfig());
    }

    /**
     * Update low stock alert configuration
     */
    public function updateLowStockConfig(Request $request)
    {
        $validated = $request->validate([
            'threshold_percentage' => 'required|numeric|min:0|max:100',
            'days_remaining_threshold' => 'required|integer|min:1',
            'enable_email_alerts' => 'required|boolean',
            'enable_system_alerts' => 'required|boolean',
        ]);

        Setting::updateOrCreate(
            ['key' => 'low_stock_config'],
            ['value' => json_encode($validated)]
        );

        Log::info('Low stock configuration updated', $validated);

        return response()->json([
            'message' => 'Low stock configuration updated successfully',
            'config' => $validated,
        ]);
    }

    /**
     * Get reorder suggestions based on allocation rate
     */
    public function reorderSuggestions()
    {
        $products = Product::where('status', 'active')->get();
        $suggestions = [];

        foreach ($products as $product) {
            $purchases = BulkPurchase::where('product_id', $product->id)->get();
            $valueRemaining = $purchases->sum('value_remaining');

            // Calculate average daily allocation (last 30 days)
            $thirtyDaysAgo = now()->subDays(30);
            $recentAllocations = UserInvestment::where('product_id', $product->id)
                ->where('allocation_date', '>=', $thirtyDaysAgo)
                ->sum('invested_amount');
            $averageDailyAllocation = $recentAllocations / 30;

            // Calculate days remaining
            $daysRemaining = $averageDailyAllocation > 0 ? $valueRemaining / $averageDailyAllocation : 999;

            // Only suggest if less than 30 days remaining
            if ($daysRemaining <= 30 && $daysRemaining > 0) {
                $suggestedAmount = $averageDailyAllocation * 90; // 90 days supply

                $priority = 'Low';
                if ($daysRemaining < 15) {
                    $priority = 'High';
                } elseif ($daysRemaining < 30) {
                    $priority = 'Medium';
                }

                $suggestions[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'days_remaining' => (int) $daysRemaining,
                    'current_stock' => number_format($valueRemaining, 2, '.', ''),
                    'average_daily_usage' => number_format($averageDailyAllocation, 2, '.', ''),
                    'suggested_purchase_amount' => number_format($suggestedAmount, 2, '.', ''),
                    'priority' => $priority,
                ];
            }
        }

        // Sort by days remaining (ascending)
        usort($suggestions, fn($a, $b) => $a['days_remaining'] <=> $b['days_remaining']);

        return response()->json(['data' => $suggestions]);
    }

    // ========== PRIVATE HELPER METHODS ==========

    /**
     * Get low stock configuration from settings
     */
    private function getLowStockConfig(): array
    {
        $config = Setting::where('key', 'low_stock_config')->first();

        if ($config && $config->value) {
            return json_decode($config->value, true);
        }

        // Default configuration
        return [
            'threshold_percentage' => 80,
            'days_remaining_threshold' => 15,
            'enable_email_alerts' => true,
            'enable_system_alerts' => true,
        ];
    }

    /**
     * Export records as CSV
     */
    private function exportAsCsv($records)
    {
        $filename = 'allocation-history-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, ['ID', 'Date', 'User', 'Email', 'Product', 'Amount', 'Type', 'Status', 'Allocated By']);

            // Data
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->id,
                    $record->created_at->format('Y-m-d H:i:s'),
                    $record->user->name ?? 'N/A',
                    $record->user->email ?? 'N/A',
                    $record->product->name ?? 'N/A',
                    $record->invested_amount,
                    $record->allocation_type ?? 'automatic',
                    $record->is_reversed ? 'Reversed' : 'Active',
                    $record->allocatedByAdmin->name ?? 'System',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export records as Excel (using CSV format for simplicity)
     */
    private function exportAsExcel($records)
    {
        // For now, use CSV format with .xlsx extension
        // In production, use a library like PhpSpreadsheet
        return $this->exportAsCsv($records);
    }

    /**
     * Export records as PDF (placeholder)
     */
    private function exportAsPdf($records)
    {
        // Placeholder - implement with a PDF library like TCPDF or DomPDF
        return response()->json(['error' => 'PDF export not yet implemented'], 501);
    }
}