<?php
// V-AUDIT-MODULE13-004 (MEDIUM): TicketCategory Enum to replace hardcoded validation strings
// Created: 2025-12-17 | Prevents validation errors when business adds new categories

namespace App\Enums;

/**
 * TicketCategory Enumeration
 *
 * V-AUDIT-MODULE13-004 (MEDIUM): Move valid categories to a TicketCategory Enum
 *
 * Previous Issue:
 * User/SupportTicketController::store() validated categories against a hardcoded string:
 * 'category' => 'required|string|in:technical,investment,payment,kyc,withdrawal,bonus,account,subscription,general,other'
 *
 * Problem: When the business adds a new category (e.g., "Referrals") in the frontend
 * or database, the API rejects it until a developer manually updates the validation string.
 * This creates deployment dependencies and maintenance overhead.
 *
 * Fix:
 * Centralized category definitions in this Enum. Controllers use TicketCategory::values()
 * for validation, automatically including any new categories added here.
 *
 * Benefits:
 * - Single source of truth for all ticket categories
 * - Adding new categories doesn't require controller changes
 * - Type-safe category handling throughout the application
 * - Self-documenting code with category descriptions
 *
 * Usage in Validation:
 * ```php
 * use App\Enums\TicketCategory;
 * use Illuminate\Validation\Rules\Enum;
 *
 * $request->validate([
 *     'category' => ['required', new Enum(TicketCategory::class)],
 * ]);
 * ```
 */
enum TicketCategory: string
{
    /**
     * Technical issues: Login problems, bugs, errors, app crashes
     */
    case TECHNICAL = 'technical';

    /**
     * Investment-related queries: How to invest, plan details, portfolio questions
     */
    case INVESTMENT = 'investment';

    /**
     * Payment issues: Failed transactions, payment not received, refunds
     */
    case PAYMENT = 'payment';

    /**
     * KYC verification: Document submission issues, verification delays, rejections
     */
    case KYC = 'kyc';

    /**
     * Withdrawal requests: Withdrawal delays, failed withdrawals, bank details
     */
    case WITHDRAWAL = 'withdrawal';

    /**
     * Bonus and rewards: Bonus not credited, referral bonus, promotional offers
     */
    case BONUS = 'bonus';

    /**
     * Account management: Profile updates, password reset, account security
     */
    case ACCOUNT = 'account';

    /**
     * Subscription (SIP): SIP setup, SIP pausing, SIP cancellation
     */
    case SUBSCRIPTION = 'subscription';

    /**
     * General inquiries: Product information, company questions, feedback
     */
    case GENERAL = 'general';

    /**
     * Other: Anything not fitting above categories
     */
    case OTHER = 'other';

    /**
     * Get all category values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for frontend
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::TECHNICAL => 'Technical Support',
            self::INVESTMENT => 'Investment Inquiry',
            self::PAYMENT => 'Payment Issue',
            self::KYC => 'KYC Verification',
            self::WITHDRAWAL => 'Withdrawal Request',
            self::BONUS => 'Bonuses & Rewards',
            self::ACCOUNT => 'Account Management',
            self::SUBSCRIPTION => 'Subscription (SIP)',
            self::GENERAL => 'General Inquiry',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get icon for frontend (optional helper)
     *
     * @return string
     */
    public function icon(): string
    {
        return match($this) {
            self::TECHNICAL => 'wrench',
            self::INVESTMENT => 'chart-line',
            self::PAYMENT => 'credit-card',
            self::KYC => 'id-card',
            self::WITHDRAWAL => 'money-bill',
            self::BONUS => 'gift',
            self::ACCOUNT => 'user',
            self::SUBSCRIPTION => 'repeat',
            self::GENERAL => 'question-circle',
            self::OTHER => 'ellipsis-h',
        };
    }
}
