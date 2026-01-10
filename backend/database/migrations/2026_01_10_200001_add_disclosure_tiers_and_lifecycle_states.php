<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 2 - MIGRATION 1/1: Add Disclosure Tiers and Company Lifecycle States
 *
 * PURPOSE:
 * Implements tiered disclosure approval system and company lifecycle state engine.
 *
 * TIERED DISCLOSURE SYSTEM:
 * - Tier 1: Basic identity & business → limited public visibility
 * - Tier 2: Financials & offer details → enables buying
 * - Tier 3: Advanced disclosures → full trust/transparency
 *
 * COMPANY LIFECYCLE STATES:
 * - draft: Company profile incomplete, not visible
 * - live_limited: Tier 1 approved, public profile visible (no buying)
 * - live_investable: Tier 2 approved, buying enabled
 * - live_fully_disclosed: Tier 3 approved, maximum trust badge
 * - suspended: Admin freeze, buying disabled, warning shown
 *
 * STATE TRANSITIONS:
 * draft → live_limited (when Tier 1 modules approved)
 * live_limited → live_investable (when Tier 2 modules approved)
 * live_investable → live_fully_disclosed (when Tier 3 modules approved)
 * any → suspended (admin action)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================================
        // ADD TIER TO DISCLOSURE MODULES
        // =====================================================================
        Schema::table('disclosure_modules', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->default(1)->after('is_required')
                ->comment('Approval tier: 1=Basic (public visibility), 2=Financials (buying), 3=Advanced (trust)');

            $table->index('tier', 'idx_disclosure_modules_tier');
        });

        // =====================================================================
        // ADD COMPANY LIFECYCLE STATE
        // =====================================================================
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('lifecycle_state', [
                'draft',                  // Not ready for public
                'live_limited',           // Tier 1 approved - profile visible, no buying
                'live_investable',        // Tier 2 approved - buying enabled
                'live_fully_disclosed',   // Tier 3 approved - trust badge
                'suspended',              // Admin freeze
            ])->default('draft')->after('disclosure_stage')
                ->comment('Company lifecycle state based on disclosure tier approvals');

            $table->timestamp('lifecycle_state_changed_at')->nullable()->after('lifecycle_state')
                ->comment('When lifecycle state last changed');

            $table->foreignId('lifecycle_state_changed_by')->nullable()->after('lifecycle_state_changed_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who triggered state change');

            $table->text('lifecycle_state_change_reason')->nullable()->after('lifecycle_state_changed_by')
                ->comment('Reason for state change (required for suspension)');

            // Suspension fields
            $table->timestamp('suspended_at')->nullable()->after('lifecycle_state_change_reason')
                ->comment('When company was suspended');

            $table->foreignId('suspended_by')->nullable()->after('suspended_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who suspended company');

            $table->text('suspension_reason')->nullable()->after('suspended_by')
                ->comment('Public reason shown to investors');

            $table->text('suspension_internal_notes')->nullable()->after('suspension_reason')
                ->comment('Admin-only suspension notes');

            $table->boolean('buying_enabled')->default(false)->after('suspension_internal_notes')
                ->comment('Whether investors can buy shares (controlled by lifecycle state)');

            $table->boolean('show_warning_banner')->default(false)->after('buying_enabled')
                ->comment('Show warning banner on company profile page');

            $table->text('warning_banner_message')->nullable()->after('show_warning_banner')
                ->comment('Custom warning message for investors');

            // Tier approval tracking
            $table->timestamp('tier_1_approved_at')->nullable()->after('warning_banner_message')
                ->comment('When all Tier 1 modules were approved');

            $table->timestamp('tier_2_approved_at')->nullable()->after('tier_1_approved_at')
                ->comment('When all Tier 2 modules were approved');

            $table->timestamp('tier_3_approved_at')->nullable()->after('tier_2_approved_at')
                ->comment('When all Tier 3 modules were approved');

            // Indexes for lifecycle queries
            $table->index('lifecycle_state', 'idx_companies_lifecycle_state');
            $table->index(['lifecycle_state', 'buying_enabled'], 'idx_companies_lifecycle_buying');
            $table->index('suspended_at', 'idx_companies_suspended');
        });

        // =====================================================================
        // ADD EDIT TRACKING TO COMPANY DISCLOSURES
        // =====================================================================
        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->json('edits_during_review')->nullable()->after('internal_notes')
                ->comment('Track all edits made during admin review for audit trail');

            $table->unsignedInteger('edit_count_during_review')->default(0)->after('edits_during_review')
                ->comment('How many times disclosure was edited while under review');

            $table->timestamp('last_edit_during_review_at')->nullable()->after('edit_count_during_review')
                ->comment('When disclosure was last edited during review');
        });

        // =====================================================================
        // ADD COMPANY LIFECYCLE AUDIT LOG TABLE
        // =====================================================================
        Schema::create('company_lifecycle_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company whose state changed');

            $table->string('from_state', 50)
                ->comment('Previous lifecycle state');

            $table->string('to_state', 50)
                ->comment('New lifecycle state');

            $table->string('trigger', 50)
                ->comment('What triggered change: tier_approval, admin_action, system');

            $table->foreignId('triggered_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who triggered change (admin or company)');

            $table->text('reason')->nullable()
                ->comment('Reason for state change');

            $table->json('metadata')->nullable()
                ->comment('Additional context: tier approved, modules approved, etc.');

            $table->string('ip_address', 45)->nullable()
                ->comment('IP address of trigger');

            $table->text('user_agent')->nullable()
                ->comment('User agent of trigger');

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'created_at'], 'idx_lifecycle_logs_company_timeline');
            $table->index('to_state', 'idx_lifecycle_logs_to_state');
            $table->index('trigger', 'idx_lifecycle_logs_trigger');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_lifecycle_logs');

        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->dropColumn([
                'edits_during_review',
                'edit_count_during_review',
                'last_edit_during_review_at',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_companies_lifecycle_state');
            $table->dropIndex('idx_companies_lifecycle_buying');
            $table->dropIndex('idx_companies_suspended');

            // Drop foreign keys
            $table->dropForeign(['lifecycle_state_changed_by']);
            $table->dropForeign(['suspended_by']);

            // Drop columns
            $table->dropColumn([
                'lifecycle_state',
                'lifecycle_state_changed_at',
                'lifecycle_state_changed_by',
                'lifecycle_state_change_reason',
                'suspended_at',
                'suspended_by',
                'suspension_reason',
                'suspension_internal_notes',
                'buying_enabled',
                'show_warning_banner',
                'warning_banner_message',
                'tier_1_approved_at',
                'tier_2_approved_at',
                'tier_3_approved_at',
            ]);
        });

        Schema::table('disclosure_modules', function (Blueprint $table) {
            $table->dropIndex('idx_disclosure_modules_tier');
            $table->dropColumn('tier');
        });
    }
};
