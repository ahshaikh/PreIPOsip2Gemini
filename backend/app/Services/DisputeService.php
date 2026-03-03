<?php

namespace App\Services;

use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * DisputeService - Main orchestration service for the Dispute Management System
 *
 * This service is the primary entry point for all dispute operations:
 * - Filing disputes (investor-initiated)
 * - Managing disputes (admin operations)
 * - State transitions (via DisputeStateMachine)
 * - Settlements (via DisputeSettlementOrchestrator)
 * - Snapshots (via DisputeSnapshotService)
 *
 * All operations create appropriate timeline entries and follow the state machine rules.
 */
class DisputeService
{
    public function __construct(
        private DisputeStateMachine $stateMachine,
        private DisputeSnapshotService $snapshotService,
        private DisputeSettlementOrchestrator $settlementOrchestrator,
        private SnapshotIntegrityService $integrityService,
    ) {}

    /**
     * File a new dispute (investor-initiated).
     *
     * @param User $user The investor filing the dispute
     * @param string $type DisputeType value (confusion, payment, allocation, fraud)
     * @param Model|null $disputable The entity being disputed (Payment, Investment, etc.)
     * @param string $title Dispute title
     * @param string $description Detailed description
     * @param array $evidence Evidence files/data
     * @throws \InvalidArgumentException If type/disputable combination is invalid
     */
    public function fileDispute(
        User $user,
        string $type,
        ?Model $disputable,
        string $title,
        string $description,
        array $evidence = []
    ): Dispute {
        // Validate type
        $disputeType = DisputeType::fromString($type);

        // Validate type/disputable combination
        if ($disputable) {
            DisputeType::validateCombination($type, get_class($disputable));

            // Check for existing active dispute for the same disputable by this user
            $exists = Dispute::where('user_id', $user->id)
                ->where('disputable_type', get_class($disputable))
                ->where('disputable_id', $disputable->getKey())
                ->active()
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException('You already have an active dispute for this item.');
            }
        }

        return DB::transaction(function () use ($user, $disputeType, $disputable, $title, $description, $evidence) {
            // Calculate SLA and escalation deadlines
            $now = now();
            $slaDeadline = $now->copy()->addHours($disputeType->slaHours());
            $escalationDeadline = $now->copy()->addHours($disputeType->autoEscalationHours());

            // Create the dispute
            $dispute = Dispute::create([
                'disputable_type' => $disputable ? get_class($disputable) : null,
                'disputable_id' => $disputable?->getKey(),
                'type' => $disputeType->value,
                'user_id' => $user->id,
                'raised_by_user_id' => $user->id,
                'company_id' => $this->resolveCompanyId($disputable),
                'status' => DisputeStatus::OPEN->value,
                'severity' => $this->mapRiskToSeverity($disputeType->riskLevel()),
                'category' => $this->mapTypeToCategory($disputeType),
                'title' => $title,
                'description' => $description,
                'evidence' => $evidence ?: null,
                'opened_at' => $now,
                'sla_deadline_at' => $slaDeadline,
                'escalation_deadline_at' => $escalationDeadline,
                'risk_score' => $disputeType->riskScore(),
                'blocks_investment' => $disputeType->riskScore() >= 3,
            ]);

            // Capture snapshot
            $this->snapshotService->captureAtFiling($dispute);

            // Create initial timeline entry
            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event_type' => DisputeTimeline::EVENT_CREATED,
                'actor_user_id' => $user->id,
                'actor_role' => DisputeTimeline::ROLE_INVESTOR,
                'title' => 'Dispute filed',
                'description' => $description,
                'visible_to_investor' => true,
                'is_internal_note' => false,
            ]);

            // Auto-escalate fraud disputes
            if ($disputeType->requiresImmediateEscalation()) {
                $this->autoEscalate($dispute, 'High-risk dispute type requires immediate attention');
            }

            Log::channel('financial_contract')->info('Dispute filed', [
                'dispute_id' => $dispute->id,
                'user_id' => $user->id,
                'type' => $disputeType->value,
                'disputable_type' => $dispute->disputable_type,
                'disputable_id' => $dispute->disputable_id,
            ]);

            return $dispute->fresh(['snapshot', 'timeline']);
        });
    }

    /**
     * Add a comment to a dispute.
     */
    public function addComment(
        Dispute $dispute,
        User $actor,
        string $comment,
        bool $isInternalNote = false,
        array $attachments = []
    ): DisputeTimeline {
        $actorRole = $this->getActorRole($actor);

        // Investors cannot add internal notes
        if ($isInternalNote && $actorRole === DisputeTimeline::ROLE_INVESTOR) {
            throw new \InvalidArgumentException('Investors cannot add internal notes');
        }

        return DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_user_id' => $actor->id,
            'actor_role' => $actorRole,
            'title' => $isInternalNote ? 'Internal note added' : 'Comment added',
            'description' => $comment,
            'attachments' => $attachments ?: null,
            'visible_to_investor' => !$isInternalNote,
            'is_internal_note' => $isInternalNote,
        ]);
    }

    /**
     * Add evidence to a dispute.
     */
    public function addEvidence(
        Dispute $dispute,
        User $actor,
        array $evidenceFiles,
        ?string $description = null
    ): DisputeTimeline {
        // Append to existing evidence
        $currentEvidence = $dispute->evidence ?? [];
        $newEvidence = array_merge($currentEvidence, $evidenceFiles);
        $dispute->update(['evidence' => $newEvidence]);

        return DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_EVIDENCE_ADDED,
            'actor_user_id' => $actor->id,
            'actor_role' => $this->getActorRole($actor),
            'title' => 'Evidence added',
            'description' => $description ?? 'New evidence files uploaded',
            'attachments' => $evidenceFiles,
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);
    }

    /**
     * Assign dispute to an admin.
     */
    public function assignToAdmin(
        Dispute $dispute,
        User $admin,
        User $assignedBy
    ): Dispute {
        $previousAdmin = $dispute->assigned_to_admin_id;
        $dispute->update(['assigned_to_admin_id' => $admin->id]);

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_ASSIGNED,
            'actor_user_id' => $assignedBy->id,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Dispute assigned',
            'description' => "Assigned to {$admin->name}",
            'metadata' => [
                'previous_admin_id' => $previousAdmin,
                'new_admin_id' => $admin->id,
            ],
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        return $dispute->fresh();
    }

    /**
     * Start review of a dispute.
     */
    public function startReview(Dispute $dispute, User $admin): Dispute
    {
        return $this->stateMachine->startReview($dispute, $admin);
    }

    /**
     * Request investor response.
     */
    public function requestInvestorResponse(
        Dispute $dispute,
        User $admin,
        string $question
    ): Dispute {
        return $this->stateMachine->requestInvestorResponse($dispute, $admin, $question);
    }

    /**
     * Investor responds to request.
     */
    public function investorRespond(
        Dispute $dispute,
        User $investor,
        string $response,
        array $attachments = []
    ): Dispute {
        // Add the response as a comment
        $this->addComment($dispute, $investor, $response, false, $attachments);

        // Transition back to under review
        return $this->stateMachine->transition(
            $dispute,
            DisputeStatus::UNDER_REVIEW,
            $investor,
            'Investor response provided'
        );
    }

    /**
     * Escalate a dispute.
     */
    public function escalate(
        Dispute $dispute,
        User $actor,
        string $reason
    ): Dispute {
        return $this->stateMachine->escalate($dispute, $actor, $reason);
    }

    /**
     * Resolve dispute as approved (in favor of investor).
     */
    public function resolveApproved(
        Dispute $dispute,
        User $admin,
        string $resolution,
        string $settlementAction,
        ?int $settlementAmountPaise = null,
        array $settlementDetails = []
    ): Dispute {
        return DB::transaction(function () use (
            $dispute, $admin, $resolution, $settlementAction, $settlementAmountPaise, $settlementDetails
        ) {
            // Transition to resolved_approved
            $dispute = $this->stateMachine->resolveApproved(
                $dispute,
                $admin,
                $resolution,
                ['intended_settlement' => $settlementAction]
            );

            // Execute settlement
            $this->settlementOrchestrator->executeSettlement(
                $dispute,
                $settlementAction,
                $settlementAmountPaise,
                $settlementDetails,
                $admin
            );

            return $dispute->fresh();
        });
    }

    /**
     * Resolve dispute as rejected (against investor claim).
     */
    public function resolveRejected(
        Dispute $dispute,
        User $admin,
        string $reason
    ): Dispute {
        return $this->stateMachine->resolveRejected($dispute, $admin, $reason);
    }

    /**
     * Close a dispute (final state).
     */
    public function close(
        Dispute $dispute,
        User $actor,
        ?string $finalNotes = null
    ): Dispute {
        return $this->stateMachine->close($dispute, $actor, $finalNotes);
    }

    /**
     * Investor withdraws their dispute.
     */
    public function investorWithdraw(
        Dispute $dispute,
        User $investor,
        string $reason
    ): Dispute {
        if ($dispute->user_id !== $investor->id) {
            throw new \InvalidArgumentException('Only the dispute owner can withdraw');
        }

        return $this->stateMachine->close($dispute, $investor, "Withdrawn by investor: {$reason}");
    }

    /**
     * Investor appeals a rejected resolution.
     */
    public function investorAppeal(
        Dispute $dispute,
        User $investor,
        string $appealReason
    ): Dispute {
        if ($dispute->user_id !== $investor->id) {
            throw new \InvalidArgumentException('Only the dispute owner can appeal');
        }

        return $this->stateMachine->escalate($dispute, $investor, "Appeal: {$appealReason}");
    }

    /**
     * Get available actions for a user on a dispute.
     */
    public function getAvailableActions(Dispute $dispute, User $actor): array
    {
        $availableTransitions = $this->stateMachine->getAvailableTransitions($dispute, $actor);
        $actorRole = $this->getActorRole($actor);

        $actions = [];

        // Add comment action (always available if not closed)
        if ($dispute->status !== Dispute::STATUS_CLOSED) {
            $actions[] = [
                'action' => 'add_comment',
                'label' => 'Add Comment',
                'available' => true,
            ];
        }

        // Add evidence action
        if ($dispute->status !== Dispute::STATUS_CLOSED) {
            $actions[] = [
                'action' => 'add_evidence',
                'label' => 'Add Evidence',
                'available' => true,
            ];
        }

        // Status transitions
        foreach ($availableTransitions as $status) {
            $actions[] = [
                'action' => 'transition_' . $status->value,
                'label' => $this->getTransitionLabel($status),
                'target_status' => $status->value,
                'available' => true,
            ];
        }

        // Admin-specific actions
        if ($actorRole === DisputeTimeline::ROLE_ADMIN) {
            if ($dispute->status !== Dispute::STATUS_CLOSED) {
                $actions[] = [
                    'action' => 'add_internal_note',
                    'label' => 'Add Internal Note',
                    'available' => true,
                ];

                $actions[] = [
                    'action' => 'assign',
                    'label' => 'Assign to Admin',
                    'available' => true,
                ];
            }
        }

        return $actions;
    }

    /**
     * Get disputes for admin dashboard.
     */
    public function getAdminDashboard(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Dispute::with(['user', 'company', 'assignedAdmin', 'disputable'])
            ->orderBy('risk_score', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to_admin_id', $filters['assigned_to']);
        }

        if (isset($filters['unassigned']) && $filters['unassigned']) {
            $query->whereNull('assigned_to_admin_id');
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['sla_breached'])) {
            $query->slaBreach();
        }

        if (!empty($filters['active_only'])) {
            $query->active();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get disputes for investor view.
     */
    public function getInvestorDisputes(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Dispute::with(['company', 'disputable'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get dispute detail for admin.
     */
    public function getDisputeForAdmin(Dispute $dispute): array
    {
        $dispute->load([
            'user',
            'company',
            'assignedAdmin',
            'disputable',
            'snapshot',
            'timeline',
        ]);

        return [
            'dispute' => $dispute,
            'snapshot_valid' => $dispute->snapshot ? $dispute->snapshot->verifyIntegrity() : null,
            'available_actions' => $this->getAvailableActions($dispute, auth()->user()),
            'recommended_settlement' => $dispute->status === Dispute::STATUS_UNDER_REVIEW
                ? $this->settlementOrchestrator->getRecommendedAction($dispute)
                : null,
        ];
    }

    /**
     * Get dispute detail for investor.
     */
    public function getDisputeForInvestor(Dispute $dispute, User $investor): array
    {
        if ($dispute->user_id !== $investor->id) {
            throw new \InvalidArgumentException('Access denied');
        }

        $dispute->load([
            'company',
            'disputable',
            'investorTimeline', // Only visible timeline entries
        ]);

        return [
            'dispute' => $dispute,
            'timeline' => $dispute->investorTimeline,
            'available_actions' => $this->getAvailableActions($dispute, $investor),
            'permissions' => $this->computeInvestorPermissions($dispute),
        ];
    }

    /**
     * Compute permission flags for investor dispute view.
     *
     * Backend is authoritative - frontend must use these flags
     * and MUST NOT derive permissions from status.
     */
    private function computeInvestorPermissions(Dispute $dispute): array
    {
        $status = DisputeStatus::tryFrom($dispute->status);
        $isTerminal = $status?->isTerminal() ?? false;
        $isResolved = $status?->isResolved() ?? false;

        // can_add_evidence: Only when dispute is awaiting investor response
        $canAddEvidence = $status === DisputeStatus::AWAITING_INVESTOR;

        // can_add_comment: Allowed on any active (non-terminal) dispute
        $canAddComment = !$isTerminal;

        return [
            'can_add_evidence' => $canAddEvidence,
            'can_add_comment' => $canAddComment,
        ];
    }

    /**
     * Auto-escalate a dispute.
     */
    private function autoEscalate(Dispute $dispute, string $reason): void
    {
        $systemUser = User::where('email', 'system@preipo.com')->first();

        if ($systemUser) {
            $this->stateMachine->escalate($dispute, $systemUser, $reason);
        } else {
            // Fallback: update directly
            $dispute->update([
                'status' => DisputeStatus::ESCALATED->value,
                'escalated_at' => now(),
                'risk_score' => min(4, $dispute->risk_score + 1),
            ]);

            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event_type' => DisputeTimeline::EVENT_ESCALATED,
                'actor_user_id' => null,
                'actor_role' => DisputeTimeline::ROLE_SYSTEM,
                'title' => 'Dispute auto-escalated',
                'description' => $reason,
                'visible_to_investor' => true,
                'is_internal_note' => false,
            ]);
        }
    }

    /**
     * Resolve company ID from disputable entity.
     */
    private function resolveCompanyId(?Model $disputable): ?int
    {
        if (!$disputable) {
            return null;
        }

        // Try direct company_id
        if (isset($disputable->company_id)) {
            return $disputable->company_id;
        }

        // Try via product
        if (method_exists($disputable, 'product') && $disputable->product?->company_id) {
            return $disputable->product->company_id;
        }

        return null;
    }

    /**
     * Map risk level to severity.
     */
    private function mapRiskToSeverity(string $riskLevel): string
    {
        return match ($riskLevel) {
            'low' => Dispute::SEVERITY_LOW,
            'medium' => Dispute::SEVERITY_MEDIUM,
            'high' => Dispute::SEVERITY_HIGH,
            'critical' => Dispute::SEVERITY_CRITICAL,
            default => Dispute::SEVERITY_MEDIUM,
        };
    }

    /**
     * Map dispute type to category.
     */
    private function mapTypeToCategory(DisputeType $type): string
    {
        return match ($type) {
            DisputeType::A_CONFUSION => Dispute::CATEGORY_PLATFORM_SERVICE,
            DisputeType::B_PAYMENT => Dispute::CATEGORY_FUND_TRANSFER,
            DisputeType::C_ALLOCATION => Dispute::CATEGORY_INVESTMENT_PROCESSING,
            DisputeType::D_FRAUD => Dispute::CATEGORY_INVESTOR_CONDUCT,
        };
    }

    /**
     * Get actor role.
     */
    private function getActorRole(User $actor): string
    {
        if ($actor->hasRole('admin') || $actor->hasRole('super-admin')) {
            return DisputeTimeline::ROLE_ADMIN;
        }

        if ($actor->email === 'system@preipo.com') {
            return DisputeTimeline::ROLE_SYSTEM;
        }

        return DisputeTimeline::ROLE_INVESTOR;
    }

    /**
     * Get human-readable label for transition.
     */
    private function getTransitionLabel(DisputeStatus $status): string
    {
        return match ($status) {
            DisputeStatus::OPEN => 'Reopen',
            DisputeStatus::UNDER_REVIEW => 'Start Review',
            DisputeStatus::AWAITING_INVESTOR => 'Request Info',
            DisputeStatus::ESCALATED => 'Escalate',
            DisputeStatus::RESOLVED_APPROVED => 'Approve',
            DisputeStatus::RESOLVED_REJECTED => 'Reject',
            DisputeStatus::CLOSED => 'Close',
        };
    }
}
