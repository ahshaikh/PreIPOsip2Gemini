<?php
// V-AUDIT-MODULE3-001 (Created) - TransactionType Enum to replace magic strings
// Purpose: Centralized transaction type values to prevent typos and ensure consistency

namespace App\Enums;

/**
 * Transaction Type Enumeration
 *
 * Represents all possible transaction types in the wallet/ledger system.
 * This enum replaces hardcoded string values throughout the codebase.
 *
 * Transaction Flow:
 * CREDITS (Positive amounts):
 * - DEPOSIT: User deposits money into wallet
 * - BONUS_CREDIT: Bonus awarded to user
 * - REFUND: Pro-rata refund for cancellation
 * - ADMIN_ADJUSTMENT: Manual admin balance correction (can be +/-)
 * - REVERSAL: Cancelled withdrawal, funds returned
 *
 * DEBITS (Negative amounts):
 * - WITHDRAWAL: Funds withdrawn from wallet
 * - WITHDRAWAL_REQUEST: Withdrawal requested (locks balance)
 * - INVESTMENT: Funds used for investment (SIP purchase)
 */
enum TransactionType: string
{
    /**
     * User deposit - money added to wallet via payment gateway
     */
    case DEPOSIT = 'deposit';

    /**
     * Bonus credit - promotional bonus or reward
     */
    case BONUS_CREDIT = 'bonus_credit';

    /**
     * Refund - returned funds from cancellation or failed transaction
     */
    case REFUND = 'refund';

    /**
     * Admin adjustment - manual balance correction by administrator
     */
    case ADMIN_ADJUSTMENT = 'admin_adjustment';

    /**
     * Withdrawal - successful withdrawal (funds debited)
     */
    case WITHDRAWAL = 'withdrawal';

    /**
     * Withdrawal request - pending withdrawal (funds locked)
     */
    case WITHDRAWAL_REQUEST = 'withdrawal_request';

    /**
     * Reversal - cancelled withdrawal, funds unlocked and returned
     */
    case REVERSAL = 'reversal';

    /**
     * Investment - funds used to purchase SIP or investment product
     */
    case INVESTMENT = 'investment';

    /**
     * Interest earned - interest credit on wallet balance
     */
    case INTEREST = 'interest';

    /**
     * TDS deduction - Tax Deducted at Source
     */
    case TDS_DEDUCTION = 'tds_deduction';

    /**
     * Subscription payment - funds used for subscription fee
     */
    case SUBSCRIPTION_PAYMENT = 'subscription_payment';

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Chargeback reversal
     * Bank-initiated reversal of funds (dispute resolved in customer's favor)
     */
    case CHARGEBACK = 'chargeback';

    /**
     * Get all transaction type values as array
     * Useful for validation rules and dropdowns
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get credit transaction types (positive amounts)
     *
     * @return array<self>
     */
    public static function credits(): array
    {
        return [
            self::DEPOSIT,
            self::BONUS_CREDIT,
            self::REFUND,
            self::REVERSAL,
            self::INTEREST,
        ];
    }

    /**
     * Get debit transaction types (negative amounts)
     *
     * @return array<self>
     */
    public static function debits(): array
    {
        return [
            self::WITHDRAWAL,
            self::WITHDRAWAL_REQUEST,
            self::INVESTMENT,
            self::TDS_DEDUCTION,
            self::SUBSCRIPTION_PAYMENT,
            self::CHARGEBACK,
        ];
    }

    /**
     * Get human-readable label for the transaction type
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::DEPOSIT => 'Deposit',
            self::BONUS_CREDIT => 'Bonus Credit',
            self::REFUND => 'Refund',
            self::ADMIN_ADJUSTMENT => 'Admin Adjustment',
            self::WITHDRAWAL => 'Withdrawal',
            self::WITHDRAWAL_REQUEST => 'Withdrawal Request',
            self::REVERSAL => 'Reversal',
            self::INVESTMENT => 'Investment',
            self::INTEREST => 'Interest Earned',
            self::TDS_DEDUCTION => 'TDS Deduction',
            self::SUBSCRIPTION_PAYMENT => 'Subscription Payment',
            self::CHARGEBACK => 'Chargeback Reversal',
        };
    }

    /**
     * Check if transaction type is a credit (adds to balance)
     *
     * @return bool
     */
    public function isCredit(): bool
    {
        return in_array($this, self::credits());
    }

    /**
     * Check if transaction type is a debit (reduces balance)
     *
     * @return bool
     */
    public function isDebit(): bool
    {
        return in_array($this, self::debits());
    }

    /**
     * Get icon class for UI display
     *
     * @return string
     */
    public function icon(): string
    {
        return match($this) {
            self::DEPOSIT => 'arrow-down-circle',
            self::BONUS_CREDIT => 'gift',
            self::REFUND => 'arrow-left-circle',
            self::ADMIN_ADJUSTMENT => 'tool',
            self::WITHDRAWAL, self::WITHDRAWAL_REQUEST => 'arrow-up-circle',
            self::REVERSAL => 'arrow-counterclockwise',
            self::INVESTMENT => 'trending-up',
            self::INTEREST => 'percent',
            self::TDS_DEDUCTION => 'receipt',
            self::SUBSCRIPTION_PAYMENT => 'calendar-check',
            self::CHARGEBACK => 'alert-triangle',
        };
    }

    /**
     * Get color class for UI display
     *
     * @return string
     */
    public function color(): string
    {
        return match($this) {
            self::DEPOSIT, self::BONUS_CREDIT, self::REFUND, self::INTEREST => 'green',
            self::WITHDRAWAL, self::WITHDRAWAL_REQUEST, self::TDS_DEDUCTION, self::SUBSCRIPTION_PAYMENT => 'red',
            self::REVERSAL => 'blue',
            self::INVESTMENT => 'purple',
            self::ADMIN_ADJUSTMENT => 'gray',
            self::CHARGEBACK => 'orange', // Warning color - bank-initiated reversal
        };
    }
}
