<?php
/**
 * Deal Approval Workflow API Controller
 *
 * Provides endpoints for managing deal approval workflow.
 * Part of FIX 49: Deal Approval Workflow implementation.
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\DealApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DealApprovalController extends Controller
{
    /**
     * Get all deal approvals with filtering
     * GET /api/v1/admin/deal-approvals
     */
    public function index(Request $request)
    {
        $query = DealApproval::with([
            'deal:id,title,company_id',
            'submitter:id,name,email',
            'reviewer:id,name,email',
            'approver:id,name,email',
        ])->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by deal
        if ($request->has('deal_id')) {
            $query->where('deal_id', $request->deal_id);
        }

        // Filter by reviewer
        if ($request->has('reviewed_by')) {
            $query->where('reviewed_by', $request->reviewed_by);
        }

        // Filter by overdue (SLA)
        if ($request->has('overdue') && $request->overdue) {
            $slaDays = $request->get('sla_days', 7);
            $query->whereIn('status', ['pending_review', 'under_review'])
                ->whereDate('submitted_at', '<=', now()->subDays($slaDays));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('deal', function ($dealQuery) use ($search) {
                    $dealQuery->where('title', 'like', "%{$search}%");
                })
                ->orWhere('rejection_reason', 'like', "%{$search}%")
                ->orWhere('approval_notes', 'like', "%{$search}%");
            });
        }

        $approvals = $query->paginate($request->get('per_page', 50));

        // Get statistics
        $stats = [
            'total_approvals' => DealApproval::count(),
            'pending_review' => DealApproval::where('status', 'pending_review')->count(),
            'under_review' => DealApproval::where('status', 'under_review')->count(),
            'approved' => DealApproval::where('status', 'approved')->count(),
            'rejected' => DealApproval::where('status', 'rejected')->count(),
            'published' => DealApproval::where('status', 'published')->count(),
            'overdue' => DealApproval::whereIn('status', ['pending_review', 'under_review'])
                ->whereDate('submitted_at', '<=', now()->subDays(7))
                ->count(),
            'avg_approval_time' => DealApproval::where('status', 'approved')
                ->whereNotNull('submitted_at')
                ->whereNotNull('approved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(DAY, submitted_at, approved_at)) as avg_days')
                ->value('avg_days'),
        ];

        return response()->json([
            'approvals' => $approvals,
            'stats' => $stats,
        ]);
    }

    /**
     * Get approval queue (pending and under review)
     * GET /api/v1/admin/deal-approvals/queue
     */
    public function queue(Request $request)
    {
        $query = DealApproval::with([
            'deal:id,title,company_id,valuation',
            'submitter:id,name,email',
        ])
        ->whereIn('status', ['pending_review', 'under_review'])
        ->orderBy('submitted_at', 'asc');

        // Filter by priority (overdue first)
        if ($request->get('priority_sort', true)) {
            $slaDays = $request->get('sla_days', 7);
            $query->orderByRaw("
                CASE
                    WHEN submitted_at <= DATE_SUB(NOW(), INTERVAL {$slaDays} DAY) THEN 1
                    ELSE 2
                END
            ");
        }

        $queue = $query->paginate($request->get('per_page', 20));

        // Add SLA status to each item
        $queue->getCollection()->transform(function ($approval) use ($request) {
            $slaDays = $request->get('sla_days', 7);
            $approval->is_overdue = $approval->isOverdue($slaDays);
            $approval->days_pending = $approval->submitted_at
                ? $approval->submitted_at->diffInDays(now())
                : null;
            return $approval;
        });

        return response()->json([
            'queue' => $queue,
        ]);
    }

    /**
     * Get specific deal approval
     * GET /api/v1/admin/deal-approvals/{approval}
     */
    public function show(DealApproval $approval)
    {
        $approval->load([
            'deal',
            'submitter:id,name,email',
            'reviewer:id,name,email',
            'approver:id,name,email',
            'rejector:id,name,email',
        ]);

        // Add computed fields
        $approval->approval_duration_days = $approval->getApprovalDuration();
        $approval->review_duration_days = $approval->getReviewDuration();
        $approval->is_overdue = $approval->isOverdue();
        $approval->can_approve = $approval->canApprove();
        $approval->can_reject = $approval->canReject();
        $approval->can_publish = $approval->canPublish();

        return response()->json([
            'approval' => $approval,
        ]);
    }

    /**
     * Submit deal for approval
     * POST /api/v1/admin/deals/{deal}/submit-for-approval
     */
    public function submit(Deal $deal, Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if deal already has an active approval
        $existingApproval = $deal->approvals()
            ->whereIn('status', ['pending_review', 'under_review', 'approved'])
            ->first();

        if ($existingApproval) {
            return response()->json([
                'error' => 'Deal already has an active approval process',
                'current_status' => $existingApproval->status,
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create new approval
            $approval = DealApproval::create([
                'deal_id' => $deal->id,
                'status' => 'draft',
            ]);

            // Submit for review
            $approval->submit($request->notes);

            DB::commit();

            return response()->json([
                'message' => 'Deal submitted for approval successfully',
                'approval' => $approval->load('deal', 'submitter'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to submit deal for approval',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start reviewing a deal
     * POST /api/v1/admin/deal-approvals/{approval}/start-review
     */
    public function startReview(DealApproval $approval)
    {
        if ($approval->status !== 'pending_review') {
            return response()->json([
                'error' => 'Can only start review for deals in pending_review status',
                'current_status' => $approval->status,
            ], 400);
        }

        try {
            $approval->startReview();

            return response()->json([
                'message' => 'Review started successfully',
                'approval' => $approval->fresh()->load('reviewer'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to start review',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a deal
     * POST /api/v1/admin/deal-approvals/{approval}/approve
     */
    public function approve(DealApproval $approval, Request $request)
    {
        $request->validate([
            'compliance_checklist' => 'required|array',
            'compliance_checklist.*.item' => 'required|string',
            'compliance_checklist.*.checked' => 'required|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (!$approval->canApprove()) {
            return response()->json([
                'error' => 'Deal cannot be approved in current status',
                'current_status' => $approval->status,
            ], 400);
        }

        DB::beginTransaction();
        try {
            $approval->approve(
                $request->compliance_checklist,
                $request->notes
            );

            DB::commit();

            return response()->json([
                'message' => 'Deal approved successfully',
                'approval' => $approval->fresh()->load('approver', 'deal'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to approve deal',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a deal
     * POST /api/v1/admin/deal-approvals/{approval}/reject
     */
    public function reject(DealApproval $approval, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'feedback' => 'nullable|array',
        ]);

        if (!$approval->canReject()) {
            return response()->json([
                'error' => 'Deal cannot be rejected in current status',
                'current_status' => $approval->status,
            ], 400);
        }

        try {
            $approval->reject(
                $request->reason,
                $request->feedback
            );

            return response()->json([
                'message' => 'Deal rejected successfully',
                'approval' => $approval->fresh()->load('rejector', 'deal'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reject deal',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish an approved deal
     * POST /api/v1/admin/deal-approvals/{approval}/publish
     */
    public function publish(DealApproval $approval)
    {
        if (!$approval->canPublish()) {
            return response()->json([
                'error' => 'Only approved deals can be published',
                'current_status' => $approval->status,
            ], 400);
        }

        DB::beginTransaction();
        try {
            $approval->publish();

            DB::commit();

            return response()->json([
                'message' => 'Deal published successfully',
                'approval' => $approval->fresh()->load('deal'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to publish deal',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get approval statistics and analytics
     * GET /api/v1/admin/deal-approvals/analytics
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $analytics = [
            // Overall metrics
            'total_submissions' => DealApproval::whereBetween('submitted_at', [$dateFrom, $dateTo])->count(),
            'total_approved' => DealApproval::where('status', 'approved')
                ->whereBetween('approved_at', [$dateFrom, $dateTo])
                ->count(),
            'total_rejected' => DealApproval::where('status', 'rejected')
                ->whereBetween('rejected_at', [$dateFrom, $dateTo])
                ->count(),

            // Approval rates
            'approval_rate' => 0,
            'rejection_rate' => 0,

            // Time metrics
            'avg_approval_time_days' => DealApproval::where('status', 'approved')
                ->whereBetween('approved_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(DAY, submitted_at, approved_at)) as avg_days')
                ->value('avg_days'),

            'avg_review_time_days' => DealApproval::whereNotNull('review_started_at')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(DAY, review_started_at, COALESCE(approved_at, rejected_at, NOW()))) as avg_days')
                ->value('avg_days'),

            // Status breakdown
            'by_status' => DealApproval::whereBetween('created_at', [$dateFrom, $dateTo])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),

            // Top reviewers
            'top_reviewers' => DealApproval::whereNotNull('reviewed_by')
                ->whereBetween('review_started_at', [$dateFrom, $dateTo])
                ->select('reviewed_by', DB::raw('count(*) as count'))
                ->groupBy('reviewed_by')
                ->with('reviewer:id,name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),

            // SLA compliance
            'sla_compliance' => [
                'total_completed' => DealApproval::whereIn('status', ['approved', 'rejected', 'published'])
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->count(),
                'within_sla' => DealApproval::whereIn('status', ['approved', 'rejected', 'published'])
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereRaw('TIMESTAMPDIFF(DAY, submitted_at, COALESCE(approved_at, rejected_at)) <= 7')
                    ->count(),
            ],
        ];

        // Calculate rates
        $totalCompleted = $analytics['total_approved'] + $analytics['total_rejected'];
        if ($totalCompleted > 0) {
            $analytics['approval_rate'] = round(($analytics['total_approved'] / $totalCompleted) * 100, 2);
            $analytics['rejection_rate'] = round(($analytics['total_rejected'] / $totalCompleted) * 100, 2);
        }

        // Calculate SLA compliance rate
        if ($analytics['sla_compliance']['total_completed'] > 0) {
            $analytics['sla_compliance']['rate'] = round(
                ($analytics['sla_compliance']['within_sla'] / $analytics['sla_compliance']['total_completed']) * 100,
                2
            );
        } else {
            $analytics['sla_compliance']['rate'] = 0;
        }

        return response()->json([
            'analytics' => $analytics,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }
}
