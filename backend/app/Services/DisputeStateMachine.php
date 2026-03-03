<?php

namespace App\Services;

use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DisputeStateMachine - Enforces valid state transitions for disputes
 *
 * This service is the ONLY authorized way to change dispute status.
 * It validates transitions against the DisputeStatus enum rules and
 * creates timeline entries for every state change.
 *
 * State Machine:
 * - OPEN → UNDER_REVIEW, CLOSED
 * - UNDER_REVIEW → AWAITING_INVESTOR, ESCALATED, RESOLVED_APPROVED, RESOLVED_REJECTED
 * - AWAITING_INVESTOR → UNDER_REVIEW, ESCALATED, CLOSED
 * - ESCALATED → RESOLVED_APPROVED, RESOLVED_REJECTED
 * - RESOLVED_APPROVED → CLOSED
 * - RESOLVED_REJECTED → CLOSED, ESCALATED (appeal)
 * - CLOSED → (terminal)
 */
class DisputeStateMachine
{
    /**
     * Transition dispute to a new status.
     *
     * @throws \InvalidArgumentException If transition is not allowed
     * @throws \RuntimeException If transition fails
     */
    public function transition(
        Dispute $dispute,
        DisputeStatus $targetStatus,
        User $actor,
        ?string $comment = null,
        array $metadata = []
    ): Dispute {
        $currentStatus = DisputeStatus::tryFrom($dispute->status);

        // Validate current status is recognized
        if (!$currentStatus) {
            throw new \InvalidArgumentException(
                "Dispute #{$dispute->id} has unrecognized status: {$dispute->status}"
            );
        }

        // Validate transition is allowed
        if (!$currentStatus->canTransitionTo($targetStatus)) {
            throw new \InvalidArgumentException(
                "Transition from '{$currentStatus->value}' to '{$targetStatus->value}' is not allowed. " .
                "Allowed transitions: " . implode(', ', array_map(
                    fn($s) => $s->value,
                    $currentStatus->getAllowedTransitions()
                ))
            );
        }

        return DB::transaction(function () use ($dispute, $currentStatus, $targetStatus, $actor, $comment, $metadata) {
            $oldStatus = $dispute->status;

            // Update status
            $dispute->status = $targetStatus->value;

            // Set timestamps based on target status
            $this->updateTimestamps($dispute, $targetStatus);

            // Update risk score if status indicates escalation
            if ($targetStatus === DisputeStatus::ESCALATED) {
                $dispute->escalated_at = now();
                $dispute->risk_score = min(4, $dispute->risk_score + 1);
            }

            $dispute->save();

            // Create timeline entry
            $this->createTimelineEntry(
                $dispute,
                $oldStatus,
                $targetStatus->value,
                $actor,
                $comment,
                $metadata
            );

            Log::channel('financial_contract')->info('Dispute status transitioned', [
                'dispute_id' => $dispute->id,
                'from_status' => $oldStatus,
                'to_status' => $targetStatus->value,
                'actor_id' => $actor->id,
                'actor_role' => $this->getActorRole($actor),
            ]);

            return $dispute->fresh();
        });
    }

    /**
     * Escalate a dispute (convenience method).
     */
    public function escalate(
        Dispute $dispute,
        User $actor,
        string $reason,
        array $metadata = []
    ): Dispute {
        return $this->transition(
            $dispute,
            DisputeStatus::ESCALATED,
            $actor,
            $reason,
            array_merge($metadata, ['escalation_reason' => $reason])
        );
    }

    /**
     * Resolve dispute as approved (in favor of investor).
     */
    public function resolveApproved(
        Dispute $dispute,
        User $actor,
        string $resolution,
        array $settlementDetails = []
    ): Dispute {
        return $this->transition(
            $dispute,
            DisputeStatus::RESOLVED_APPROVED,
            $actor,
            $resolution,
            ['settlement_details' => $settlementDetails]
        );
    }

    /**
     * Resolve dispute as rejected (against investor claim).
     */
    public function resolveRejected(
        Dispute $dispute,
        User $actor,
        string $reason
    ): Dispute {
        return $this->transition(
            $dispute,
            DisputeStatus::RESOLVED_REJECTED,
            $actor,
            $reason
        );
    }

    /**
     * Close a dispute (final state).
     */
    public function close(
        Dispute $dispute,
        User $actor,
        ?string $finalNotes = null
    ): Dispute {
        return $this->transition(
            $dispute,
            DisputeStatus::CLOSED,
            $actor,
            $finalNotes
        );
    }

    /**
     * Move dispute to under review.
     */
    public function startReview(
        Dispute $dispute,
        User $admin,
        ?string $comment = null
    ): Dispute {
        return $this->transition(
            $dispute,
            DisputeStatus::UNDER_REVIEW,
            $admin,
            $comment ?? 'Dispute is now under review'
        );
    }

    /**
     * Request additional information from investor.
     */
    public function requestInvestorResponse(
        Dispute $dispute,
        User $admin,
        string $question
    ): Dispute {
        return $this->transition(
            $dispute,
            DisputeStatus::AWAITING_INVESTOR,
            $admin,
            $question,
            ['requested_information' => $question]
        );
    }

    /**
     * Process auto-escalation for disputes past deadline.
     */
    public function processAutoEscalation(Dispute $dispute): Dispute
    {
        // Create system user for auto-escalation
        $systemUser = User::where('email', 'system@preipo.com')->first();

        if (!$systemUser) {
            Log::warning('System user not found for auto-escalation', [
                'dispute_id' => $dispute->id,
            ]);
            throw new \RuntimeException('System user not found');
        }

        return DB::transaction(function () use ($dispute, $systemUser) {
            // Transition to escalated
            $oldStatus = $dispute->status;
            $dispute->status = DisputeStatus::ESCALATED->value;
            $dispute->escalated_at = now();
            $dispute->risk_score = min(4, $dispute->risk_score + 1);
            $dispute->save();

            // Create timeline entry for auto-escalation
            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event_type' => DisputeTimeline::EVENT_AUTO_ESCALATION,
                'actor_user_id' => $systemUser->id,
                'actor_role' => DisputeTimeline::ROLE_SYSTEM,
                'title' => 'Dispute auto-escalated',
                'description' => 'Dispute was automatically escalated due to escalation deadline breach.',
                'old_status' => $oldStatus,
                'new_status' => DisputeStatus::ESCALATED->value,
                'visible_to_investor' => true,
                'is_internal_note' => false,
            ]);

            Log::channel('financial_contract')->warning('Dispute auto-escalated', [
                'dispute_id' => $dispute->id,
                'old_status' => $oldStatus,
                'escalation_deadline' => $dispute->escalation_deadline_at,
            ]);

            return $dispute->fresh();
        });
    }

    /**
     * Check if actor can perform transition.
     */
    public function canActorTransition(
        Dispute $dispute,
        DisputeStatus $targetStatus,
        User $actor
    ): bool {
        $actorRole = $this->getActorRole($actor);

        // System can do anything
        if ($actorRole === DisputeTimeline::ROLE_SYSTEM) {
            return true;
        }

        // Investors can only:
        // - Withdraw (close) their own disputes from AWAITING_INVESTOR
        // - Respond (back to under_review) from AWAITING_INVESTOR
        // - Appeal (escalate) from RESOLVED_REJECTED
        if ($actorRole === DisputeTimeline::ROLE_INVESTOR) {
            $currentStatus = DisputeStatus::tryFrom($dispute->status);

            if ($currentStatus === DisputeStatus::AWAITING_INVESTOR) {
                return in_array($targetStatus, [
                    DisputeStatus::UNDER_REVIEW,
                    DisputeStatus::CLOSED,
                ], true);
            }

            if ($currentStatus === DisputeStatus::RESOLVED_REJECTED) {
                return $targetStatus === DisputeStatus::ESCALATED;
            }

            return false;
        }

        // Admins can perform transitions that are valid per the state graph
        $currentStatus = DisputeStatus::tryFrom($dispute->status);
        if (!$currentStatus) {
            return false;
        }

        return $currentStatus->canTransitionTo($targetStatus);
    }

    /**
     * Get available transitions for a dispute.
     */
    public function getAvailableTransitions(Dispute $dispute, User $actor): array
    {
        $currentStatus = DisputeStatus::tryFrom($dispute->status);

        if (!$currentStatus) {
            return [];
        }

        $allowed = $currentStatus->getAllowedTransitions();

        // Filter by actor permissions
        return array_filter($allowed, function ($targetStatus) use ($dispute, $actor) {
            return $this->canActorTransition($dispute, $targetStatus, $actor);
        });
    }

    /**
     * Update timestamps based on target status.
     */
    private function updateTimestamps(Dispute $dispute, DisputeStatus $targetStatus): void
    {
        switch ($targetStatus) {
            case DisputeStatus::UNDER_REVIEW:
                $dispute->investigation_started_at = $dispute->investigation_started_at ?? now();
                break;

            case DisputeStatus::RESOLVED_APPROVED:
            case DisputeStatus::RESOLVED_REJECTED:
                $dispute->resolved_at = now();
                break;

            case DisputeStatus::CLOSED:
                $dispute->closed_at = now();
                break;
        }
    }

    /**
     * Create timeline entry for state transition.
     */
    private function createTimelineEntry(
        Dispute $dispute,
        string $oldStatus,
        string $newStatus,
        User $actor,
        ?string $comment,
        array $metadata
    ): DisputeTimeline {
        $actorRole = $this->getActorRole($actor);

        return DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'actor_user_id' => $actor->id,
            'actor_role' => $actorRole,
            'title' => $this->getTransitionTitle($oldStatus, $newStatus),
            'description' => $comment,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'metadata' => $metadata ?: null,
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);
    }

    /**
     * Get actor role for timeline.
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
     * Generate human-readable transition title.
     */
    private function getTransitionTitle(string $from, string $to): string
    {
        $toStatus = DisputeStatus::tryFrom($to);
        $toLabel = $toStatus ? $toStatus->label() : ucfirst(str_replace('_', ' ', $to));

        return "Status changed to {$toLabel}";
    }
}
