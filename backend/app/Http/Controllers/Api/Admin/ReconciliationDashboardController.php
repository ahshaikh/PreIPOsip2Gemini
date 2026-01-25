<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * P0 FIX (GAP 28-30): Admin Reconciliation Dashboard Controller
 *
 * PURPOSE:
 * Provide admin endpoints for platform financial oversight:
 * - Cash position visibility
 * - Share lifecycle tracing
 * - Full reconciliation dashboard
 *
 * AUTHORIZATION:
 * All endpoints require admin role (super_admin or finance_admin).
 */
class ReconciliationDashboardController extends Controller
{
    protected PlatformReconciliationService $reconciliationService;

    public function __construct(PlatformReconciliationService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
    }

    /**
     * GAP 28: Get platform cash position
     *
     * GET /api/admin/reconciliation/cash-position
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCashPosition(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');

        $data = $this->reconciliationService->getPlatformCashPosition(
            $companyId ? (int) $companyId : null
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * GAP 28: Get daily cash flow
     *
     * GET /api/admin/reconciliation/cash-flow
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCashFlow(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $data = $this->reconciliationService->getDailyCashFlow(
            Carbon::parse($request->input('start_date')),
            Carbon::parse($request->input('end_date')),
            $request->input('company_id') ? (int) $request->input('company_id') : null
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * GAP 29: Trace share lifecycle for a bulk purchase
     *
     * GET /api/admin/reconciliation/share-lifecycle/{bulkPurchaseId}
     *
     * @param int $bulkPurchaseId
     * @return JsonResponse
     */
    public function traceShareLifecycle(int $bulkPurchaseId): JsonResponse
    {
        $data = $this->reconciliationService->traceShareLifecycle($bulkPurchaseId);

        if (isset($data['error'])) {
            return response()->json([
                'status' => 'error',
                'message' => $data['error'],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * GAP 29: Trace provenance for an investment
     *
     * GET /api/admin/reconciliation/investment-provenance/{investmentId}
     *
     * @param int $investmentId
     * @return JsonResponse
     */
    public function traceInvestmentProvenance(int $investmentId): JsonResponse
    {
        $data = $this->reconciliationService->traceInvestmentProvenance($investmentId);

        if (isset($data['error'])) {
            return response()->json([
                'status' => 'error',
                'message' => $data['error'],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * GAP 30: Get complete reconciliation dashboard
     *
     * GET /api/admin/reconciliation/dashboard
     *
     * Single view showing:
     * - Shares bought / sold / remaining
     * - Cash in / out
     * - Reconciliation checks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');

        $data = $this->reconciliationService->getReconciliationDashboard(
            $companyId ? (int) $companyId : null
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Get unreconciled items for investigation
     *
     * GET /api/admin/reconciliation/unreconciled
     *
     * @return JsonResponse
     */
    public function getUnreconciledItems(): JsonResponse
    {
        $data = $this->reconciliationService->getUnreconciledItems();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Export reconciliation report
     *
     * GET /api/admin/reconciliation/export
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'nullable|in:json,csv',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $request->input('company_id');
        $format = $request->input('format', 'json');

        $dashboard = $this->reconciliationService->getReconciliationDashboard(
            $companyId ? (int) $companyId : null
        );

        $unreconciledItems = $this->reconciliationService->getUnreconciledItems();

        $report = [
            'report_type' => 'Platform Reconciliation Report',
            'generated_at' => now()->toIso8601String(),
            'generated_by' => auth()->user()?->name ?? 'System',
            'filter' => [
                'company_id' => $companyId,
            ],
            'dashboard' => $dashboard,
            'unreconciled_items' => $unreconciledItems,
        ];

        if ($format === 'csv') {
            // For CSV, return summary data
            return response()->json([
                'status' => 'success',
                'message' => 'CSV export not yet implemented. Use JSON format.',
                'data' => $report,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $report,
        ]);
    }
}
