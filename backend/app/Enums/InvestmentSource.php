<?php
// V-AUDIT-MODULE5-001 (Created) - InvestmentSource Enum to replace magic strings
// Purpose: Centralized investment source values for type safety and consistency

namespace App\Enums;

/**
 * Investment Source Enumeration
 *
 * Represents the origin of a user investment allocation.
 * Used in AllocationService and UserInvestment model.
 *
 * Values:
 * - INVESTMENT_AND_BONUS: Combined payment + bonus allocation
 * - INVESTMENT_ONLY: Payment-only allocation (no bonus)
 * - BONUS_ONLY: Pure bonus allocation
 * - REVERSAL: Reversed/cancelled investment
 * - MANUAL_ADMIN: Manual allocation by administrator
 */
enum InvestmentSource: string
{
    /**
     * Investment from payment + bonus combined
     */
    case INVESTMENT_AND_BONUS = 'investment_and_bonus';

    /**
     * Investment from payment only (no bonus)
     */
    case INVESTMENT_ONLY = 'investment';

    /**
     * Bonus-only allocation
     */
    case BONUS_ONLY = 'bonus';

    /**
     * Reversal of previous investment
     */
    case REVERSAL = 'reversal';

    /**
     * Manual allocation by admin
     */
    case MANUAL_ADMIN = 'manual_admin';

    /**
     * Get all investment source values as array
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
            self::INVESTMENT_AND_BONUS => 'Investment + Bonus',
            self::INVESTMENT_ONLY => 'Investment Only',
            self::BONUS_ONLY => 'Bonus Only',
            self::REVERSAL => 'Reversal',
            self::MANUAL_ADMIN => 'Manual (Admin)',
        };
    }

    /**
     * Check if source includes payment funds
     *
     * @return bool
     */
    public function includesPayment(): bool
    {
        return in_array($this, [
            self::INVESTMENT_AND_BONUS,
            self::INVESTMENT_ONLY,
        ]);
    }

    /**
     * Check if source includes bonus funds
     *
     * @return bool
     */
    public function includesBonus(): bool
    {
        return in_array($this, [
            self::INVESTMENT_AND_BONUS,
            self::BONUS_ONLY,
        ]);
    }
}
