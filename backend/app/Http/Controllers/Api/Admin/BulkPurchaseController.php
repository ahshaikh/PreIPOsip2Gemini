<?php
// V-PHASE2-1730-057 | V-BULK-PURCHASE-ENHANCEMENT-005 | V-FIX-UNITS-AND-N1 (Gemini)
// Enhanced with full CRUD, allocation history, inventory dashboard, and low stock management

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkPurchase;
use App\Models\PlatformLedgerEntry;
use App\Models\UserInvestment;
use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use App\Services\PlatformLedgerService;
use App\Services\DoubleEntryLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

// Library Imports for Exports
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // V-AUDIT-MODULE20-CRITICAL: Fixed Scalability Bomb (BulkPurchase::all())
        //
        // PROBLEM: Original code loaded EVERY bulk purchase record into PHP memory:
        //   $allPurchases = BulkPurchase::all();
        //   $summary['total_inventory_value'] = $allPurchases->sum('total_value_received');
        //
        // Impact: As history grows to 1,000+ bulk purchases, this causes:
        // - PHP memory exhaustion (Out of Memory error on 128MB limit)
        // - Slow response times (loading entire table + relationships)
        // - Database load (full table scan without WHERE clause)
        // - Dashboard becomes UNUSABLE in production with scale
        //
        // SOLUTION: Use SQL aggregates to calculate sums directly in the database.
        // - Single query instead of loading thousands of Eloquent objects
        // - Constant O(1) memory usage regardless of record count
        // - Database engine optimizes aggregation (uses indexes)
        //
        // Performance: O(N) memory → O(1) memory (1000x improvement)

        // V-AUDIT-MODULE20-CRITICAL: Calculate summary statistics using SQL aggregates (no memory load)
        $aggregates = BulkPurchase::selectRaw('
            SUM(total_value_received) as total_inventory_value,
            SUM(total_value_received - value_remaining) as total_allocated
        ')->first();

        $summary = [
            'total_inventory_value' => (float) ($aggregates->total_inventory_value ?? 0),
            'total_allocated' => (float) ($aggregates->total_allocated ?? 0),
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
     *
     * EPIC 4 - GAP 1 FIX: Inventory Creation With Financial Atomicity
     *
     * INVARIANT: No inventory may exist without a corresponding platform ledger debit.
     *
     * PROTOCOL:
     * 1. Wrap entire creation in DB transaction
     * 2. Create BulkPurchase record
     * 3. Create platform ledger debit atomically
     * 4. Link BulkPurchase to ledger entry
     * 5. If ANY step fails, entire transaction rolls back
     *
     * FAILURE SEMANTICS:
     * - Hard failure if ledger debit cannot be recorded
     * - No orphaned inventory (BulkPurchase without ledger proof)
     * - Rollback guarantees: either BOTH exist or NEITHER exists
     */
    public function store(
        Request $request,
        PlatformLedgerService $ledgerService,
        DoubleEntryLedgerService $doubleEntryLedger
    ) {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'face_value_purchased' => 'required|numeric|min:0.01', // Changed from min:0 to prevent division by zero
            'actual_cost_paid' => 'required|numeric|min:0',
            'extra_allocation_percentage' => 'required|numeric|min:0',
            'seller_name' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            // PROVENANCE: Required for audit compliance
            'company_id' => 'required|exists:companies,id',
            'source_type' => 'required|in:company_listing,manual_entry',
            'company_share_listing_id' => 'required_if:source_type,company_listing|nullable|exists:company_share_listings,id',
            'manual_entry_reason' => 'required_if:source_type,manual_entry|nullable|string|min:50',
            'source_documentation' => 'required_if:source_type,manual_entry|nullable|string',
        ]);

        $discount = ($validated['face_value_purchased'] - $validated['actual_cost_paid']) / $validated['face_value_purchased'];
        $totalValue = $validated['face_value_purchased'] * (1 + ($validated['extra_allocation_percentage'] / 100));

        // GAP 1 FIX: Atomic transaction ensures inventory + ledger are created together
        // PHASE 4 FIX: Now uses DOUBLE-ENTRY LEDGER for bank-mirroring accounting
        $purchase = DB::transaction(function () use ($validated, $request, $totalValue, $discount, $ledgerService, $doubleEntryLedger) {

            // STEP 1: Create BulkPurchase record (without ledger link initially)
            $purchase = BulkPurchase::create($validated + [
                'admin_id' => $request->user()->id,
                'approved_by_admin_id' => $validated['source_type'] === 'manual_entry' ? $request->user()->id : null,
                'verified_at' => now(),
                'discount_percentage' => $discount * 100,
                'total_value_received' => $totalValue,
                'value_remaining' => $totalValue,
            ]);

            // STEP 2: Create platform ledger debit to prove capital movement (legacy)
            // Convert to paise (smallest currency unit) for precision
            $amountPaise = (int) round($validated['actual_cost_paid'] * 100);

            $ledgerEntry = $ledgerService->debit(
                PlatformLedgerEntry::SOURCE_BULK_PURCHASE,
                $purchase->id,
                $amountPaise,
                "Inventory purchase: Product #{$purchase->product_id}, BulkPurchase #{$purchase->id}, " .
                "Face Value: ₹" . number_format($validated['face_value_purchased'], 2) . ", " .
                "Cost Paid: ₹" . number_format($validated['actual_cost_paid'], 2),
                'INR',
                [
                    'product_id' => $purchase->product_id,
                    'company_id' => $validated['company_id'],
                    'source_type' => $validated['source_type'],
                    'face_value_purchased' => $validated['face_value_purchased'],
                    'actual_cost_paid' => $validated['actual_cost_paid'],
                    'total_value_received' => $totalValue,
                    'admin_id' => $request->user()->id,
                ]
            );

            // STEP 3: DOUBLE-ENTRY LEDGER - Record inventory purchase
            // This ensures the platform ledger mirrors the real bank account:
            //   DEBIT  INVENTORY (asset increases - we now own shares)
            //   CREDIT BANK      (asset decreases - cash paid out)
            $doubleEntryLedger->recordInventoryPurchase(
                $purchase,
                (float) $validated['actual_cost_paid'],
                $request->user()->id
            );

            // STEP 4: Link BulkPurchase to ledger entry (proving the invariant)
            // This update is allowed because platform_ledger_entry_id is mutable
            // (it's set after creation, not a financial field)
            $purchase->platform_ledger_entry_id = $ledgerEntry->id;
            $purchase->saveQuietly(); // Skip observer to avoid validation loops

            Log::info('Bulk purchase created with double-entry ledger atomicity', [
                'purchase_id' => $purchase->id,
                'product_id' => $purchase->product_id,
                'total_value' => $totalValue,
                'actual_cost_paid' => $validated['actual_cost_paid'],
                'ledger_entry_id' => $ledgerEntry->id,
                'admin_id' => $request->user()->id,
                'double_entry' => 'DEBIT INVENTORY, CREDIT BANK',
            ]);

            return $purchase;
        });

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
            'face_value_purchased' => 'sometimes|numeric|min:0.01',
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

        // V-AUDIT-MODULE20-MEDIUM: Fixed Race Condition (Concurrent Over-Allocation)
        //
        // PROBLEM: Original code checked value_remaining BEFORE transaction:
        //   if ($bulkPurchase->value_remaining < $allocationValue) { throw error; }
        //   DB::transaction(function () { $bulkPurchase->decrement(...); });
        //
        // Race Condition: Two concurrent requests (A and B) both allocating ₹10,000:
        // 1. Request A checks: value_remaining = ₹15,000 (pass)
        // 2. Request B checks: value_remaining = ₹15,000 (pass) ← STALE READ
        // 3. Request A decrements: value_remaining = ₹5,000
        // 4. Request B decrements: value_remaining = -₹5,000 ← NEGATIVE BALANCE!
        //
        // Impact: Inventory goes negative, over-allocation occurs, double-spending
        // Result: More value allocated than actually purchased (financial loss)
        //
        // SOLUTION: Use lockForUpdate() inside transaction to acquire pessimistic lock.
        // - Locks the row for the entire transaction duration
        // - Request B blocks until Request A commits (serialized execution)
        // - Re-checks value_remaining after lock acquisition (no stale reads)
        //
        // Security: Prevents double-spending and inventory over-allocation

        DB::transaction(function () use ($bulkPurchase, $user, $allocationValue, $validated, $request) {
            // V-AUDIT-MODULE20-MEDIUM: Lock bulk purchase row for update (pessimistic lock)
            // This prevents concurrent allocations from the same purchase
            $lockedPurchase = BulkPurchase::where('id', $bulkPurchase->id)
                ->lockForUpdate()
                ->first();

            // V-AUDIT-MODULE20-MEDIUM: Re-check value_remaining AFTER acquiring lock
            // This ensures we have the latest value (no race condition)
            if ($lockedPurchase->value_remaining < $allocationValue) {
                throw ValidationException::withMessages([
                    'allocation_value' => 'Insufficient remaining value. Available: ₹' . number_format($lockedPurchase->value_remaining, 2),
                ]);
            }

            // FIX: Load Product to access face_value_per_unit
            $lockedPurchase->load('product');
            $product = $lockedPurchase->product;

            // FIX: Old logic used $allocationValue directly as shares_allocated (1:1).
            // New logic divides Allocation Value by Face Value to get correct Unit Count.
            $units = $allocationValue; // Default fallback to 1:1 if face value is missing (safety)

            if ($product && $product->face_value_per_unit > 0) {
                $units = $allocationValue / $product->face_value_per_unit;
            } else {
                Log::warning("Manual Allocation: Product {$lockedPurchase->product_id} has invalid face value. Using 1:1 fallback.", ['product' => $product]);
            }

            // Create investment record
            $investment = UserInvestment::create([
                'user_id' => $user->id,
                'product_id' => $lockedPurchase->product_id,
                'bulk_purchase_id' => $lockedPurchase->id,
                'invested_amount' => $allocationValue,
                'shares_allocated' => $units, // FIX: Use calculated units, not raw value
                'allocation_date' => now(),
                'allocation_type' => 'manual',
                'allocated_by_admin_id' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
            ]);

            // V-AUDIT-MODULE20-MEDIUM: Deduct from locked purchase (atomic within transaction)
            $lockedPurchase->decrement('value_remaining', $allocationValue);

            Log::info('Manual allocation completed', [
                'investment_id' => $investment->id,
                'user_id' => $user->id,
                'bulk_purchase_id' => $lockedPurchase->id,
                'allocation_value' => $allocationValue,
                'units' => $units,
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
        // FIX: N+1 Performance Issue.
        // Replaced loop-based queries with Eloquent `withSum` and `withCount` aggregates.
        // This reduces database queries from (N*2 + 1) to just 2-3 optimized queries.
        
        $products = Product::where('status', 'active')
            ->withSum('bulkPurchases as total_inventory', 'total_value_received')
            ->withSum('bulkPurchases as remaining_inventory', 'value_remaining')
            ->withCount('bulkPurchases as purchase_count')
            ->get();

        $inventoryData = [];
        $lowStockConfig = $this->getLowStockConfig();

        // Optimized burn rate calculation: Single query to fetch recent sales for all relevant products
        $thirtyDaysAgo = now()->subDays(30);
        
        // Fetch all recent investment sums grouped by product_id in one go
        $recentAllocationsMap = DB::table('user_investments')
            ->select('product_id', DB::raw('SUM(invested_amount) as total_invested'))
            ->whereIn('product_id', $products->pluck('id'))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy('product_id')
            ->pluck('total_invested', 'product_id');

        foreach ($products as $product) {
            
            $totalInventory = $product->total_inventory ?? 0;
            $valueRemaining = $product->remaining_inventory ?? 0;
            $allocated = $totalInventory - $valueRemaining;
            $allocationPercentage = $totalInventory > 0 ? ($allocated / $totalInventory) * 100 : 0;

            // Use the pre-fetched map instead of querying inside the loop
            $recentAllocations = $recentAllocationsMap[$product->id] ?? 0;
            $averageDailyAllocation = $recentAllocations / 30;

            // Calculate days remaining
            $daysRemaining = $averageDailyAllocation > 0 ? $valueRemaining / $averageDailyAllocation : 999;

            // Low stock alert
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
                'purchase_count' => $product->purchase_count,
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

        // Optimizing this loop to use a single aggregation query
        $data = UserInvestment::select(
                DB::raw('DATE(allocation_date) as date'),
                DB::raw('SUM(invested_amount) as total')
            )
            ->where('allocation_date', '>=', $startDate)
            ->when($productId, function($q) use ($productId) {
                return $q->where('product_id', $productId);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        for ($i = $days - 1; $i >= 0; $i--) {
            $dateStr = now()->subDays($i)->format('Y-m-d');
            $trends[] = [
                'date' => $dateStr,
                'allocation' => number_format($data[$dateStr] ?? 0, 2, '.', ''),
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
        // Optimization: Use a separate summary endpoint or cache this if it's too heavy
        // For now, keeping it but using SQL aggregates
        $summary = [
            'total_allocations' => UserInvestment::count(),
            'total_value_allocated' => UserInvestment::sum('invested_amount'),
            'active_allocations' => UserInvestment::where('is_reversed', false)->count(),
            'reversed_allocations' => UserInvestment::where('is_reversed', true)->count(),
            'automatic_allocations' => UserInvestment::where('allocation_type', 'automatic')->count(),
            'manual_allocations' => UserInvestment::where('allocation_type', 'manual')->count(),
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

        return response()->stream(function () use ($records) {
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
        }, 200, $headers);
    }

    /**
     * Export records as Excel (.xlsx) using PhpSpreadsheet)
     */
    private function exportAsExcel($records)
    {
        // FIX: Implemented real .xlsx export
        $filename = 'allocation-history-' . date('Y-m-d') . '.xlsx';
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set Header Row
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'User');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'Product');
        $sheet->setCellValue('F1', 'Amount');
        $sheet->setCellValue('G1', 'Type');
        $sheet->setCellValue('H1', 'Status');
        $sheet->setCellValue('I1', 'Allocated By');
        
        // Style Header
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        // Populate Data
        $row = 2;
        foreach ($records as $record) {
            $sheet->setCellValue('A' . $row, $record->id);
            $sheet->setCellValue('B' . $row, $record->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('C' . $row, $record->user->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $record->user->email ?? 'N/A');
            $sheet->setCellValue('E' . $row, $record->product->name ?? 'N/A');
            $sheet->setCellValue('F' . $row, $record->invested_amount);
            $sheet->setCellValue('G' . $row, $record->allocation_type ?? 'automatic');
            $sheet->setCellValue('H' . $row, $record->is_reversed ? 'Reversed' : 'Active');
            $sheet->setCellValue('I' . $row, $record->allocatedByAdmin->name ?? 'System');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        return response()->stream(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export as PDF using DomPDF
     */
    
    private function exportAsPdf($records)
    {
        // FIX: Implemented real PDF export
        $filename = 'allocation-history-' . date('Y-m-d') . '.pdf';
        
        // Simple HTML table for PDF
        $html = '<h1>Allocation History</h1>';
        $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5" width="100%">';
        $html .= '<thead><tr style="background-color: #f2f2f2;">
                    <th>ID</th><th>Date</th><th>User</th><th>Product</th><th>Amount</th><th>Status</th>
                  </tr></thead><tbody>';
        
        foreach ($records as $record) {
            $statusColor = $record->is_reversed ? '#ffebee' : '#ffffff';
            $html .= "<tr style='background-color: {$statusColor};'>
                        <td>{$record->id}</td>
                        <td>{$record->created_at->format('Y-m-d')}</td>
                        <td>" . ($record->user->name ?? 'N/A') . "</td>
                        <td>" . ($record->product->name ?? 'N/A') . "</td>
                        <td>{$record->invested_amount}</td>
                        <td>" . ($record->is_reversed ? 'Reversed' : 'Active') . "</td>
                      </tr>";
        }
        $html .= '</tbody></table>';

        $pdf = Pdf::loadHTML($html);
        return $pdf->download($filename);
    }
}