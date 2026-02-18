<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 5 - Issue 4: Investor Snapshotting (Deterministic Schema Version)
 *
 * PURPOSE:
 * Extend `investment_disclosure_snapshots` with additional Phase 5
 * investor-facing snapshot fields.
 *
 * IMPORTANT ARCHITECTURAL NOTE:
 * ------------------------------------------------------------
 * This migration has been rewritten to be DETERMINISTIC.
 *
 * Previous versions used Schema::hasColumn() guards to tolerate
 * partially-applied environments. That approach is not suitable
 * for canonical schema rebuilds (migrate:fresh, CI, test DB).
 *
 * From this point forward:
 * - Migrations must assume a clean, canonical schema.
 * - Each migration owns only the columns it introduces.
 * - No defensive conditional schema logic.
 *
 * Governance snapshot is handled in:
 *   2026_01_11_000006_add_governance_snapshot_to_investments
 *
 * This migration ONLY manages Phase 5 additions.
 *
 * This guarantees:
 * - Fresh rebuild safety
 * - Deterministic CI behavior
 * - Proper rollback ordering
 * - No schema drift masking
 */
return new class extends Migration
{
    /**
     * Apply the migration.
     *
     * Adds Phase 5 snapshot fields capturing:
     * - Public page view at investment time
     * - Acknowledgement state snapshot
     * - Specific acknowledgements granted
     */
    public function up(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {

            /*
             * PHASE 5 - Public Page View Snapshot
             *
             * Captures the COMPLETE public-facing company page as seen
             * by the investor at the moment of investment.
             *
             * Ensures:
             * - Disclosure immutability
             * - Regulatory defensibility
             * - Dispute protection
             */
            $table->json('public_page_view_snapshot')
                ->nullable()
                ->after('governance_snapshot')
                ->comment(
                    'Complete public company page data investor saw: {disclosures, platform_context, warnings, etc.}'
                );

            /*
             * PHASE 5 - Acknowledgements Snapshot
             *
             * System-calculated acknowledgement state at the moment
             * of snapshot creation.
             *
             * Example payload:
             * {
             *   all_acknowledged: true,
             *   missing: [],
             *   expired: [],
             *   valid: ["illiquidity", "no_guarantee"]
             * }
             */
            $table->json('acknowledgements_snapshot')
                ->nullable()
                ->after('public_page_view_snapshot')
                ->comment(
                    'Status of all risk acknowledgements at snapshot time'
                );

            /*
             * PHASE 5 - Acknowledgements Granted
             *
             * Records ONLY the acknowledgements explicitly granted
             * during the investment flow.
             *
             * Example payload:
             * {
             *   illiquidity: 123,
             *   no_guarantee: 124
             * }
             */
            $table->json('acknowledgements_granted')
                ->nullable()
                ->after('acknowledgements_snapshot')
                ->comment(
                    'Specific acknowledgements investor granted during investment flow'
                );
        });
    }

    /**
     * Reverse the migration.
     *
     * Drops ONLY the columns introduced in this migration.
     *
     * Governance snapshot rollback remains owned by:
     * 2026_01_11_000006_add_governance_snapshot_to_investments
     */
    public function down(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {

            $table->dropColumn([
                'public_page_view_snapshot',
                'acknowledgements_snapshot',
                'acknowledgements_granted',
            ]);
        });
    }
};
