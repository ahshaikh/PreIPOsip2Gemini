<?php

/**
 * V-DISPUTE-RISK-2026-010: Admin Dispute Controller
 *
 * Read-only endpoints for dispute and chargeback management.
 * All endpoints are RBAC-protected and produce no financial mutations.
 *
 * ENDPOINTS:
 * - GET /admin/api/disputes         - List all disputes (paginated, filterable)
 * - GET /admin/api/disputes/overview - Cached statistics overview
 * - GET /admin/api/disputes/:id     - Single dispute details
 * - GET /admin/api/disputes/:id/ledger - Related ledger entries
 * - GET /admin/api/disputes/:id/risk - User risk profile
 * - GET /admin/api/disputes/:id/audit - Related audit logs
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Services\DisputeStatsCache;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DisputeController extends Controller
{
    protected DisputeStatsCache $statsCache;
    protected RiskScoringService $riskService;

    public function __construct(DisputeStatsCache $statsCache, RiskScoringService $riskService)
    {
        $this->statsCache = $statsCache;
        $this->riskService = $riskService;
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
}
