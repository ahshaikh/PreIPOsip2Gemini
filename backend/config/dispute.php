<?php

/**
 * V-DISPUTE-RISK-2026-007: Dispute System Configuration
 *
 * Configuration for dispute tracking, caching, and aggregation.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for dispute statistics caching.
    |
    */

    // Cache store to use (redis recommended for production)
    'cache_store' => env('DISPUTE_CACHE_STORE', 'redis'),

    // Cache TTL in seconds (default: 30 minutes)
    'cache_ttl' => (int) env('DISPUTE_CACHE_TTL', 1800),

    /*
    |--------------------------------------------------------------------------
    | Aggregation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for daily dispute snapshot aggregation.
    |
    */

    // Time to run daily aggregation (24-hour format)
    'aggregation_time' => env('DISPUTE_AGGREGATION_TIME', '02:00'),

    // Number of days to keep detailed snapshots
    'snapshot_retention_days' => (int) env('DISPUTE_SNAPSHOT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for triggering dispute-related alerts.
    |
    */

    'alerts' => [
        // Alert when chargeback rate exceeds this percentage (of total payments)
        'chargeback_rate_threshold' => (float) env('DISPUTE_ALERT_CHARGEBACK_RATE', 1.0),

        // Alert when X new disputes are opened in 24 hours
        'daily_dispute_threshold' => (int) env('DISPUTE_ALERT_DAILY_COUNT', 10),

        // Alert when X users are blocked in 24 hours
        'daily_blocked_threshold' => (int) env('DISPUTE_ALERT_DAILY_BLOCKED', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for dispute reports.
    |
    */

    'reports' => [
        // Default date range for reports (days)
        'default_range_days' => (int) env('DISPUTE_REPORT_RANGE_DAYS', 30),

        // Maximum date range for reports (days)
        'max_range_days' => (int) env('DISPUTE_REPORT_MAX_RANGE_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | V-DISPUTE-MGMT-2026: Escalation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic dispute escalation.
    |
    */

    'escalation' => [
        // Amount threshold (in paise) above which disputes auto-escalate
        'amount_threshold_paise' => (int) env('DISPUTE_ESCALATION_AMOUNT_THRESHOLD', 10000000), // 1 lakh

        // Dispute types that require immediate escalation
        'auto_escalate_types' => [
            'fraud', // D_FRAUD always escalates
        ],

        // Hours before auto-escalation due to inactivity (per type)
        'inactivity_hours' => [
            'confusion' => 72,  // Low priority - 3 days
            'payment' => 48,    // Medium priority - 2 days
            'allocation' => 36, // Higher priority - 1.5 days
            'fraud' => 24,      // Highest priority - 1 day
        ],

        // Risk score threshold for auto-escalation
        'risk_score_threshold' => (int) env('DISPUTE_ESCALATION_RISK_THRESHOLD', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | V-DISPUTE-MGMT-2026: SLA Configuration
    |--------------------------------------------------------------------------
    |
    | Service Level Agreement configuration per dispute type.
    | SLA is measured from dispute creation to resolution.
    |
    */

    'sla' => [
        // SLA hours per dispute type
        'hours_by_type' => [
            'confusion' => 72,   // 3 days for user confusion issues
            'payment' => 48,     // 2 days for payment issues
            'allocation' => 36,  // 1.5 days for allocation discrepancies
            'fraud' => 24,       // 1 day for fraud/chargeback (urgent)
        ],

        // Default SLA if type not specified
        'default_hours' => 48,

        // Warning before SLA breach (hours)
        'warning_hours_before' => 4,

        // Send notifications for SLA warnings
        'notify_on_warning' => true,

        // Send notifications on SLA breach
        'notify_on_breach' => true,

        // Notification channels for SLA events
        'notification_channels' => ['database', 'mail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | V-DISPUTE-MGMT-2026: Snapshot Integrity Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for dispute snapshot integrity verification.
    |
    */

    'snapshot' => [
        // Hash algorithm for integrity verification
        'hash_algorithm' => 'sha256',

        // Whether to block resolution on integrity failure
        'block_resolution_on_failure' => true,

        // Number of recent snapshots to check in dashboard
        'dashboard_sample_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | V-DISPUTE-MGMT-2026: Settlement Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for dispute settlement actions.
    |
    */

    'settlement' => [
        // Available settlement actions
        'actions' => ['refund', 'credit', 'allocation_correction', 'none'],

        // Require approval for settlements above this amount (paise)
        'approval_threshold_paise' => (int) env('DISPUTE_SETTLEMENT_APPROVAL_THRESHOLD', 5000000), // 50k

        // Log settlement to financial_contract channel
        'log_to_financial_channel' => true,
    ],

];
