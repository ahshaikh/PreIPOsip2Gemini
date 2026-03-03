<?php

/**
 * V-DISPUTE-RISK-2026-010: Admin Dispute Controller
 * V-DISPUTE-MGMT-2026: Enhanced with full dispute management capabilities
 *
 * Read and write endpoints for dispute and chargeback management.
 * All endpoints are RBAC-protected.
 *
 * READ ENDPOINTS:
 * - GET /admin/api/disputes         - List all disputes (paginated, filterable)
 * - GET /admin/api/disputes/overview - Cached statistics overview
 * - GET /admin/api/disputes/:id     - Single dispute details
 * - GET /admin/api/disputes/:id/ledger - Related ledger entries
 * - GET /admin/api/disputes/:id/risk - User risk profile
 * - GET /admin/api/disputes/:id/audit - Related audit logs
 *
 * MANAGEMENT ENDPOINTS (V-DISPUTE-MGMT-2026):
 * - POST /admin/api/disputes/:id/transition - Transition dispute status
 * - POST /admin/api/disputes/:id/resolve    - Resolve dispute (approve/reject)
 * - POST /admin/api/disputes/:id/close      - Close dispute
 * - POST /admin/api/disputes/:id/comment    - Add comment
 * - POST /admin/api/disputes/:id/request-response - Request investor response
 * - POST /admin/api/disputes/:id/escalate   - Escalate dispute
 * - POST /admin/api/disputes/:id/assign     - Assign to admin
 * - GET  /admin/api/disputes/:id/verify-integrity - Verify snapshot integrity
 * - GET  /admin/api/disputes/integrity-audit - Batch integrity check
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveDisputeRequest;
use App\Http\Requests\Admin\TransitionDisputeRequest;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Services\DisputeService;
use App\Services\DisputeStateMachine;
use App\Services\DisputeStatsCache;
use App\Services\RiskScoringService;
use App\Services\SnapshotIntegrityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DisputeController extends Controller
{
    protected DisputeStatsCache $statsCache;
    protected RiskScoringService $riskService;
    protected DisputeService $disputeService;
    protected SnapshotIntegrityService $integrityService;

    public function __construct(
        DisputeStatsCache $statsCache,
        RiskScoringService $riskService,
        DisputeService $disputeService,
        SnapshotIntegrityService $integrityService
    ) {
        $this->statsCache = $statsCache;
        $this->riskService = $riskService;
        $this->disputeService = $disputeService;
        $this->integrityService = $integrityService;
    }

    /**
     * List all disputes with filtering and pagination.
     *
     * GET /admin/api/disputes
     *
     * Query parameters:
     * - status: Filter by status (open, under_investigation, resolved, closed, escalated)
     * - severity: Filter by severity (low, medium, high, critical)
     * - category: Filter by category
     * - company_id: Filter by company
     * - user_id: Filter by user
     * - search: Search in title/description
     * - sort: Sort field (created_at, severity, status)
     * - order: Sort order (asc, desc)
     * - per_page: Items per page (default: 20, max: 100)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dispute::with([
            'company:id,name,slug',
            'user:id,username,email',
            'raisedBy:id,username,email',
            'assignedAdmin:id,username,email',
        ]);

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Severity filter
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        // Category filter
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // User filter
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Active only filter
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $allowedSorts = ['created_at', 'severity', 'status', 'opened_at', 'resolved_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->latest();
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Get cached dispute overview statistics.
     *
     * GET /admin/api/disputes/overview
     *
     * Query parameters:
     * - refresh: Force cache refresh (boolean)
     */
    public function overview(Request $request): JsonResponse
    {
        $forceRefresh = $request->boolean('refresh');

        $stats = $this->statsCache->getAllStats($forceRefresh);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get single dispute details.
     *
     * GET /admin/api/disputes/:id
     */
    public function show(int $id): JsonResponse
    {
        $dispute = Dispute::with([
            'company:id,name,slug,status',
            'user:id,username,email,risk_score,is_blocked',
            'user.wallet:id,user_id,balance_paise',
            'raisedBy:id,username,email',
            'assignedAdmin:id,username,email',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $dispute,
        ]);
    }

    /**
     * Get ledger entries related to a dispute's user.
     *
     * GET /admin/api/disputes/:id/ledger
     *
     * Shows ledger entries for payments associated with the dispute's user.
     */
    public function ledger(int $id): JsonResponse
    {
        $dispute = Dispute::findOrFail($id);

        if (!$dispute->user_id) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No user associated with this dispute',
            ]);
        }

        // Get payment IDs for the user
        $paymentIds = Payment::where('user_id', $dispute->user_id)
            ->whereIn('status', [
                Payment::STATUS_PAID,
                Payment::STATUS_REFUNDED,
                Payment::STATUS_CHARGEBACK_CONFIRMED,
            ])
            ->pluck('id');

        // Get ledger entries related to those payments
        $ledgerEntries = LedgerEntry::where('reference_type', Payment::class)
            ->whereIn('reference_id', $paymentIds)
            ->with('lines.account')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ledgerEntries,
            'user_id' => $dispute->user_id,
            'payment_count' => $paymentIds->count(),
        ]);
    }

    /**
     * Get user risk profile for a dispute.
     *
     * GET /admin/api/disputes/:id/risk
     *
     * Returns the user's current risk score, factors, and chargeback history.
     */
    public function risk(int $id): JsonResponse
    {
        $dispute = Dispute::findOrFail($id);

        if (!$dispute->user_id) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No user associated with this dispute',
            ]);
        }

        $user = User::with(['wallet', 'kyc'])->findOrFail($dispute->user_id);

        // Get risk summary
        $riskSummary = $this->riskService->getRiskSummary($user);

        // Get chargeback history
        $chargebackHistory = $this->riskService->getChargebackHistory($user);

        // Get open disputes count
        $openDisputes = Dispute::forUser($user->id)->active()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'created_at' => $user->created_at->toIso8601String(),
                    'kyc_status' => $user->kyc_status,
                ],
                'risk_profile' => $riskSummary,
                'chargeback_history' => $chargebackHistory,
                'open_disputes_count' => $openDisputes,
            ],
        ]);
    }

    /**
     * Get audit logs related to a dispute.
     *
     * GET /admin/api/disputes/:id/audit
     *
     * Returns audit logs for the dispute and related user risk changes.
     */
    public function audit(int $id): JsonResponse
    {
        $dispute = Dispute::findOrFail($id);

        // Get audit logs for the dispute itself
        $disputeAuditLogs = AuditLog::where('target_type', Dispute::class)
            ->where('target_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get risk-related audit logs for the user
        $userRiskLogs = [];
        if ($dispute->user_id) {
            $userRiskLogs = AuditLog::where('module', 'risk_management')
                ->where('target_type', User::class)
                ->where('target_id', $dispute->user_id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'dispute_logs' => $disputeAuditLogs,
                'user_risk_logs' => $userRiskLogs,
            ],
        ]);
    }

    /**
     * List chargebacks (specialized view for chargeback management).
     *
     * GET /admin/api/disputes/chargebacks
     *
     * Query parameters:
     * - status: chargeback_pending, chargeback_confirmed
     * - user_id: Filter by user
     * - date_from: Filter by date range start
     * - date_to: Filter by date range end
     */
    public function chargebacks(Request $request): JsonResponse
    {
        $query = Payment::with([
            'user:id,username,email,risk_score,is_blocked',
            'subscription:id,plan_id',
            'subscription.plan:id,name',
        ])
            ->whereIn('status', [
                Payment::STATUS_CHARGEBACK_PENDING,
                Payment::STATUS_CHARGEBACK_CONFIRMED,
            ]);

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // User filter
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filters
        if ($request->has('date_from')) {
            $query->whereDate('chargeback_initiated_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('chargeback_initiated_at', '<=', $request->date_to);
        }

        // Sorting
        $query->orderBy('chargeback_initiated_at', 'desc');

        // Pagination
        $perPage = min((int) $request->get('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * List blocked users.
     *
     * GET /admin/api/disputes/blocked-users
     */
    public function blockedUsers(Request $request): JsonResponse
    {
        $query = User::with(['wallet:id,user_id,balance_paise', 'kyc:user_id,status'])
            ->where('is_blocked', true)
            ->select([
                'id', 'username', 'email', 'risk_score',
                'is_blocked', 'blocked_reason', 'last_risk_update_at', 'created_at'
            ]);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $query->orderBy('last_risk_update_at', 'desc');

        // Pagination
        $perPage = min((int) $request->get('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    // =========================================================================
    // V-DISPUTE-MGMT-2026: DISPUTE MANAGEMENT ENDPOINTS
    // =========================================================================

    /**
     * Transition dispute to a new status.
     *
     * POST /admin/api/disputes/:id/transition
     */
    public function transition(TransitionDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        try {
            $targetStatus = $request->getTargetStatus();

            $dispute = app(DisputeStateMachine::class)->transition(
                $dispute,
                $targetStatus,
                $request->user(),
                $request->comment
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute status updated successfully.',
                'data' => $dispute->fresh(['timeline']),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid transition',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolve a dispute.
     *
     * POST /admin/api/disputes/:id/resolve
     */
    public function resolve(ResolveDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        try {
            if ($request->outcome === 'approved') {
                $dispute = $this->disputeService->resolveApproved(
                    $dispute,
                    $request->user(),
                    $request->resolution,
                    $request->settlement_action,
                    $request->getSettlementAmountPaise(),
                    $request->settlement_details ?? []
                );
            } else {
                $dispute = $this->disputeService->resolveRejected(
                    $dispute,
                    $request->user(),
                    $request->resolution
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Dispute resolved successfully.',
                'data' => $dispute->fresh(['timeline']),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Resolution failed',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Resolution failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close a dispute.
     *
     * POST /admin/api/disputes/:id/close
     */
    public function close(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $dispute = $this->disputeService->close(
                $dispute,
                $request->user(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute closed successfully.',
                'data' => $dispute,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Close failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add a comment to a dispute.
     *
     * POST /admin/api/disputes/:id/comment
     */
    public function addComment(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'comment' => 'required|string|max:5000',
            'is_internal' => 'boolean',
            'attachments' => 'nullable|array',
        ]);

        $timeline = $this->disputeService->addComment(
            $dispute,
            $request->user(),
            $request->comment,
            $request->boolean('is_internal'),
            $request->attachments ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'data' => $timeline,
        ], 201);
    }

    /**
     * Request investor response.
     *
     * POST /admin/api/disputes/:id/request-response
     */
    public function requestResponse(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:2000',
        ]);

        try {
            $dispute = $this->disputeService->requestInvestorResponse(
                $dispute,
                $request->user(),
                $request->question
            );

            return response()->json([
                'success' => true,
                'message' => 'Response requested from investor.',
                'data' => $dispute->fresh(['timeline']),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Request failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Escalate a dispute.
     *
     * POST /admin/api/disputes/:id/escalate
     */
    public function escalate(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        try {
            $dispute = $this->disputeService->escalate(
                $dispute,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute escalated successfully.',
                'data' => $dispute->fresh(['timeline']),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Escalation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Assign dispute to an admin.
     *
     * POST /admin/api/disputes/:id/assign
     */
    public function assign(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'admin_id' => 'required|integer|exists:users,id',
        ]);

        $admin = User::findOrFail($request->admin_id);

        // Verify the target user is an admin
        if (!$admin->hasRole('admin') && !$admin->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid assignment',
                'message' => 'Target user is not an admin.',
            ], 422);
        }

        $dispute = $this->disputeService->assignToAdmin(
            $dispute,
            $admin,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Dispute assigned successfully.',
            'data' => $dispute,
        ]);
    }

    /**
     * Verify snapshot integrity.
     *
     * GET /admin/api/disputes/:id/verify-integrity
     */
    public function verifyIntegrity(Dispute $dispute): JsonResponse
    {
        $result = $this->integrityService->verifyForDispute($dispute);

        return response()->json([
            'success' => true,
            'dispute_id' => $dispute->id,
            'integrity' => $result,
        ]);
    }

    /**
     * Run batch integrity check.
     *
     * GET /admin/api/disputes/integrity-audit
     */
    public function integrityAudit(): JsonResponse
    {
        $results = $this->integrityService->verifyAll();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get recommended settlement for a dispute.
     *
     * GET /admin/api/disputes/:id/recommended-settlement
     */
    public function recommendedSettlement(Dispute $dispute): JsonResponse
    {
        $recommendation = app(\App\Services\DisputeSettlementOrchestrator::class)
            ->getRecommendedAction($dispute);

        return response()->json([
            'success' => true,
            'dispute_id' => $dispute->id,
            'recommendation' => $recommendation,
        ]);
    }
}
