<?php
/**
 * V-AUDIT-FIX-2026: Inventory Traceability Controller
 *
 * Admin endpoints for inventory audit trail and conservation verification.
 * Answers: "Where did the shares go?"
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\InventoryTraceabilityReportService;
use App\Jobs\InventoryReconciliationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryTraceabilityController extends Controller
{
    protected InventoryTraceabilityReportService $reportService;

    public function __construct(InventoryTraceabilityReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get traceability report for a specific bulk purchase.
     *
     * GET /api/v1/admin/inventory/{bulkPurchaseId}/trace
     *
     * @param int $bulkPurchaseId
     * @return JsonResponse
     */
    public function traceBulkPurchase(int $bulkPurchaseId): JsonResponse
    {
        $report = $this->reportService->generateReport($bulkPurchaseId);

        if (isset($report['error'])) {
            return response()->json(['error' => $report['error']], 404);
        }

        return response()->json([
            'data' => $report,
            'message' => $report['conservation_verified']
                ? 'Conservation verified'
                : 'WARNING: Conservation violation detected',
        ], $report['conservation_verified'] ? 200 : 409);
    }

    /**
     * Get traceability report for all batches of a product.
     *
     * GET /api/v1/admin/products/{productId}/inventory/trace
     *
     * @param int $productId
     * @return JsonResponse
     */
    public function traceProduct(int $productId): JsonResponse
    {
        $report = $this->reportService->generateProductReport($productId);

        if (isset($report['error'])) {
            return response()->json(['error' => $report['error']], 404);
        }

        return response()->json([
            'data' => $report,
            'message' => $report['summary']['conservation_verified']
                ? 'All batches pass conservation check'
                : 'WARNING: One or more batches have conservation violations',
        ]);
    }

    /**
     * Get platform-wide inventory summary.
     *
     * GET /api/v1/admin/inventory/summary
     *
     * @return JsonResponse
     */
    public function platformSummary(): JsonResponse
    {
        $summary = $this->reportService->generatePlatformSummary();

        return response()->json([
            'data' => $summary,
            'message' => $summary['conservation_check']['status'] === 'PASS'
                ? 'Platform inventory is conserved'
                : 'WARNING: Platform-wide conservation discrepancy detected',
        ]);
    }

    /**
     * Dispatch inventory reconciliation job.
     *
     * POST /api/v1/admin/inventory/reconcile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dispatchReconciliation(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');

        InventoryReconciliationJob::dispatch($productId);

        return response()->json([
            'message' => 'Inventory reconciliation job dispatched',
            'scope' => $productId ? "Product #{$productId}" : 'All products',
        ], 202);
    }
}
