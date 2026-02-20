<?php
/**
 * V-AUDIT-FIX-2026: Snapshot Integrity Controller
 *
 * Admin endpoints for verifying investment snapshot integrity.
 * Detects tampering by recomputing and comparing hashes.
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SnapshotIntegrityAuditJob;
use App\Services\InvestmentSnapshotService;
use App\Services\PlatformContextSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SnapshotIntegrityController extends Controller
{
    protected InvestmentSnapshotService $snapshotService;

    public function __construct(InvestmentSnapshotService $snapshotService)
    {
        $this->snapshotService = $snapshotService;
    }

    /**
     * Verify integrity of a specific snapshot.
     *
     * GET /api/v1/admin/snapshots/{snapshotId}/verify
     *
     * @param int $snapshotId
     * @return JsonResponse
     */
    public function verifySnapshot(int $snapshotId): JsonResponse
    {
        $result = $this->snapshotService->verifySnapshotIntegrity($snapshotId);

        $statusCode = $result['verified'] ? 200 : ($result['tamper_detected'] ? 409 : 404);

        return response()->json([
            'data' => $result,
            'message' => $result['verified']
                ? 'Snapshot integrity verified'
                : ($result['tamper_detected'] ? 'Tampering detected' : 'Verification failed'),
        ], $statusCode);
    }

    /**
     * Verify all snapshots for a company.
     *
     * GET /api/v1/admin/companies/{companyId}/snapshots/verify
     *
     * @param int $companyId
     * @return JsonResponse
     */
    public function verifyCompanySnapshots(int $companyId): JsonResponse
    {
        $result = $this->snapshotService->verifyAllSnapshotsForCompany($companyId);

        $hasViolations = $result['tampered'] > 0;
        $statusCode = $hasViolations ? 409 : 200;

        return response()->json([
            'data' => $result,
            'message' => $hasViolations
                ? "Tampering detected in {$result['tampered']} snapshot(s)"
                : 'All snapshots verified',
        ], $statusCode);
    }

    /**
     * Dispatch full integrity audit job.
     *
     * POST /api/v1/admin/snapshots/audit
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dispatchAudit(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');
        $snapshotId = $request->input('snapshot_id');

        SnapshotIntegrityAuditJob::dispatch($companyId, $snapshotId);

        return response()->json([
            'message' => 'Snapshot integrity audit job dispatched',
            'scope' => [
                'company_id' => $companyId ?? 'all',
                'snapshot_id' => $snapshotId ?? 'all',
            ],
        ], 202);
    }

    /**
     * Get integrity audit summary (recent results).
     *
     * GET /api/v1/admin/snapshots/audit/summary
     *
     * @return JsonResponse
     */
    public function auditSummary(): JsonResponse
    {
        // Get recent integrity alerts
        $recentAlerts = \DB::table('admin_alerts')
            ->where('alert_type', 'snapshot_integrity_violation')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'title' => $alert->title,
                    'severity' => $alert->severity,
                    'created_at' => $alert->created_at,
                    'is_read' => $alert->is_read,
                    'context' => json_decode($alert->context, true),
                ];
            });

        // Get snapshot statistics
        $totalSnapshots = \DB::table('investment_disclosure_snapshots')->count();
        $snapshotsWithHash = \DB::table('investment_disclosure_snapshots')
            ->whereNotNull('disclosure_snapshot')
            ->count();

        return response()->json([
            'data' => [
                'total_snapshots' => $totalSnapshots,
                'snapshots_verifiable' => $snapshotsWithHash,
                'recent_violations' => $recentAlerts,
                'last_audit' => $recentAlerts->first()?->created_at ?? null,
            ],
        ]);
    }

    /**
     * V-AUDIT-FIX-2026: Compare two platform context snapshots.
     *
     * GET /api/v1/admin/platform-snapshots/compare
     *
     * @param Request $request
     * @param PlatformContextSnapshotService $contextService
     * @return JsonResponse
     */
    public function comparePlatformSnapshots(
        Request $request,
        PlatformContextSnapshotService $contextService
    ): JsonResponse {
        $request->validate([
            'snapshot_id_1' => 'required|integer',
            'snapshot_id_2' => 'required|integer',
        ]);

        $result = $contextService->compareSnapshots(
            $request->input('snapshot_id_1'),
            $request->input('snapshot_id_2')
        );

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json(['data' => $result]);
    }

    /**
     * V-AUDIT-FIX-2026: Get platform context snapshot history for a company.
     *
     * GET /api/v1/admin/companies/{companyId}/platform-snapshots
     *
     * @param int $companyId
     * @param PlatformContextSnapshotService $contextService
     * @return JsonResponse
     */
    public function getPlatformSnapshotHistory(
        int $companyId,
        PlatformContextSnapshotService $contextService
    ): JsonResponse {
        $history = $contextService->getSnapshotHistory($companyId);

        return response()->json([
            'data' => [
                'company_id' => $companyId,
                'total_snapshots' => count($history),
                'snapshots' => $history,
            ],
        ]);
    }
}
