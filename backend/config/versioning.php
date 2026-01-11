<?php

/**
 * PHASE 1 STABILIZATION - Issue 6: Versioning Authority Config
 *
 * PROBLEM:
 * Platform has multiple versioning systems:
 * - disclosure_versions (for investor-facing disclosures)
 * - company_versions (for company master record)
 * - platform_context_versions (for metrics calculation logic)
 *
 * Engineers might accidentally query wrong table.
 *
 * SURGICAL FIX:
 * Explicit authority mapping defining which table is authoritative for each data type.
 * VersioningRouter service enforces this mapping.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Versioning Authority Map
    |--------------------------------------------------------------------------
    |
    | Defines the AUTHORITATIVE version source for each data domain.
    | CRITICAL: Investor logic must NEVER use wrong versioning table.
    |
    | Rules:
    | - investor_facing_data → ALWAYS use disclosure_versions
    | - company_master_record → ALWAYS use company_versions (if implemented)
    | - platform_context → ALWAYS use platform_context_versions
    |
    */

    'authority' => [
        'investor_facing_data' => [
            'source' => 'disclosure_versions',
            'description' => 'Disclosures shown to investors (business model, financials, risks, governance)',
            'usage' => 'Investment decisions, dispute resolution, regulatory compliance',
            'never_use' => ['company_versions', 'platform_context_versions'],
        ],

        'company_master_record' => [
            'source' => 'company_versions',
            'description' => 'Company profile metadata (name, logo, sector, etc.)',
            'usage' => 'Company profile page, search results, admin management',
            'never_use' => ['disclosure_versions', 'platform_context_versions'],
        ],

        'platform_context' => [
            'source' => 'platform_context_versions',
            'description' => 'Platform calculation logic versions (metrics, flags, valuations)',
            'usage' => 'Reproducing old calculations, audit trail, methodology evolution',
            'never_use' => ['disclosure_versions', 'company_versions'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Version Query Enforcement
    |--------------------------------------------------------------------------
    |
    | Controls how strictly versioning authority is enforced.
    |
    */

    'enforcement' => [
        // Throw exception if wrong versioning table queried
        'strict_mode' => env('VERSIONING_STRICT_MODE', true),

        // Log warnings when wrong table accessed
        'log_violations' => true,

        // Track violations for monitoring
        'track_violations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Version Table Metadata
    |--------------------------------------------------------------------------
    |
    | Metadata about each versioning table for documentation and validation.
    |
    */

    'tables' => [
        'disclosure_versions' => [
            'table_name' => 'disclosure_versions',
            'model' => \App\Models\DisclosureVersion::class,
            'immutable' => true,
            'purpose' => 'Investor-facing disclosure snapshots at approval time',
            'created_phase' => 'Phase 1',
        ],

        'company_versions' => [
            'table_name' => 'company_versions',
            'model' => \App\Models\CompanyVersion::class ?? null,
            'immutable' => false,
            'purpose' => 'Company profile metadata changes over time',
            'created_phase' => 'Pre-Phase 1 (if exists)',
            'note' => 'Different from disclosure_versions - this is company metadata, not investor disclosures',
        ],

        'platform_context_versions' => [
            'table_name' => 'platform_context_versions',
            'model' => null, // No Eloquent model, accessed via PlatformContextGuard
            'immutable' => true,
            'purpose' => 'Platform calculation methodology versions',
            'created_phase' => 'Phase 4 Stabilization',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Mistakes Prevention
    |--------------------------------------------------------------------------
    |
    | Documentation of common mistakes engineers might make.
    |
    */

    'common_mistakes' => [
        [
            'mistake' => 'Using company_versions for investor decision data',
            'why_wrong' => 'Company versions track profile metadata, not SEBI-compliant disclosures',
            'correct_approach' => 'Use disclosure_versions for any data shown to investors',
            'example_wrong' => 'CompanyVersion::where(...)->get() // For investor-facing financial data',
            'example_correct' => 'DisclosureVersion::where(...)->get() // For investor-facing financial data',
        ],

        [
            'mistake' => 'Mixing disclosure_versions and company_versions in same query',
            'why_wrong' => 'These tables version different domains and are not interchangeable',
            'correct_approach' => 'Use VersioningRouter to get authoritative source for data type',
            'example_wrong' => 'SELECT * FROM disclosure_versions JOIN company_versions ...',
            'example_correct' => 'VersioningRouter::getAuthoritativeSource("investor_facing_data")',
        ],

        [
            'mistake' => 'Not checking version when reproducing platform calculations',
            'why_wrong' => 'Platform logic evolves - must use correct version to reproduce old calculation',
            'correct_approach' => 'PlatformContextGuard::getCurrentVersion("company_metrics")',
            'example_wrong' => 'calculateMetrics() // Uses latest logic for old data',
            'example_correct' => 'calculateMetrics($version = "v1.0.0") // Reproduces with old logic',
        ],
    ],

];
