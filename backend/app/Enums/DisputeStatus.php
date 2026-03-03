<?php

namespace App\Enums;

/**
 * DisputeStatus - Strict ENUM-backed state machine for disputes
 *
 * States are authority-controlled: only admin can transition between most states.
 * The state machine enforces valid transitions via getAllowedTransitions().
 *
 * @see \App\Services\DisputeStateMachine for transition enforcement
 */
enum DisputeStatus: string
{
    case OPEN = 'open';
    case UNDER_REVIEW = 'under_review';
    case AWAITING_INVESTOR = 'awaiting_investor';
    case ESCALATED = 'escalated';
    case RESOLVED_APPROVED = 'resolved_approved';
    case RESOLVED_REJECTED = 'resolved_rejected';
    case CLOSED = 'closed';

    /**
     * Get all allowed transitions FROM this status.
     *
     * @return array<DisputeStatus>
     */
    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::OPEN => [
                self::UNDER_REVIEW,
                self::CLOSED, // Admin can close without review (e.g., duplicate)
            ],
            self::UNDER_REVIEW => [
                self::AWAITING_INVESTOR,
                self::ESCALATED,
                self::RESOLVED_APPROVED,
                self::RESOLVED_REJECTED,
            ],
            self::AWAITING_INVESTOR => [
                self::UNDER_REVIEW, // Investor responded
                self::ESCALATED,    // Auto-escalation on timeout
                self::CLOSED,       // Investor withdrew dispute
            ],
            self::ESCALATED => [
                self::RESOLVED_APPROVED,
                self::RESOLVED_REJECTED,
            ],
            self::RESOLVED_APPROVED => [
                self::CLOSED, // Final closure after settlement executed
            ],
            self::RESOLVED_REJECTED => [
                self::CLOSED, // Final closure
                self::ESCALATED, // Investor appeals rejection
            ],
            self::CLOSED => [], // Terminal state - no transitions allowed
        };
    }

    /**
     * Check if a transition to the given status is allowed.
     */
    public function canTransitionTo(DisputeStatus $target): bool
    {
        return in_array($target, $this->getAllowedTransitions(), true);
    }

    /**
     * Check if this is a terminal (final) state.
     */
    public function isTerminal(): bool
    {
        return $this === self::CLOSED;
    }

    /**
     * Check if this is a resolved state (approved or rejected).
     */
    public function isResolved(): bool
    {
        return in_array($this, [self::RESOLVED_APPROVED, self::RESOLVED_REJECTED], true);
    }

    /**
     * Check if this status requires admin attention.
     */
    public function requiresAdminAction(): bool
    {
        return in_array($this, [
            self::OPEN,
            self::UNDER_REVIEW,
            self::ESCALATED,
        ], true);
    }

    /**
     * Check if this status requires investor response.
     */
    public function requiresInvestorAction(): bool
    {
        return $this === self::AWAITING_INVESTOR;
    }

    /**
     * Check if this status allows a refund to be issued.
     */
    public function allowsRefund(): bool
    {
        return $this === self::RESOLVED_APPROVED;
    }

    /**
     * Get human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::UNDER_REVIEW => 'Under Review',
            self::AWAITING_INVESTOR => 'Awaiting Investor Response',
            self::ESCALATED => 'Escalated',
            self::RESOLVED_APPROVED => 'Resolved (Approved)',
            self::RESOLVED_REJECTED => 'Resolved (Rejected)',
            self::CLOSED => 'Closed',
        };
    }

    /**
     * Get CSS color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::OPEN => 'yellow',
            self::UNDER_REVIEW => 'blue',
            self::AWAITING_INVESTOR => 'orange',
            self::ESCALATED => 'red',
            self::RESOLVED_APPROVED => 'green',
            self::RESOLVED_REJECTED => 'gray',
            self::CLOSED => 'slate',
        };
    }

    /**
     * Get all status values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get statuses that are considered "active" (not closed).
     *
     * @return array<DisputeStatus>
     */
    public static function activeStatuses(): array
    {
        return [
            self::OPEN,
            self::UNDER_REVIEW,
            self::AWAITING_INVESTOR,
            self::ESCALATED,
            self::RESOLVED_APPROVED,
            self::RESOLVED_REJECTED,
        ];
    }

    /**
     * Get statuses that are still in progress (not resolved or closed).
     *
     * @return array<DisputeStatus>
     */
    public static function activeStates(): array
    {
        return [
            self::OPEN,
            self::UNDER_REVIEW,
            self::AWAITING_INVESTOR,
            self::ESCALATED,
        ];
    }
}
