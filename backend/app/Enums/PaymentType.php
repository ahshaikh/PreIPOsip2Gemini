<?php
// V-AUDIT-MODULE5-002 (Created) - PaymentType Enum to replace magic strings
// Purpose: Centralized payment type values for subscription and payment logic

namespace App\Enums;

/**
 * Payment Type Enumeration
 *
 * Represents the type/purpose of a payment in the system.
 * Used in SubscriptionService and Payment model.
 *
 * Values:
 * - SIP_INSTALLMENT: Regular SIP monthly payment
 * - SUBSCRIPTION_INITIAL: First payment when creating subscription
 * - UPGRADE_CHARGE: Pro-rated charge when upgrading plan
 * - ONE_TIME: One-time investment (not recurring)
 * - PENALTY: Late payment penalty or cancellation fee
 * - REFUND: Refund to user
 */
enum PaymentType: string
{
    /**
     * Regular SIP monthly installment
     */
    case SIP_INSTALLMENT = 'sip_installment';

    /**
     * Initial subscription payment
     */
    case SUBSCRIPTION_INITIAL = 'subscription_initial';

    /**
     * Plan upgrade pro-rated charge
     */
    case UPGRADE_CHARGE = 'upgrade_charge';

    /**
     * One-time investment payment
     */
    case ONE_TIME = 'one_time';

    /**
     * Late payment penalty
     */
    case PENALTY = 'penalty';

    /**
     * Refund payment
     */
    case REFUND = 'refund';

    /**
     * Downgrade credit (unused, for future)
     */
    case DOWNGRADE_CREDIT = 'downgrade_credit';

    /**
     * Get all payment type values as array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::SIP_INSTALLMENT => 'SIP Installment',
            self::SUBSCRIPTION_INITIAL => 'Initial Subscription',
            self::UPGRADE_CHARGE => 'Upgrade Charge',
            self::ONE_TIME => 'One-time Payment',
            self::PENALTY => 'Penalty',
            self::REFUND => 'Refund',
            self::DOWNGRADE_CREDIT => 'Downgrade Credit',
        };
    }

    /**
     * Check if payment type is recurring
     *
     * @return bool
     */
    public function isRecurring(): bool
    {
        return in_array($this, [
            self::SIP_INSTALLMENT,
        ]);
    }

    /**
     * Check if payment type is a charge (debit)
     *
     * @return bool
     */
    public function isCharge(): bool
    {
        return !in_array($this, [
            self::REFUND,
            self::DOWNGRADE_CREDIT,
        ]);
    }

    /**
     * Get icon for UI display
     *
     * @return string
     */
    public function icon(): string
    {
        return match($this) {
            self::SIP_INSTALLMENT => 'calendar-repeat',
            self::SUBSCRIPTION_INITIAL => 'play-circle',
            self::UPGRADE_CHARGE => 'arrow-up-circle',
            self::ONE_TIME => 'cash',
            self::PENALTY => 'exclamation-triangle',
            self::REFUND => 'arrow-counterclockwise',
            self::DOWNGRADE_CREDIT => 'arrow-down-circle',
        };
    }
}
