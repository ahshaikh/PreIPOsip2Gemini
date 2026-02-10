<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FRESHNESS MODEL - MIGRATION 2/3
 *
 * Adds freshness tracking fields to company_disclosures table.
 *
 * ENUM VOCABULARY (FROZEN - DO NOT ADD SYNONYMS):
 * - freshness_state: 'current' | 'aging' | 'stale' | 'unstable'
 *
 * STATE MEANINGS:
 * - current: Document is within expected update window / stable
 * - aging: Document is approaching staleness threshold (warning zone)
 * - stale: Document has exceeded expected update window (action required)
 * - unstable: Document has excessive changes in stability window (version_controlled only)
 *
 * FRESHNESS OVERRIDE CONSTRAINT:
 * The freshness_override field is AUDIT-ONLY:
 * - Overrides must NEVER improve a state silently
 * - Overrides must ALWAYS be visible in admin evidence views
 * - Overrides must NOT propagate to subscriber UI as "improved freshness"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_disclosures', function (Blueprint $table) {
            // Computed freshness state (refreshed by scheduler)
            $table->enum('freshness_state', ['current', 'aging', 'stale', 'unstable'])
                ->nullable()
                ->after('is_locked')
                ->comment('Backend-computed freshness state');

            // When freshness was last computed
            $table->timestamp('freshness_computed_at')
                ->nullable()
                ->after('freshness_state')
                ->comment('Timestamp of last freshness computation');

            // Cached days since approval (for quick queries)
            $table->unsignedInteger('days_since_approval')
                ->nullable()
                ->after('freshness_computed_at')
                ->comment('Cached days since approval for freshness calc');

            // Change count within stability window (for version_controlled instability)
            $table->unsignedInteger('update_count_in_window')
                ->default(0)
                ->after('days_since_approval')
                ->comment('Approval count within stability window');

            // Next expected update date (for update_required documents)
            $table->date('next_update_expected')
                ->nullable()
                ->after('update_count_in_window')
                ->comment('When next update is expected (update_required only)');

            // Admin override with audit trail
            // CONSTRAINT: This is AUDIT-ONLY - see migration docblock
            $table->json('freshness_override')
                ->nullable()
                ->after('next_update_expected')
                ->comment('AUDIT-ONLY: Admin override with reason, expiry, visibility flag');
        });

        // Index for scheduler queries
        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->index(['freshness_state', 'freshness_computed_at'], 'idx_freshness_refresh');
            $table->index(['status', 'freshness_state'], 'idx_status_freshness');
        });
    }

    public function down(): void
    {
        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->dropIndex('idx_freshness_refresh');
            $table->dropIndex('idx_status_freshness');

            $table->dropColumn([
                'freshness_state',
                'freshness_computed_at',
                'days_since_approval',
                'update_count_in_window',
                'next_update_expected',
                'freshness_override',
            ]);
        });
    }
};
