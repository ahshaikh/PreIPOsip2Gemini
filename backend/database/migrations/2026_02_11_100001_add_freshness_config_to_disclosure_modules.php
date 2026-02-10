<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FRESHNESS MODEL - MIGRATION 1/3
 *
 * Adds freshness configuration to disclosure_modules table.
 *
 * ENUM VOCABULARY (FROZEN - DO NOT ADD SYNONYMS):
 * - document_type: 'update_required' | 'version_controlled'
 *
 * DOCUMENT TYPES:
 * - update_required: Documents expected to change regularly (financials, cap table)
 *   - Staleness is detected by time since last update
 *   - Uses expected_update_days for cadence
 *
 * - version_controlled: Documents expected to be stable (articles, bylaws)
 *   - Instability is detected by excessive changes
 *   - Uses stability_window_days and max_changes_per_window
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disclosure_modules', function (Blueprint $table) {
            // Document classification for freshness behavior
            $table->enum('document_type', ['update_required', 'version_controlled'])
                ->nullable()
                ->after('tier')
                ->comment('Determines freshness calculation logic');

            // For update_required documents: expected update cadence in days
            $table->unsignedInteger('expected_update_days')
                ->nullable()
                ->after('document_type')
                ->comment('For update_required: days before document becomes stale');

            // For version_controlled documents: stability window in days
            $table->unsignedInteger('stability_window_days')
                ->nullable()
                ->after('expected_update_days')
                ->comment('For version_controlled: window for measuring change frequency');

            // For version_controlled documents: max changes before instability
            $table->unsignedInteger('max_changes_per_window')
                ->default(2)
                ->after('stability_window_days')
                ->comment('For version_controlled: changes above this = unstable');

            // Weight for pillar vitality calculation (0.00 - 1.00)
            $table->decimal('freshness_weight', 3, 2)
                ->default(1.00)
                ->after('max_changes_per_window')
                ->comment('Contribution weight to pillar vitality (1.00 = full weight)');
        });
    }

    public function down(): void
    {
        Schema::table('disclosure_modules', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'expected_update_days',
                'stability_window_days',
                'max_changes_per_window',
                'freshness_weight',
            ]);
        });
    }
};
