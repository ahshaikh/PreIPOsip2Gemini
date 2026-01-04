<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Wallet Compliance State Enum
 *
 * Represents the derived compliance state for user wallet.
 * This is NOT persisted - it's derived from Wallet existence and balance
 *
 * @package App\Enums
 */
enum WalletComplianceState: string
{
    case INACTIVE = 'inactive';  // No wallet exists
    case ACTIVE = 'active';      // Wallet exists (regardless of balance)

    /**
     * Check if wallet is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'Wallet Not Created',
            self::ACTIVE => 'Wallet Active',
        };
    }
}
