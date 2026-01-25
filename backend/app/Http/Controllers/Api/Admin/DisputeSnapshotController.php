<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DisputeDefensibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * P0 FIX (GAP 31): Admin Dispute Snapshot Controller
 *
 * PURPOSE:
 * Provide admin endpoints for retrieving complete dispute snapshots.
 * Enables legal/compliance team to gather all evidence for dispute resolution.
 *
 * AUTHORIZATION:
 * Requires admin role with compliance or legal permissions.
 */
class DisputeSnapshotController extends Controller
{
    protected DisputeDefensibilityService $defensibilityService;

    public function __construct(DisputeDefensibilityService $defensibilityService)
    {
        $this->defensibilityService = $defensibilityService;
    }

    /**
     * GAP 31: Get complete dispute snapshot for an investment
     *
     * GET /api/admin/disputes/snapshot/{investmentId}
     *
     * Returns all information needed to defend a dispute:
     * - Platform context at time of investment
     * - Investor journey and acknowledgements
     * - Disclosure versions shown
     * - Risk flags active
     * - Admin actions context
     * - Wallet transaction
     * - Allocation proof
     * - Immutability verification
     *
     * @param int $investmentId
     * @return JsonResponse
     */
    public function getSnapshot(int $investmentId): JsonResponse
    {
        $snapshot = $this->defensibilityService->getDisputeSnapshot($investmentId);

        if (isset($snapshot['error'])) {
            return response()->json([
                'status' => 'error',
                'message' => $snapshot['error'],
            ], 404);
        }

        // Log access for audit
        \Log::info('DISPUTE SNAPSHOT ACCESSED', [
            'investment_id' => $investmentId,
            'accessed_by' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $snapshot,
        ]);
    }

    /**
     * GAP 33: Verify state machine integrity for an investment
     *
     * GET /api/admin/disputes/verify-integrity/{investmentId}
     *
     * @param int $investmentId
     * @return JsonResponse
     */
    public function verifyIntegrity(int $investmentId): JsonResponse
    {
        $result = $this->defensibilityService->verifyStateMachineIntegrity($investmentId);

        if (isset($result['error'])) {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Export dispute package for legal team
     *
     * GET /api/admin/disputes/export/{investmentId}
     *
     * @param int $investmentId
     * @return JsonResponse
     */
    public function exportForLegal(int $investmentId): JsonResponse
    {
        $export = $this->defensibilityService->exportForLegal($investmentId);

        if (isset($export['error'])) {
            return response()->json([
                'status' => 'error',
                'message' => $export['error'],
            ], 404);
        }

        // Log export for audit
        \Log::info('DISPUTE EXPORT GENERATED', [
            'investment_id' => $investmentId,
            'exported_by' => auth()->id(),
            'case_reference' => $export['case_reference'] ?? null,
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $export,
        ]);
    }

    /**
     * Search for investments by criteria for dispute investigation
     *
     * GET /api/admin/disputes/search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchInvestments(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|string',
            'min_amount' => 'nullable|numeric',
            'max_amount' => 'nullable|numeric',
        ]);

        $query = \App\Models\CompanyInvestment::with(['user:id,name,email', 'company:id,name']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        $investments = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $investments,
        ]);
    }
}
