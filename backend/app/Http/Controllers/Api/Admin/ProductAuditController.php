<?php
/**
 * Product Audit Trail API Controller
 *
 * Provides endpoints for viewing and analyzing product audit logs.
 * Part of FIX 48: Product Audit Trail implementation.
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAuditController extends Controller
{
    /**
     * Get product audit logs with filtering and search
     * GET /api/v1/admin/product-audits
     */
    public function index(Request $request)
    {
        $query = ProductAudit::with(['product:id,name,slug', 'performedBy:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by action type
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user who performed the action
        if ($request->has('performed_by')) {
            $query->where('performed_by', $request->performed_by);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by critical changes only
        if ($request->has('critical_only') && $request->critical_only) {
            $query->where(function ($q) {
                $q->whereJsonContains('changed_fields', 'status')
                  ->orWhereJsonContains('changed_fields', 'current_market_price')
                  ->orWhereJsonContains('changed_fields', 'face_value_per_unit')
                  ->orWhereJsonContains('changed_fields', 'sebi_approval_number');
            });
        }

        // Search in descriptions and reasons
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($productQuery) use ($search) {
                      $productQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $audits = $query->paginate($request->get('per_page', 50));

        // Get statistics
        $stats = [
            'total_audits' => ProductAudit::count(),
            'today_audits' => ProductAudit::whereDate('created_at', today())->count(),
            'critical_changes' => ProductAudit::where(function ($q) {
                $q->whereJsonContains('changed_fields', 'status')
                  ->orWhereJsonContains('changed_fields', 'current_market_price')
                  ->orWhereJsonContains('changed_fields', 'sebi_approval_number');
            })->count(),
            'unique_products' => ProductAudit::distinct('product_id')->count(),
            'by_action' => ProductAudit::select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->pluck('count', 'action'),
            'recent_price_changes' => ProductAudit::where('action', 'price_updated')
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        return response()->json([
            'audits' => $audits,
            'stats' => $stats,
        ]);
    }

    /**
     * Get audit log for a specific product
     * GET /api/v1/admin/products/{product}/audits
     */
    public function productAudits(Product $product, Request $request)
    {
        $query = $product->audits()
            ->with('performedBy:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filter by action type
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits = $query->paginate($request->get('per_page', 50));

        // Product-specific stats
        $stats = [
            'total_changes' => $product->audits()->count(),
            'last_change' => $product->audits()->latest()->first()?->created_at,
            'by_action' => $product->audits()
                ->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->pluck('count', 'action'),
            'price_change_count' => $product->audits()->where('action', 'price_updated')->count(),
            'activation_count' => $product->audits()->where('action', 'activated')->count(),
        ];

        return response()->json([
            'product' => $product->only(['id', 'name', 'slug', 'status']),
            'audits' => $audits,
            'stats' => $stats,
        ]);
    }

    /**
     * Get specific audit log with full details
     * GET /api/v1/admin/product-audits/{audit}
     */
    public function show(ProductAudit $audit)
    {
        $audit->load(['product', 'performedBy']);

        // Get change summary
        $changeSummary = $audit->getChangeSummary();

        // Check if critical change
        $isCritical = $audit->isCriticalChange();

        return response()->json([
            'audit' => $audit,
            'change_summary' => $changeSummary,
            'is_critical' => $isCritical,
        ]);
    }

    /**
     * Compare two audit versions
     * GET /api/v1/admin/product-audits/compare
     */
    public function compare(Request $request)
    {
        $request->validate([
            'audit_id_1' => 'required|exists:product_audits,id',
            'audit_id_2' => 'required|exists:product_audits,id',
        ]);

        $audit1 = ProductAudit::with('product')->findOrFail($request->audit_id_1);
        $audit2 = ProductAudit::with('product')->findOrFail($request->audit_id_2);

        // Ensure both audits are for the same product
        if ($audit1->product_id !== $audit2->product_id) {
            return response()->json([
                'error' => 'Cannot compare audits from different products'
            ], 400);
        }

        // Get differences
        $differences = [];
        $allFields = array_unique(array_merge(
            array_keys($audit1->new_values ?? []),
            array_keys($audit2->new_values ?? [])
        ));

        foreach ($allFields as $field) {
            $value1 = $audit1->new_values[$field] ?? null;
            $value2 = $audit2->new_values[$field] ?? null;

            if ($value1 !== $value2) {
                $differences[$field] = [
                    'audit_1_value' => $value1,
                    'audit_2_value' => $value2,
                    'changed' => true,
                ];
            } else {
                $differences[$field] = [
                    'value' => $value1,
                    'changed' => false,
                ];
            }
        }

        return response()->json([
            'audit_1' => $audit1,
            'audit_2' => $audit2,
            'differences' => $differences,
        ]);
    }

    /**
     * Get audit timeline for visualization
     * GET /api/v1/admin/products/{product}/audit-timeline
     */
    public function timeline(Product $product)
    {
        $timeline = $product->audits()
            ->select('id', 'action', 'changed_fields', 'created_at', 'performed_by')
            ->with('performedBy:id,name')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'action' => $audit->action,
                    'timestamp' => $audit->created_at->toISOString(),
                    'user' => $audit->performedBy?->name ?? 'System',
                    'fields_changed' => count($audit->changed_fields ?? []),
                    'is_critical' => $audit->isCriticalChange(),
                ];
            });

        return response()->json([
            'product' => $product->only(['id', 'name']),
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get price change history for a product
     * GET /api/v1/admin/products/{product}/price-history
     */
    public function priceHistory(Product $product)
    {
        $priceChanges = $product->audits()
            ->where(function ($q) {
                $q->whereJsonContains('changed_fields', 'current_market_price')
                  ->orWhereJsonContains('changed_fields', 'face_value_per_unit');
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($audit) {
                $priceData = [];

                if ($audit->hasFieldChanged('current_market_price')) {
                    $change = $audit->getFieldChange('current_market_price');
                    $priceData['current_market_price'] = $change;
                }

                if ($audit->hasFieldChanged('face_value_per_unit')) {
                    $change = $audit->getFieldChange('face_value_per_unit');
                    $priceData['face_value_per_unit'] = $change;
                }

                return [
                    'timestamp' => $audit->created_at,
                    'performed_by' => $audit->performedBy?->name ?? 'System',
                    'changes' => $priceData,
                    'reason' => $audit->reason,
                ];
            });

        return response()->json([
            'product' => $product->only(['id', 'name', 'current_market_price', 'face_value_per_unit']),
            'price_changes' => $priceChanges,
        ]);
    }

    /**
     * Export audit logs to CSV
     * GET /api/v1/admin/product-audits/export
     */
    public function export(Request $request)
    {
        $query = ProductAudit::with(['product:id,name', 'performedBy:id,name'])
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits = $query->limit(10000)->get(); // Limit to prevent memory issues

        $csvData = [];
        $csvData[] = ['ID', 'Product', 'Action', 'Changed Fields', 'Performed By', 'IP Address', 'Timestamp'];

        foreach ($audits as $audit) {
            $csvData[] = [
                $audit->id,
                $audit->product->name ?? 'N/A',
                $audit->action,
                implode(', ', $audit->changed_fields ?? []),
                $audit->performedBy->name ?? 'System',
                $audit->ip_address,
                $audit->created_at->toDateTimeString(),
            ];
        }

        $filename = 'product_audits_' . now()->format('Y-m-d_His') . '.csv';

        return response()->json([
            'filename' => $filename,
            'data' => $csvData,
            'row_count' => count($csvData) - 1, // Exclude header
        ]);
    }
}
