<?php

/**
 * PROTOCOL-1 CONFIGURATION
 *
 * PURPOSE:
 * Central configuration for Protocol-1 governance enforcement framework.
 * Controls enforcement mode, monitoring settings, alerting thresholds.
 *
 * CRITICAL SETTINGS:
 * - enforcement_mode: Determines how strictly rules are enforced
 * - enabled_rule_sets: Which rule sets are active (granular control)
 * - alert_channels: Where to send critical alerts
 *
 * DEPLOYMENT STRATEGY:
 * - Development: 'monitor' mode (log only, no blocking)
 * - Staging: 'lenient' mode (block CRITICAL only)
 * - Production: 'strict' mode (block CRITICAL and HIGH)
 *
 * ENVIRONMENT OVERRIDES:
 * All settings can be overridden via .env:
 * - PROTOCOL1_ENFORCEMENT_MODE=strict
 * - PROTOCOL1_MONITORING_ENABLED=true
 * - PROTOCOL1_ALERT_EMAIL=security@preiposip.com
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Protocol-1 Enforcement Mode
    |--------------------------------------------------------------------------
    |
    | Controls how strictly Protocol-1 rules are enforced:
    |
    | - 'strict': Block all CRITICAL and HIGH severity violations (Production)
    | - 'lenient': Block only CRITICAL violations, warn on HIGH (Staging)
    | - 'monitor': Log all violations but never block (Development/Testing)
    |
    | This is the master switch for enforcement behavior.
    |
    | CURRENT STATUS: PARKED - System-level Protocol-1 is in observational mode only.
    | All blocking, alerts, and enforcement are disabled. Only passive logging active.
    |
    */

    'enforcement_mode' => env('PROTOCOL1_ENFORCEMENT_MODE', 'monitor'),

    /*
    |--------------------------------------------------------------------------
    | Protocol-1 Enabled Status
    |--------------------------------------------------------------------------
    |
    | Master on/off switch for Protocol-1 enforcement.
    | If false, all Protocol-1 validation is skipped.
    |
    | Use this for emergency rollback if Protocol-1 causes system issues.
    |
    */

    'enabled' => env('PROTOCOL1_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Enabled Rule Sets
    |--------------------------------------------------------------------------
    |
    | Granular control over which rule sets are active.
    | Allows gradual rollout of Protocol-1 rules.
    |
    | Rule Sets:
    | - platform_supremacy: Platform state overrides (Rules 1.x)
    | - immutability: Snapshot and locked record protection (Rules 2.x)
    | - actor_separation: Actor boundary enforcement (Rules 3.x)
    | - attribution: Explicit actor_type and audit trail (Rules 4.x)
    | - buy_eligibility: Investment eligibility guards (Rules 5.x)
    | - cross_phase: Cross-phase enforcement (Rules 6.x)
    |
    */

    'enabled_rule_sets' => [
        'platform_supremacy' => env('PROTOCOL1_RULE_PLATFORM_SUPREMACY', true),
        'immutability' => env('PROTOCOL1_RULE_IMMUTABILITY', true),
        'actor_separation' => env('PROTOCOL1_RULE_ACTOR_SEPARATION', true),
        'attribution' => env('PROTOCOL1_RULE_ATTRIBUTION', true),
        'buy_eligibility' => env('PROTOCOL1_RULE_BUY_ELIGIBILITY', true),
        'cross_phase' => env('PROTOCOL1_RULE_CROSS_PHASE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for Protocol-1 monitoring system.
    |
    */

    'monitoring' => [
        // Enable real-time monitoring and metrics collection
        'enabled' => env('PROTOCOL1_MONITORING_ENABLED', true),

        // Metrics retention (days)
        'metrics_retention_days' => env('PROTOCOL1_METRICS_RETENTION', 90),

        // Violation log retention (days)
        'violation_log_retention_days' => env('PROTOCOL1_VIOLATION_RETENTION', 730), // 2 years for compliance

        // Cache TTL for metrics (seconds)
        'metrics_cache_ttl' => env('PROTOCOL1_METRICS_CACHE_TTL', 300), // 5 minutes

        // Record action counter for compliance scoring
        'track_action_counter' => env('PROTOCOL1_TRACK_ACTIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Protocol-1 alert generation and delivery.
    |
    */

    'alerting' => [
        // Enable alerting system (PARKED - disabled by default)
        'enabled' => env('PROTOCOL1_ALERTING_ENABLED', false),

        // Alert on any CRITICAL violation (PARKED - disabled)
        'alert_on_critical' => env('PROTOCOL1_ALERT_CRITICAL', false),

        // Alert on high volume of violations (anomaly detection) (PARKED - disabled)
        'alert_on_anomaly' => env('PROTOCOL1_ALERT_ANOMALY', false),

        // Anomaly threshold (violations per time window)
        'anomaly_threshold' => env('PROTOCOL1_ANOMALY_THRESHOLD', 10),

        // Anomaly time window (minutes)
        'anomaly_window_minutes' => env('PROTOCOL1_ANOMALY_WINDOW', 5),

        // Alert delivery channels
        'channels' => [
            'log' => env('PROTOCOL1_ALERT_LOG', true), // Always log
            'database' => env('PROTOCOL1_ALERT_DATABASE', true), // Store in protocol1_alerts table
            'email' => env('PROTOCOL1_ALERT_EMAIL_ENABLED', false),
            'slack' => env('PROTOCOL1_ALERT_SLACK_ENABLED', false),
            'sms' => env('PROTOCOL1_ALERT_SMS_ENABLED', false),
        ],

        // Email alert recipients (comma-separated)
        'email_recipients' => env('PROTOCOL1_ALERT_EMAIL_RECIPIENTS', 'security@preiposip.com,admin@preiposip.com'),

        // Slack webhook URL for alerts
        'slack_webhook' => env('PROTOCOL1_ALERT_SLACK_WEBHOOK', null),

        // SMS alert recipients (comma-separated phone numbers)
        'sms_recipients' => env('PROTOCOL1_ALERT_SMS_RECIPIENTS', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Scoring
    |--------------------------------------------------------------------------
    |
    | Configuration for Protocol-1 compliance scoring system.
    |
    */

    'compliance' => [
        // Minimum acceptable compliance score (0-100)
        'minimum_score' => env('PROTOCOL1_MIN_COMPLIANCE_SCORE', 95),

        // Compliance score calculation mode
        // - 'daily': Calculate score per day
        // - 'rolling': Calculate score over rolling time window
        'score_mode' => env('PROTOCOL1_SCORE_MODE', 'daily'),

        // Rolling window days (if score_mode = rolling)
        'rolling_window_days' => env('PROTOCOL1_ROLLING_WINDOW', 7),

        // Grade thresholds (percentage)
        'grade_thresholds' => [
            'A+' => 95,
            'A'  => 90,
            'B+' => 85,
            'B'  => 80,
            'C+' => 75,
            'C'  => 70,
            'D'  => 60,
            'F'  => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for how Protocol-1 handles validation errors.
    |
    */

    'exceptions' => [
        // Fail-safe mode: If validation throws unexpected error, what to do?
        // - 'block': Block action (fail closed) - Most secure
        // - 'allow': Allow action (fail open) - Most resilient
        // - 'environment': Block in production, allow in dev/staging
        'fail_safe_mode' => env('PROTOCOL1_FAIL_SAFE', 'environment'),

        // Log all exceptions
        'log_exceptions' => env('PROTOCOL1_LOG_EXCEPTIONS', true),

        // Include full stack trace in logs
        'log_stack_trace' => env('PROTOCOL1_LOG_STACK_TRACE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance and Optimization
    |--------------------------------------------------------------------------
    |
    | Configuration for Protocol-1 performance optimization.
    |
    */

    'performance' => [
        // Cache validation results (for idempotent operations)
        'cache_validations' => env('PROTOCOL1_CACHE_VALIDATIONS', false),

        // Validation cache TTL (seconds)
        'validation_cache_ttl' => env('PROTOCOL1_VALIDATION_CACHE_TTL', 60),

        // Maximum validation duration (ms) before warning
        'max_validation_duration_ms' => env('PROTOCOL1_MAX_VALIDATION_MS', 500),

        // Queue violation logging (async, for high-throughput systems)
        'queue_violation_logging' => env('PROTOCOL1_QUEUE_LOGGING', false),

        // Queue name for violation logging
        'logging_queue' => env('PROTOCOL1_LOGGING_QUEUE', 'protocol1-violations'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    |
    | Configuration for Protocol-1 admin dashboard and reporting.
    |
    */

    'dashboard' => [
        // Enable admin dashboard
        'enabled' => env('PROTOCOL1_DASHBOARD_ENABLED', true),

        // Dashboard access control
        // - 'super_admin': Only super admins can access
        // - 'admin': All admins can access
        // - 'custom': Use custom policy/permission
        'access_level' => env('PROTOCOL1_DASHBOARD_ACCESS', 'admin'),

        // Dashboard refresh interval (seconds)
        'refresh_interval' => env('PROTOCOL1_DASHBOARD_REFRESH', 30),

        // Show real-time metrics
        'real_time_metrics' => env('PROTOCOL1_DASHBOARD_REALTIME', true),

        // Export violation reports
        'enable_exports' => env('PROTOCOL1_DASHBOARD_EXPORTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development and Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for Protocol-1 development and testing.
    |
    */

    'development' => [
        // Verbose logging (detailed validation steps)
        'verbose_logging' => env('PROTOCOL1_VERBOSE_LOGGING', false),

        // Log validation timing (performance profiling)
        'log_timing' => env('PROTOCOL1_LOG_TIMING', true),

        // Dry-run mode (log what would be blocked without actually blocking)
        'dry_run' => env('PROTOCOL1_DRY_RUN', false),

        // Test mode (disable certain checks for testing)
        'test_mode' => env('PROTOCOL1_TEST_MODE', false),
    ],

];
