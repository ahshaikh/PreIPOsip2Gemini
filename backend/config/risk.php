<?php

/**
 * V-DISPUTE-RISK-2026-004: Risk Scoring Configuration
 *
 * Config-driven thresholds for user risk assessment.
 * All values can be overridden via environment variables.
 *
 * SCORING MODEL:
 * - Deterministic (same inputs → same score)
 * - No decay logic (score never decreases automatically)
 * - Capped at 100 (maximum risk)
 *
 * BLOCKING POLICY:
 * - Auto-block when score >= blocking_threshold
 * - Manual unblock only by admin action
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Risk Score Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for categorizing risk levels and triggering actions.
    |
    */

    'thresholds' => [
        // Score at which user is automatically blocked from investments
        'blocking' => (int) env('RISK_BLOCKING_THRESHOLD', 70),

        // Score at which user is considered high-risk (for reporting)
        'high_risk' => (int) env('RISK_HIGH_THRESHOLD', 50),

        // Score at which user is flagged for review
        'review' => (int) env('RISK_REVIEW_THRESHOLD', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring Weights
    |--------------------------------------------------------------------------
    |
    | Points added to risk score for each factor.
    | Higher weights = more impact on final score.
    |
    */

    'weights' => [
        // Points per confirmed chargeback (base)
        'chargeback_base' => (int) env('RISK_WEIGHT_CHARGEBACK_BASE', 25),

        // Additional points per chargeback after first (escalation)
        'chargeback_repeat' => (int) env('RISK_WEIGHT_CHARGEBACK_REPEAT', 15),

        // Points for high chargeback-to-payment ratio (>20%)
        'high_chargeback_ratio' => (int) env('RISK_WEIGHT_HIGH_RATIO', 20),

        // Points for very high ratio (>40%)
        'very_high_chargeback_ratio' => (int) env('RISK_WEIGHT_VERY_HIGH_RATIO', 30),

        // Points for new account (<30 days) with chargeback
        'new_account_chargeback' => (int) env('RISK_WEIGHT_NEW_ACCOUNT', 10),

        // Points per open dispute
        'open_dispute' => (int) env('RISK_WEIGHT_OPEN_DISPUTE', 5),

        // Points for critical/high severity dispute
        'critical_dispute' => (int) env('RISK_WEIGHT_CRITICAL_DISPUTE', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ratio Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for chargeback-to-payment ratio calculations.
    |
    */

    'ratios' => [
        // Ratio above which "high_chargeback_ratio" weight applies
        'high' => (float) env('RISK_RATIO_HIGH', 0.20),

        // Ratio above which "very_high_chargeback_ratio" weight applies
        'very_high' => (float) env('RISK_RATIO_VERY_HIGH', 0.40),

        // Minimum payments required before ratio is calculated
        'min_payments_for_ratio' => (int) env('RISK_RATIO_MIN_PAYMENTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Age Thresholds
    |--------------------------------------------------------------------------
    |
    | Account age-based risk factors.
    |
    */

    'account_age' => [
        // Days within which account is considered "new"
        'new_account_days' => (int) env('RISK_NEW_ACCOUNT_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for audit trail and logging.
    |
    */

    'audit' => [
        // Whether to log all score calculations (for debugging)
        'log_calculations' => (bool) env('RISK_LOG_CALCULATIONS', true),

        // Log channel for risk-related events
        'log_channel' => env('RISK_LOG_CHANNEL', 'financial_contract'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Score
    |--------------------------------------------------------------------------
    |
    | Hard cap on risk score. Score can never exceed this value.
    |
    */

    'max_score' => 100,

];
