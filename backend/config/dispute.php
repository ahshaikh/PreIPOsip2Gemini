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

];
