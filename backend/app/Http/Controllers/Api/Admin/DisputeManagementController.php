<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OverrideDefensibilityRequest;
use App\Http\Requests\Admin\ResolveDisputeRequest;
use App\Http\Requests\Admin\TransitionDisputeRequest;
use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\User;
use App\Services\DisputeService;
use App\Services\DisputeStateMachine;
use App\Services\SnapshotIntegrityService;
use App\Services\DisputeSettlementOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DisputeManagementController - Admin-first dispute management
 *
 * Provides full dispute lifecycle management:
 * - State machine transitions with validation
 * - Settlement orchestration
 * - Snapshot integrity verification
 * - Permission flag computation
 * - Defensibility override
 */
class DisputeManagementController extends Controller
{
    public function __construct(
        private DisputeService $disputeService,
        private DisputeStateMachine $stateMachine,
        private SnapshotIntegrityService $integrityService,
        private DisputeSettlementOrchestrator $settlementOrchestrator,
    ) {}

    /**
     * List disputes with filtering and permission flags.
     *
     * GET /api/v1/admin/dispute-management
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'type',
            'assigned_to',
            'unassigned',
            'severity',
            'user_id',
            'company_id',
            'sla_breached',
            'active_only',
            'integrity_status',
        ]);

        $disputes = $this->disputeService->getAdminDashboard(
            $filters,
            $request->get('per_page', 15)
        );

        // Add permission flags to each dispute
        $admin = $request->user();
        $disputesWithPermissions = collect($disputes->items())->map(function ($dispute) use ($admin) {
            return [
                'dispute' => $dispute,
                'permissions' => $this->computePermissions($dispute, $admin),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $disputesWithPermissions,
            'meta' => [
                'current_page' => $disputes->currentPage(),
                'last_page' => $disputes->lastPage(),
                'per_page' => $disputes->perPage(),
                'total' => $disputes->total(),
            ],
        ]);
    }

    /**
     * Get dispute detail with full context.
     *
     * GET /api/v1/admin/dispute-management/{dispute}
     */
    public function show(Dispute $dispute): JsonResponse
    {
        $dispute->load([
            'user',
            'company',
            'assignedAdmin',
            'disputable',
            'snapshot',
            'timeline',
        ]);

        $admin = request()->user();
        $integrityResult = $this->integrityService->verifyForDispute($dispute);

        return response()->json([
            'success' => true,
            'data' => [
                'dispute' => $dispute,
                'permissions' => $this->computePermissions($dispute, $admin),
                'integrity' => $integrityResult,
                'recommended_settlement' => $this->getRecommendedSettlement($dispute),
                'available_transitions' => $this->stateMachine->getAvailableTransitions($dispute, $admin),
            ],
        ]);
    }

    /**
     * Transition dispute status.
     *
     * POST /api/v1/admin/dispute-management/{dispute}/transition
     */
    public function transition(TransitionDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        $admin = $request->user();
        $targetStatus = $request->getTargetStatus();

        // Validate transition is allowed
        if (!$this->stateMachine->canActorTransition($dispute, $targetStatus, $admin)) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You do not have permission to perform this transition.',
            ], 403);
        }

        try {
            $dispute = $this->stateMachine->transition(
                $dispute,
                $targetStatus,
                $admin,
                $request->comment
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute status updated.',
                'data' => [
                    'dispute' => $dispute->fresh(['timeline']),
                    'permissions' => $this->computePermissions($dispute, $admin),
                ],
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
     * Resolve dispute with settlement.
     *
     * POST /api/v1/admin/dispute-management/{dispute}/resolve
     */
    public function resolve(ResolveDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        $admin = $request->user();

        // Check permission
        $permissions = $this->computePermissions($dispute, $admin);
        if (!$permissions['can_resolve']) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Cannot resolve dispute in current state.',
            ], 403);
        }

        try {
            if ($request->outcome === 'approved') {
                $dispute = $this->disputeService->resolveApproved(
                    $dispute,
                    $admin,
                    $request->resolution,
                    $request->settlement_action,
                    $request->getSettlementAmountPaise(),
                    $request->settlement_details ?? []
                );
            } else {
                $dispute = $this->disputeService->resolveRejected(
                    $dispute,
                    $admin,
                    $request->resolution
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Dispute resolved.',
                'data' => [
                    'dispute' => $dispute->fresh(['timeline']),
                    'permissions' => $this->computePermissions($dispute, $admin),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Resolution failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Override defensibility status.
     *
     * POST /api/v1/admin/dispute-management/{dispute}/override-defensibility
     */
    public function overrideDefensibility(OverrideDefensibilityRequest $request, Dispute $dispute): JsonResponse
    {
        $admin = $request->user();

        // Check permission
        $permissions = $this->computePermissions($dispute, $admin);
        if (!$permissions['can_override_defensibility']) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Cannot override defensibility in current state.',
            ], 403);
        }

        // Record the override in timeline
        $this->disputeService->addComment(
            $dispute,
            $admin,
            "Defensibility override: {$request->reason}",
            true, // Internal note
            ['override_type' => $request->override_type]
        );

        // Update integrity status
        $dispute->update([
            'admin_notes' => ($dispute->admin_notes ?? '') . "\n[OVERRIDE] {$request->reason}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Defensibility override recorded.',
            'data' => [
                'dispute' => $dispute->fresh(['timeline']),
            ],
        ]);
    }

    /**
     * Execute settlement for approved dispute.
     *
     * POST /api/v1/admin/dispute-management/{dispute}/settle
     */
    public function settle(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:refund,credit,allocation_correction,none',
            'amount' => 'nullable|numeric|min:0',
            'details' => 'nullable|array',
        ]);

        $admin = $request->user();
        $permissions = $this->computePermissions($dispute, $admin);

        if (!$permissions['can_refund']) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Cannot execute settlement in current state.',
            ], 403);
        }

        try {
            $result = $this->settlementOrchestrator->executeSettlement(
                $dispute,
                $request->action,
                $request->amount ? (int)($request->amount * 100) : null,
                $request->details ?? [],
                $admin
            );

            return response()->json([
                'success' => true,
                'message' => 'Settlement executed.',
                'data' => [
                    'settlement_result' => $result,
                    'dispute' => $dispute->fresh(['timeline']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Settlement failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close a resolved dispute.
     *
     * POST /api/v1/admin/dispute-management/{dispute}/close
     */
    public function close(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $admin = $request->user();
        $permissions = $this->computePermissions($dispute, $admin);

        if (!$permissions['can_close']) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Cannot close dispute in current state.',
            ], 403);
        }

        try {
            $dispute = $this->disputeService->close($dispute, $admin, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Dispute closed.',
                'data' => [
                    'dispute' => $dispute,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Close failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Escalate dispute.
     *
     * POST /api/v1/admin/dispute-management/{dispute}/escalate
     */
    public function escalate(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $admin = $request->user();
        $permissions = $this->computePermissions($dispute, $admin);

        if (!$permissions['can_escalate']) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Cannot escalate dispute in current state.',
            ], 403);
        }

        try {
            $dispute = $this->disputeService->escalate($dispute, $admin, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Dispute escalated.',
                'data' => [
                    'dispute' => $dispute->fresh(['timeline']),
                    'permissions' => $this->computePermissions($dispute, $admin),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Escalation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Compute permission flags for a dispute.
     */
    private function computePermissions(Dispute $dispute, User $admin): array
    {
        $status = DisputeStatus::tryFrom($dispute->status);
        $isTerminal = $status?->isTerminal() ?? false;
        $isResolved = $status?->isResolved() ?? false;

        $canEscalate = $status && $this->stateMachine->canActorTransition(
            $dispute,
            DisputeStatus::ESCALATED,
            $admin
        );

        return [
            'can_transition' => !$isTerminal,
            'can_escalate' => $canEscalate,
            'can_resolve' => in_array($dispute->status, [
                DisputeStatus::UNDER_REVIEW->value,
                DisputeStatus::ESCALATED->value,
            ]),
            'can_override_defensibility' => !$isTerminal && !$isResolved,
            'can_refund' => $dispute->status === DisputeStatus::RESOLVED_APPROVED->value
                && empty($dispute->settlement_action),
            'can_close' => $isResolved,
            'available_transitions' => array_map(
                fn($s) => $s->value,
                $this->stateMachine->getAvailableTransitions($dispute, $admin)
            ),
        ];
    }

    /**
     * Get recommended settlement based on dispute type.
     */
    private function getRecommendedSettlement(Dispute $dispute): ?array
    {
        if (!in_array($dispute->status, [
            DisputeStatus::UNDER_REVIEW->value,
            DisputeStatus::ESCALATED->value,
        ])) {
            return null;
        }

        return $this->settlementOrchestrator->getRecommendedAction($dispute);
    }
}
