<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

// External Packages
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

// Models
use App\Models\Payment;
use App\Models\User;
use App\Models\Product;
use App\Models\Wallet;
use App\Models\BulkPurchase;
use App\Models\ActivityLog;
// ADDED: Model to track export status in DB
use App\Models\DataExportJob as DataExportJobModel;

// Jobs
// ADDED: Queueable Job to handle heavy exports asynchronously
use App\Jobs\DataExportJob;

// Dynamic imports
use App\Services\ReportService;
use App\Services\InventoryService;
use App\Exports\DynamicTableExport;

class ReportController extends Controller
{
    protected $service;
    protected $inventoryService;

    public function __construct(?ReportService $service = null, ?InventoryService $inventoryService = null)
    {
        $this->service = $service;
        $this->inventoryService = $inventoryService;
    }

    // =======================================================================
    // PART 1: DASHBOARD ENDPOINTS
    // =======================================================================

    /**
     * 1. Financial Summary Report
     * Endpoint: /api/v1/admin/reports/financial-summary
     */
    public function financialSummary(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->end_date) : Carbon::now();

            // KPI Calculations
            $totalRevenue = Payment::where('status', 'completed')->sum('amount');
            $totalUsers = User::count();
            
            // Safe checks for optional tables
            $pendingKyc = Schema::hasTable('user_kyc') ? DB::table('user_kyc')->where('status', 'pending')->count() : 0;
            $pendingWithdrawals = Schema::hasTable('withdrawals') ? DB::table('withdrawals')->where('status', 'pending')->count() : 0;

            // Daily Revenue Chart Data
            $dailyRevenue = Payment::selectRaw('DATE(created_at) as date, SUM(amount) as total')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => Carbon::parse($item->date)->format('d M'),
                        'total' => (float) $item->total
                    ];
                });

            return response()->json([
                'kpis' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_users' => (int) $totalUsers,
                    'pending_kyc' => (int) $pendingKyc,
                    'pending_withdrawals' => (int) $pendingWithdrawals,
                ],
                'charts' => [
                    'daily_revenue' => $dailyRevenue
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error("Financial Report Failed: " . $e->getMessage());
            return $this->safeErrorResponse($e);
        }
    }

    /**
     * 2. User Analytics
     * Endpoint: /api/v1/admin/reports/analytics/users
     */
    public function analyticsUsers(Request $request): JsonResponse
    {
        try {
            $totalUsers = User::count();
            $verifiedUsers = Schema::hasTable('user_kyc') ? DB::table('user_kyc')->where('status', 'verified')->count() : 0;
            $kycPercentage = $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100, 1) : 0;

            // Retention Metrics via Service if available
            $churnRate = 0;
            $usersLost = 0; 
            if ($this->service && method_exists($this->service, 'getRetentionMetrics')) {
                $metrics = $this->service->getRetentionMetrics(now()->subYear(), now());
                $churnRate = $metrics['churn_rate'] ?? 0;
                $usersLost = $metrics['users_lost'] ?? 0;
            }

            // Acquisition Chart
            $acquisition = User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->where('created_at', '>=', Carbon::now()->subYear())
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => Carbon::parse($item->month . '-01')->format('M Y'),
                        'count' => (int) $item->count
                    ];
                });

            return response()->json([
                'kyc_percentage' => $kycPercentage,
                'retention_metrics' => [
                    'churn_rate' => $churnRate,
                    'users_lost' => $usersLost
                ],
                'acquisition_chart' => $acquisition
            ]);

        } catch (\Throwable $e) {
            Log::error("User Analytics Failed: " . $e->getMessage());
            return $this->safeErrorResponse($e);
        }
    }

    /**
     * 3. Product Performance
     * Endpoint: /api/v1/admin/reports/analytics/products
     * FIX: Module 18 - Fix N+1 Query Loops (High)
     */
    public function analyticsProducts(Request $request): JsonResponse
    {
        try {
            /* * DELETED: The N+1 Loop that caused DB Thrashing.
             * Original Code:
             * $products = Product::all()->map(function($product) {
             * $inventory = DB::table('bulk_purchases')->where('product_id', $product->id)->sum(...); // Query 1 inside loop
             * $investors = DB::table('user_investments')->where('product_id', $product->id)->count(); // Query 2 inside loop
             * });
             * REASON: Iterating 50 products resulted in 101 queries.
             */

            // ADDED: Eloquent Aggregates to fetch all data in 2 optimized queries.
            $products = Product::select('id', 'name', 'sector', 'min_investment')
                // Eager load sums from relation
                ->withSum('bulkPurchases as total_inventory', 'total_value_received')
                ->withSum('bulkPurchases as remaining_inventory', 'value_remaining')
                // Eager load counts from relation (assuming 'investments' relation exists)
                ->withCount('investments as investor_count') 
                ->get()
                ->map(function ($product) {
                    // In-memory math (O(N) CPU is fine here vs O(N) DB queries)
                    $total = $product->total_inventory ?? 0;
                    $remaining = $product->remaining_inventory ?? 0;
                    $soldValue = $total - $remaining;
                    $soldPercentage = $total > 0 ? round(($soldValue / $total) * 100, 1) : 0;

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sector' => $product->sector ?? 'General',
                        'total_inventory' => (float) $total,
                        'sold_value' => (float) $soldValue,
                        'sold_percentage' => (float) $soldPercentage,
                        'investor_count' => (int) ($product->investor_count ?? 0),
                        'total_raised' => (float) $soldValue
                    ];
                });

            return response()->json($products);

        } catch (\Throwable $e) {
            Log::error("Product Stats Failed: " . $e->getMessage());
            return response()->json([]);
        }
    }

    // =======================================================================
    // PART 2: ADVANCED ANALYTICS & EXPORTS
    // =======================================================================

    /**
     * Get User Growth & Churn Analytics (Full Service Logic)
     */
    public function getUserAnalytics(Request $request)
    {
        if (!$this->service) return response()->json(['message' => 'Service not available'], 200);

        $start = $request->query('start_date', now()->subYear());
        $end = $request->query('end_date', now());

        return response()->json([
            'acquisition_chart' => $this->service->getUserGrowth($start, $end),
            'retention_metrics' => $this->service->getRetentionMetrics($start, $end),
        ]);
    }

    /**
     * Get Inventory Summary
     */
    public function getInventorySummary(Request $request)
    {
        if (!$this->inventoryService) {
            return response()->json([], 200);
        }

        try {
            $products = Product::where('status', 'active')->get();
            
            $summary = $products->map(function ($product) {
                $stats = method_exists($this->inventoryService, 'getProductInventoryStats') 
                    ? $this->inventoryService->getProductInventoryStats($product)
                    : (object)['total' => 0, 'available' => 0, 'sold_percentage' => 0];

                $suggestion = method_exists($this->inventoryService, 'getReorderSuggestion')
                    ? $this->inventoryService->getReorderSuggestion($product)
                    : 'N/A';

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'total_inventory' => $stats->total,
                    'available_inventory' => $stats->available,
                    'sold_percentage' => $stats->sold_percentage,
                    'is_low_stock' => method_exists($this->inventoryService, 'checkLowStock') 
                        ? $this->inventoryService->checkLowStock($product) 
                        : false,
                    'forecast' => $suggestion
                ];
            });

            return response()->json($summary);

        } catch (\Throwable $e) {
            Log::error("Inventory Report Error: " . $e->getMessage());
            return response()->json(['error' => 'Could not load inventory data', 'data' => []], 200);
        }
    }

    /**
     * EXPORT HANDLER: GST, TDS, P&L, AML, Audit Trail
     * FIX: Module 18 - Synchronous Export Risk (Critical)
     * Replaced synchronous PDF generation with Async Job Dispatch.
     */
    public function exportReport(Request $request)
    {
        if (!$this->service) return response()->json(['message' => 'Report Service not configured'], 500);

        try {
            $type = $request->query('report_type');
            $format = $request->query('format', 'csv');
            $start = $request->query('start_date', now()->startOfYear()->toDateString());
            $end = $request->query('end_date', now()->endOfYear()->toDateString());
            
            /*
             * DELETED: Synchronous Generation Code
             * $data = $this->service->getData(...);
             * $pdf = Pdf::loadView(...)->download();
             * * REASON: Generating large reports (e.g., Full Year Audit Trail) synchronously 
             * blocks the PHP-FPM worker and causes Gateway Timeouts (504) for the user.
             */

            // ADDED: Async Job Dispatch Pattern
            
            // 1. Create a DB record to track the job status (Allows polling UI)
            $jobRecord = DataExportJobModel::create([
                'user_id' => Auth::id() ?? 1, // Fallback for testing/cli
                'report_type' => $type,
                'format' => $format,
                'parameters' => json_encode(['start_date' => $start, 'end_date' => $end]),
                'status' => 'pending',
            ]);

            // 2. Dispatch the heavy job to the queue
            DataExportJob::dispatch($jobRecord, $start, $end);

            // 3. Return immediate "Accepted" response
            return response()->json([
                'message' => 'Report generation started. You will be notified when it is ready.',
                'job_id' => $jobRecord->id,
                'status' => 'pending'
            ]);

        } catch (\Throwable $e) {
            Log::error("Export Dispatch Failed: " . $e->getMessage());
            return response()->json(['message' => 'Export initiation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Returns a safe JSON structure on error instead of crashing.
     */
    private function safeErrorResponse(\Throwable $e): JsonResponse
    {
        return response()->json([
            'message' => 'Report generation failed.',
            'debug_error' => $e->getMessage(),
            'data' => [],
            'summary' => ['revenue' => 0, 'payouts' => 0, 'net_profit' => 0],
            'counts' => ['total' => 0, 'active' => 0],
        ], 200); 
    }
}