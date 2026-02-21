<?php
/**
 * V-CHARGEBACK-SEMANTICS-2026: Explicit Reversal Source Enum
 *
 * FINANCIAL CONTRACT:
 * Investment reversal is a SHARE-ONLY operation. The reversal source
 * determines what the CALLING SERVICE should do with the wallet:
 *
 * - REFUND: Service CREDITS wallet (user gets money back)
 * - CHARGEBACK: Service DEBITS wallet (user owes chargeback amount)
 * - ADMIN_CORRECTION: Context-dependent, admin decides
 * - ALLOCATION_FAILURE: System handles, typically no wallet change
 *
 * This enum replaces string-based branching (e.g., str_contains($reason, 'Chargeback'))
 * with explicit, type-safe domain semantics.
 */

namespace App\Enums;

enum ReversalSource: string
{
    /**
     * Normal refund initiated by merchant.
     * User keeps money, shares returned to inventory.
     * Calling service SHOULD credit wallet.
     */
    case REFUND = 'refund';

    /**
     * Bank-initiated chargeback (dispute won by customer).
     * User loses shares AND owes chargeback amount.
     * Calling service SHOULD debit wallet (to zero if insufficient).
     * Shortfall becomes accounts receivable.
     */
    case CHARGEBACK = 'chargeback';

    /**
     * Admin-initiated correction.
     * Wallet implications are context-dependent.
     */
    case ADMIN_CORRECTION = 'admin_correction';

    /**
     * System allocation failure compensation.
     * Typically no wallet change (shares were never allocated).
     */
    case ALLOCATION_FAILURE = 'allocation_failure';

    /**
     * Determine if this reversal type should credit wallet.
     * This is advisory - the calling service makes the final decision.
     */
    public function shouldCreditWallet(): bool
    {
        return match ($this) {
            self::REFUND => true,
            self::CHARGEBACK => false, // Chargeback DEBITS, not credits
            self::ADMIN_CORRECTION => false, // Context-dependent
            self::ALLOCATION_FAILURE => false,
        };
    }

    /**
     * Determine if this reversal type should debit wallet.
     */
    public function shouldDebitWallet(): bool
    {
        return match ($this) {
            self::CHARGEBACK => true, // Bank already clawed back funds
            default => false,
        };
    }

    /**
     * Get human-readable label for audit logs.
     */
    public function label(): string
    {
        return match ($this) {
            self::REFUND => 'Refund',
            self::CHARGEBACK => 'Chargeback',
            self::ADMIN_CORRECTION => 'Admin Correction',
            self::ALLOCATION_FAILURE => 'Allocation Failure',
        };
    }
}
