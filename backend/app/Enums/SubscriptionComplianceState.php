<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Subscription Compliance State Enum
 *
 * Represents the derived compliance state for user subscriptions.
 * This is NOT persisted - it's derived from Subscription.status
 *
 * @package App\Enums
 */
enum SubscriptionComplianceState: string
{
    case NONE = 'none';           // No subscription exists
    case PENDING = 'pending';     // Subscription awaiting first payment
    case ACTIVE = 'active';       // Subscription active
    case PAUSED = 'paused';       // Subscription paused
    case CANCELLED = 'cancelled'; // Subscription cancelled
    case COMPLETED = 'completed'; // Subscription completed

    /**
     * Derive subscription compliance state from Subscription status
     *
     * @param string|null $subscriptionStatus
     * @return self
     */
    public static function fromSubscriptionStatus(?string $subscriptionStatus): self
    {
        if ($subscriptionStatus === null) {
            return self::NONE;
        }

        return match ($subscriptionStatus) {
            'pending' => self::PENDING,
            'active' => self::ACTIVE,
            'paused' => self::PAUSED,
            'cancelled' => self::CANCELLED,
            'completed' => self::COMPLETED,
            default => self::NONE,
        };
    }

    /**
     * Check if subscription allows new payments
     *
     * @return bool
     */
    public function allowsPayments(): bool
    {
        return in_array($this, [self::ACTIVE], true);
    }

    /**
     * Check if subscription can be modified
     *
     * @return bool
     */
    public function canBeModified(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAUSED], true);
    }

    /**
     * Check if subscription is active or paused (resumable)
     *
     * @return bool
     */
    public function isActiveOrPaused(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAUSED], true);
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::NONE => 'No Subscription',
            self::PENDING => 'Pending Payment',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }
}
