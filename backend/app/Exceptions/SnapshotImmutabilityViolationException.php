<?php
// V-CONTRACT-HARDENING-FINAL: Snapshot immutability violation exception
// Thrown when code attempts to modify immutable subscription snapshot fields.
// This is a CRITICAL FAILURE - indicates architectural violation.

namespace App\Exceptions;

use Exception;

/**
 * SnapshotImmutabilityViolationException
 *
 * Thrown when code attempts to modify subscription bonus config snapshot fields
 * after the snapshot has been created.
 *
 * These fields are IMMUTABLE by design:
 * - progressive_config
 * - milestone_config
 * - consistency_config
 * - welcome_bonus_config
 * - referral_tiers
 * - celebration_bonus_config
 * - lucky_draw_entries
 * - config_snapshot_at
 * - config_snapshot_version
 *
 * RESPONSE PROTOCOL:
 * 1. Block the update operation
 * 2. Log to financial_contract audit channel
 * 3. Alert developers (this is a code bug, not user error)
 *
 * ALERT LEVEL: HIGH
 * LOG CHANNEL: financial_contract
 */
class SnapshotImmutabilityViolationException extends Exception
{
    protected $code = 500;

    protected int $subscriptionId;
    protected array $violatedFields;

    public function __construct(int $subscriptionId, array $violatedFields)
    {
        $this->subscriptionId = $subscriptionId;
        $this->violatedFields = $violatedFields;

        parent::__construct(
            "[SNAPSHOT IMMUTABILITY VIOLATION] Attempted to modify immutable fields on Subscription #{$subscriptionId}: " .
            implode(', ', $violatedFields) . ". " .
            "This is an architectural violation. Snapshot fields are FROZEN after creation."
        );
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getViolatedFields(): array
    {
        return $this->violatedFields;
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Return structured context for logging
     */
    public function reportContext(): array
    {
        return [
            'exception_type' => 'SnapshotImmutabilityViolationException',
            'alert_level' => 'HIGH',
            'subscription_id' => $this->subscriptionId,
            'violated_fields' => $this->violatedFields,
            'violated_count' => count($this->violatedFields),
            'action_required' => 'Review code path attempting modification',
            'root_cause' => 'Code bug - not user error',
        ];
    }
}
