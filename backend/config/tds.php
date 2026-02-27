<?php

/**
 * [P1.3 FIX]: TDS (Tax Deducted at Source) Configuration
 *
 * WHY: Centralizes all TDS rates to prevent scattered hardcoded values.
 * Single source of truth for tax calculations across the platform.
 *
 * COMPLIANCE: As per Indian tax law:
 * - Bonuses & commissions attract 10% TDS (Section 194H)
 * - Withdrawals may attract 1% TDS (Section 194J for professional services)
 * - Referral income treated as commission (10% TDS)
 *
 * Rates can be modified by admin without code changes.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | TDS Rates (in percentage)
    |--------------------------------------------------------------------------
    |
    | These rates are applied to different transaction types.
    | Values are percentages (e.g., 10.0 = 10%)
    |
    */
    'rates' => [
        /**
         * TDS on bonus transactions (consistency, milestone, progressive bonuses)
         * Default: 10% as per Section 194H (commission/bonus)
         */
        'bonus' => env('TDS_RATE_BONUS', 10.0),

        /**
         * TDS on referral income (when referrer receives bonus)
         * Default: 10% as per Section 194H (commission)
         */
        'referral' => env('TDS_RATE_REFERRAL', 10.0),

        /**
         * TDS on withdrawals (when user withdraws from wallet)
         * Default: 1% as per Section 194J (professional services)
         */
        'withdrawal' => env('TDS_RATE_WITHDRAWAL', 1.0),

        /**
         * TDS on profit share distributions
         * Default: 10%
         */
        'profit_share' => env('TDS_RATE_PROFIT_SHARE', 10.0),

        /**
         * TDS on celebration bonuses (birthday, anniversary, festival)
         * Default: 10% (treated same as regular bonus)
         */
        'celebration' => env('TDS_RATE_CELEBRATION', 10.0),

        /**
         * TDS on lucky draw winnings
         * Default: 30% as per Section 194B (winnings from lottery/crossword puzzle)
         */
        'lucky_draw' => env('TDS_RATE_LUCKY_DRAW', 30.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | TDS Exemption Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum amount below which TDS is not deducted.
    | As per Section 194H: No TDS if annual income < ₹15,000
    |
    */
    'exemption_threshold' => [
        'bonus' => env('TDS_THRESHOLD_BONUS', 0), // No threshold for bonuses
        'referral' => env('TDS_THRESHOLD_REFERRAL', 0),
        'withdrawal' => env('TDS_THRESHOLD_WITHDRAWAL', 0),
        'profit_share' => env('TDS_THRESHOLD_PROFIT_SHARE', 0),
        'celebration' => env('TDS_THRESHOLD_CELEBRATION', 0), // Birthday/anniversary/festival bonuses
        'lucky_draw' => env('TDS_THRESHOLD_LUCKY_DRAW', 10000), // Section 194B: No TDS if winnings <= ₹10,000
    ],

    /*
    |--------------------------------------------------------------------------
    | Rounding Configuration
    |--------------------------------------------------------------------------
    |
    | How to round TDS amounts (floor/ceil/round)
    | Default: 'round' for fairness
    |
    */
    'rounding' => [
        'mode' => env('TDS_ROUNDING_MODE', 'round'), // floor, ceil, round
        'decimals' => env('TDS_ROUNDING_DECIMALS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | TDS Reporting
    |--------------------------------------------------------------------------
    |
    | Configuration for TDS reports and filing
    |
    */
    'reporting' => [
        /**
         * Whether to generate quarterly TDS reports
         */
        'quarterly_reports' => env('TDS_QUARTERLY_REPORTS', true),

        /**
         * Email to send TDS reports to (for compliance officer)
         */
        'report_email' => env('TDS_REPORT_EMAIL', 'compliance@example.com'),
    ],
];
