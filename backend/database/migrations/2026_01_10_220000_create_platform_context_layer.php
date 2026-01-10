<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 4 - PLATFORM CONTEXT LAYER MIGRATION
 *
 * PURPOSE:
 * Create database tables for platform-generated analysis and context.
 * These tables store platform calculations, NOT company-provided data.
 *
 * REGULATORY COMPLIANCE:
 * - Clear separation: Company data vs Platform analysis vs Investor decision
 * - Platform metrics are READ-ONLY for companies
 * - All calculations are transparent and auditable
 * - No investment advice or recommendations
 *
 * TABLES:
 * 1. platform_company_metrics: Health scores, completeness metrics
 * 2. platform_risk_flags: Automated risk detection signals
 * 3. platform_valuation_context: Peer comparison data
 * 4. investor_view_history: Track what investors saw and when
 * 5. disclosure_change_log: Track changes for "what's new" feature
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================================
        // TABLE 1: Platform Company Metrics
        // =====================================================================
        // PURPOSE: Store platform-calculated health scores and completeness metrics
        // CRITICAL: These are PLATFORM-GENERATED, not company-editable
        Schema::create('platform_company_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Disclosure Completeness
            $table->decimal('disclosure_completeness_score', 5, 2)->comment('0-100: % of disclosure fields completed');
            $table->integer('total_fields')->comment('Total number of disclosure fields');
            $table->integer('completed_fields')->comment('Number of completed fields');
            $table->integer('missing_critical_fields')->comment('Count of missing critical/required fields');

            // Financial Health Band (NOT a rating, just a band)
            $table->enum('financial_health_band', ['insufficient_data', 'concerning', 'moderate', 'healthy', 'strong'])
                ->comment('Platform assessment band based on disclosed financials');
            $table->json('financial_health_factors')->nullable()->comment('Factors contributing to band (transparency)');

            // Governance Quality Band
            $table->enum('governance_quality_band', ['insufficient_data', 'basic', 'standard', 'strong', 'exemplary'])
                ->comment('Platform assessment based on disclosed governance practices');
            $table->json('governance_quality_factors')->nullable()->comment('Factors contributing to band');

            // Risk Intensity Band
            $table->enum('risk_intensity_band', ['insufficient_data', 'low', 'moderate', 'high', 'very_high'])
                ->comment('Platform assessment based on disclosed risk factors');
            $table->integer('disclosed_risk_count')->comment('Total number of disclosed risks');
            $table->integer('critical_risk_count')->comment('Number of high/critical severity risks');

            // Valuation Reasonableness Context (NOT a recommendation)
            $table->enum('valuation_context', ['insufficient_data', 'below_peers', 'at_peers', 'above_peers', 'premium'])
                ->nullable()->comment('Comparative context vs peer group, NOT a recommendation');
            $table->json('valuation_context_data')->nullable()->comment('Peer comparison data for transparency');

            // Data Freshness
            $table->timestamp('last_disclosure_update')->nullable()->comment('When company last updated disclosures');
            $table->timestamp('last_platform_review')->nullable()->comment('When platform last recalculated metrics');
            $table->boolean('is_under_admin_review')->default(false)->comment('Whether disclosures are currently under admin review');

            // Metadata
            $table->string('calculation_version', 50)->comment('Version of calculation algorithm used');
            $table->json('calculation_metadata')->nullable()->comment('Full calculation methodology for audit trail');

            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('financial_health_band');
            $table->index('governance_quality_band');
            $table->index('risk_intensity_band');
            $table->index(['last_disclosure_update', 'last_platform_review']);
        });

        // =====================================================================
        // TABLE 2: Platform Risk Flags
        // =====================================================================
        // PURPOSE: Store automated risk detection signals
        // CRITICAL: These are FLAGS, not ratings. Informational only.
        Schema::create('platform_risk_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Flag Identification
            $table->string('flag_type', 100)->comment('Type of risk flag detected');
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical'])->comment('Flag severity level');
            $table->enum('category', [
                'financial',
                'governance',
                'legal',
                'disclosure_quality',
                'market',
                'operational'
            ])->comment('Risk category');

            // Flag Details
            $table->text('description')->comment('Human-readable description of the flag');
            $table->text('detection_logic')->comment('How this flag was detected (transparency)');
            $table->json('supporting_data')->nullable()->comment('Data points that triggered this flag');
            $table->json('context')->nullable()->comment('Additional context for investors');

            // Source Traceability
            $table->foreignId('disclosure_id')->nullable()->constrained('company_disclosures')->onDelete('set null')
                ->comment('Which disclosure triggered this flag');
            $table->string('disclosure_field_path')->nullable()->comment('Specific field that triggered flag');

            // Flag Lifecycle
            $table->enum('status', ['active', 'resolved', 'dismissed', 'superseded'])->default('active');
            $table->timestamp('detected_at')->comment('When flag was first detected');
            $table->timestamp('resolved_at')->nullable()->comment('When flag was resolved');
            $table->text('resolution_notes')->nullable()->comment('How flag was resolved');

            // Investor Visibility
            $table->boolean('is_visible_to_investors')->default(true)
                ->comment('Whether flag should be shown to investors');
            $table->text('investor_message')->nullable()->comment('Investor-friendly explanation of flag');

            // Metadata
            $table->string('detection_version', 50)->comment('Version of detection algorithm');
            $table->json('metadata')->nullable()->comment('Additional metadata for audit trail');

            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category', 'severity']);
            $table->index('flag_type');
            $table->index('detected_at');
        });

        // =====================================================================
        // TABLE 3: Platform Valuation Context
        // =====================================================================
        // PURPOSE: Provide peer comparison context, NOT recommendations
        // CRITICAL: This is COMPARATIVE DATA, not investment advice
        Schema::create('platform_valuation_context', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Peer Group Definition
            $table->string('peer_group_name')->comment('Name of peer group used for comparison');
            $table->json('peer_company_ids')->comment('IDs of companies in peer group');
            $table->integer('peer_count')->comment('Number of companies in peer group');
            $table->text('peer_selection_criteria')->comment('How peers were selected (transparency)');

            // Comparative Metrics (NOT RECOMMENDATIONS)
            $table->decimal('company_valuation', 15, 2)->nullable()->comment('Current valuation (if disclosed)');
            $table->decimal('peer_median_valuation', 15, 2)->nullable()->comment('Median valuation of peer group');
            $table->decimal('peer_p25_valuation', 15, 2)->nullable()->comment('25th percentile peer valuation');
            $table->decimal('peer_p75_valuation', 15, 2)->nullable()->comment('75th percentile peer valuation');

            // Revenue Multiple Context
            $table->decimal('company_revenue_multiple', 8, 2)->nullable()->comment('Company valuation / revenue (if available)');
            $table->decimal('peer_median_revenue_multiple', 8, 2)->nullable()->comment('Peer median revenue multiple');

            // Growth Context
            $table->decimal('company_revenue_growth_rate', 8, 2)->nullable()->comment('Company YoY revenue growth %');
            $table->decimal('peer_median_revenue_growth', 8, 2)->nullable()->comment('Peer median growth rate');

            // Liquidity Context (NOT PREDICTIONS)
            $table->enum('liquidity_outlook', [
                'insufficient_data',
                'limited_market',
                'developing_market',
                'active_market',
                'liquid_market'
            ])->nullable()->comment('Platform assessment of market liquidity, NOT a prediction');
            $table->json('liquidity_factors')->nullable()->comment('Factors affecting liquidity assessment');

            // Market Activity Context
            $table->integer('recent_transaction_count')->default(0)->comment('Number of transactions in last 90 days');
            $table->decimal('recent_avg_transaction_size', 15, 2)->nullable()->comment('Average transaction size');
            $table->decimal('bid_ask_spread_percentage', 5, 2)->nullable()->comment('Current bid-ask spread %');

            // Data Freshness
            $table->timestamp('calculated_at')->comment('When this context was calculated');
            $table->timestamp('data_as_of')->comment('Date of underlying data');
            $table->boolean('is_stale')->default(false)->comment('Whether data needs recalculation');

            // Metadata
            $table->string('calculation_version', 50)->comment('Version of calculation methodology');
            $table->json('methodology_notes')->nullable()->comment('Full methodology for transparency');
            $table->json('data_sources')->nullable()->comment('Sources of comparative data');

            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('peer_group_name');
            $table->index(['calculated_at', 'is_stale']);
        });

        // =====================================================================
        // TABLE 4: Investor View History
        // =====================================================================
        // PURPOSE: Track what investors saw and when (for "what's new" feature)
        // CRITICAL: Used to show investors what changed since their last visit
        Schema::create('investor_view_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // View Snapshot
            $table->timestamp('viewed_at')->comment('When investor viewed this company');
            $table->string('view_type', 50)->comment('Type of view: profile, disclosure, metrics');

            // Data Snapshot (what investor saw)
            $table->json('disclosure_snapshot')->nullable()->comment('IDs and versions of disclosures viewed');
            $table->json('metrics_snapshot')->nullable()->comment('Platform metrics at time of view');
            $table->json('risk_flags_snapshot')->nullable()->comment('Risk flags visible at time of view');

            // Change Tracking
            $table->boolean('was_under_review')->default(false)->comment('Whether data was under review at view time');
            $table->timestamp('data_as_of')->nullable()->comment('Timestamp of data viewed');

            // Session Tracking
            $table->string('session_id', 100)->nullable()->comment('Session ID for grouping related views');
            $table->string('ip_address', 45)->nullable()->comment('IP address of viewer');
            $table->text('user_agent')->nullable()->comment('User agent string');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'company_id', 'viewed_at']);
            $table->index('company_id');
            $table->index('viewed_at');
        });

        // =====================================================================
        // TABLE 5: Disclosure Change Log
        // =====================================================================
        // PURPOSE: Track all changes to disclosures for "what's new" feature
        // CRITICAL: Complete audit trail of all disclosure modifications
        Schema::create('disclosure_change_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_disclosure_id')->constrained('company_disclosures')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Change Details
            $table->enum('change_type', [
                'created',
                'draft_updated',
                'submitted',
                'approved',
                'rejected',
                'error_reported',
                'clarification_added',
                'clarification_answered'
            ])->comment('Type of change that occurred');

            $table->text('change_summary')->comment('Human-readable summary of change');
            $table->json('changed_fields')->nullable()->comment('List of fields that changed');
            $table->json('field_diffs')->nullable()->comment('Before/after values for changed fields');

            // Change Metadata
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('User who made the change');
            $table->timestamp('changed_at')->comment('When change occurred');
            $table->string('change_reason', 500)->nullable()->comment('Reason for change (if provided)');

            // Impact Assessment
            $table->boolean('is_material_change')->default(false)
                ->comment('Whether change is material enough to notify investors');
            $table->enum('investor_notification_priority', ['none', 'low', 'medium', 'high', 'critical'])
                ->default('none')->comment('Priority level for investor notification');

            // Version Tracking
            $table->integer('version_before')->nullable()->comment('Disclosure version before change');
            $table->integer('version_after')->nullable()->comment('Disclosure version after change');

            // Visibility
            $table->boolean('is_visible_to_investors')->default(true)
                ->comment('Whether change should be visible in change history');
            $table->timestamp('investor_visible_at')->nullable()
                ->comment('When change became visible to investors (may be delayed for admin review)');

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'changed_at']);
            $table->index(['company_disclosure_id', 'changed_at']);
            $table->index('change_type');
            $table->index(['is_material_change', 'investor_notification_priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disclosure_change_log');
        Schema::dropIfExists('investor_view_history');
        Schema::dropIfExists('platform_valuation_context');
        Schema::dropIfExists('platform_risk_flags');
        Schema::dropIfExists('platform_company_metrics');
    }
};
