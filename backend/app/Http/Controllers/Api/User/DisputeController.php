<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\FileDisputeRequest;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * User Dispute Controller
 *
 * Provides investor endpoints for managing their disputes:
 * - File a new dispute
 * - View their disputes
 * - Respond to information requests
 * - Add comments/evidence
 * - Withdraw disputes
 * - Appeal rejected resolutions
 */
class DisputeController extends Controller
{
    public function __construct(
        private DisputeService $disputeService,
    ) {}

    /**
     * List user's disputes.
     *
     * GET /api/v1/user/disputes
     */
    public function index(Request $request): JsonResponse
    {
        $disputes = $this->disputeService->getInvestorDisputes(
            $request->user(),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => $disputes->items(),
            'meta' => [
                'current_page' => $disputes->currentPage(),
                'last_page' => $disputes->lastPage(),
                'per_page' => $disputes->perPage(),
                'total' => $disputes->total(),
            ],
        ]);
    }

    /**
     * File a new dispute.
     *
     * POST /api/v1/user/disputes
     */
    public function store(FileDisputeRequest $request): JsonResponse
    {
        try {
            $disputable = $request->getDisputable();

            // Validate disputable belongs to user if specified
            if ($request->disputable_type && !$disputable) {
                return response()->json([
                    'error' => 'Invalid entity',
                    'message' => 'The specified entity was not found or does not belong to you.',
                ], 422);
            }

            $dispute = $this->disputeService->fileDispute(
                $request->user(),
                $request->type,
                $disputable,
                $request->title,
                $request->description,
                $request->evidence ?? []
            );

            return response()->json([
                'message' => 'Dispute filed successfully.',
                'data' => $dispute,
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Filing failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * View a specific dispute.
     *
     * GET /api/v1/user/disputes/{dispute}
     */
    public function show(Request $request, Dispute $dispute): JsonResponse
    {
        try {
            $result = $this->disputeService->getDisputeForInvestor($dispute, $request->user());

            return response()->json([
                'data' => array_merge(
                    $result['dispute']->toArray(),
                    [
                        'timeline' => $result['timeline'],
                        'available_actions' => $result['available_actions'],
                        'permissions' => $result['permissions'],
                    ]
                )
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Access denied',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Add a comment to a dispute.
     *
     * POST /api/v1/user/disputes/{dispute}/comment
     */
    public function addComment(Request $request, Dispute $dispute): JsonResponse
    {
        // Verify ownership
        if ($dispute->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'This dispute does not belong to you.',
            ], 403);
        }

        $request->validate([
            'comment' => 'required|string|max:5000',
            'attachments' => 'nullable|array',
        ]);

        $timeline = $this->disputeService->addComment(
            $dispute,
            $request->user(),
            $request->comment,
            false, // Investors cannot add internal notes
            $request->attachments ?? []
        );

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => $timeline,
        ], 201);
    }

    /**
     * Add evidence to a dispute.
     *
     * POST /api/v1/user/disputes/{dispute}/evidence
     */
    public function addEvidence(Request $request, Dispute $dispute): JsonResponse
    {
        // Verify ownership
        if ($dispute->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'This dispute does not belong to you.',
            ], 403);
        }

        $request->validate([
            'evidence' => 'required|array|min:1',
            'evidence.*' => 'required|array',
            'evidence.*.type' => 'required|string|in:text,screenshot,document,link',
            'evidence.*.value' => 'required|string|max:500',
            'evidence.*.description' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:1000',
        ]);

        $timeline = $this->disputeService->addEvidence(
            $dispute,
            $request->user(),
            $request->evidence,
            $request->description
        );

        return response()->json([
            'message' => 'Evidence added successfully.',
            'data' => $timeline,
        ], 201);
    }

    /**
     * Respond to an information request.
     *
     * POST /api/v1/user/disputes/{dispute}/respond
     */
    public function respond(Request $request, Dispute $dispute): JsonResponse
    {
        // Verify ownership
        if ($dispute->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'This dispute does not belong to you.',
            ], 403);
        }

        // Verify dispute is awaiting investor response
        if ($dispute->status !== Dispute::STATUS_AWAITING_INVESTOR) {
            return response()->json([
                'error' => 'Invalid action',
                'message' => 'This dispute is not awaiting your response.',
            ], 422);
        }

        $request->validate([
            'response' => 'required|string|max:5000',
            'attachments' => 'nullable|array',
        ]);

        try {
            $dispute = $this->disputeService->investorRespond(
                $dispute,
                $request->user(),
                $request->response,
                $request->attachments ?? []
            );

            return response()->json([
                'message' => 'Response submitted successfully.',
                'data' => $dispute,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Response failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Withdraw a dispute.
     *
     * POST /api/v1/user/disputes/{dispute}/withdraw
     */
    public function withdraw(Request $request, Dispute $dispute): JsonResponse
    {
        // Verify ownership
        if ($dispute->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'This dispute does not belong to you.',
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $dispute = $this->disputeService->investorWithdraw(
                $dispute,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'message' => 'Dispute withdrawn successfully.',
                'data' => $dispute,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Withdrawal failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Appeal a rejected dispute resolution.
     *
     * POST /api/v1/user/disputes/{dispute}/appeal
     */
    public function appeal(Request $request, Dispute $dispute): JsonResponse
    {
        // Verify ownership
        if ($dispute->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'This dispute does not belong to you.',
            ], 403);
        }

        // Verify dispute is rejected
        if ($dispute->status !== Dispute::STATUS_RESOLVED_REJECTED) {
            return response()->json([
                'error' => 'Invalid action',
                'message' => 'Only rejected disputes can be appealed.',
            ], 422);
        }

        $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        try {
            $dispute = $this->disputeService->investorAppeal(
                $dispute,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'message' => 'Appeal submitted successfully. Your dispute has been escalated.',
                'data' => $dispute,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Appeal failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available actions for a dispute.
     *
     * GET /api/v1/user/disputes/{dispute}/actions
     */
    public function actions(Request $request, Dispute $dispute): JsonResponse
    {
        // Verify ownership
        if ($dispute->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'This dispute does not belong to you.',
            ], 403);
        }

        $actions = $this->disputeService->getAvailableActions($dispute, $request->user());

        return response()->json([
            'dispute_id' => $dispute->id,
            'status' => $dispute->status,
            'actions' => $actions,
        ]);
    }
}
