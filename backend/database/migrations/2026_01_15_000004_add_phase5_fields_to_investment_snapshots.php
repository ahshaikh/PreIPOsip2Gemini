<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 5 - Issue 4: Investor Snapshotting
 *
 * PURPOSE:
 * Add Phase 5 fields to investment_disclosure_snapshots table.
 * Captures complete investor-facing view including public page, warnings, and acknowledgements.
 *
 * NEW FIELDS:
 * - governance_snapshot: Governance state at snapshot (from Phase 2)
 * - public_page_view_snapshot: What investor saw on public company page
 * - acknowledgements_snapshot: Status of all acknowledgements
 * - acknowledgements_granted: Specific acknowledgements investor granted
 *
 * IMPORTANT MIGRATION NOTE (READ THIS):
 * ------------------------------------------------------------
 * This migration is intentionally written in a DEFENSIVE manner.
 *
 * Reason:
 * - The table `investment_disclosure_snapshots` has already undergone
 *   multiple Phase 2 / Phase 5 evolutions.
 * - In at least one environment, `governance_snapshot` already exists,
 *   which caused a hard failure due to a duplicate column error.
 *
 * Strategy:
 * - Each column addition is guarded with Schema::hasColumn(...)
 * - This makes the migration:
 *     - Idempotent
 *     - Safe across dev / CI / staging / prod
 *     - Resistant to partial or out-of-order migration runs
 *
 * RULE APPLIED:
 * - From Phase 5 onward, ALL schema-altering migrations must be defensive.
 */
return new class extends Migration
{
    /**
     * Apply the migration.
     *
     * This method adds new JSON snapshot columns to
     * `investment_disclosure_snapshots`, but ONLY if they do not already exist.
     */
    public function up(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {

            /*
             * PHASE 2 HARDENING: Governance snapshot
             *
             * Stores governance-related state at the time of snapshot creation.
             * Example payload:
             * {
             *   lifecycle_state,
             *   buying_enabled,
             *   tier_1_approved,
             *   tier_2_approved,
             *   tier_3_approved,
             *   governance_state_version
             * }
             *
             * Guarded to avoid duplicate-column failures in environments
             * where this field was added earlier.
             */
            if (!Schema::hasColumn('investment_disclosure_snapshots', 'governance_snapshot')) {
                $table->json('governance_snapshot')
                    ->nullable()
                    ->after('valuation_context_snapshot')
                    ->comment(
                        'Governance state at snapshot time: {lifecycle_state, buying_enabled, tier approvals, etc.}'
                    );
            }

            /*
             * PHASE 5 - Issue 4: Public page view snapshot
             *
             * Captures the COMPLETE public-facing company page as seen by
             * the investor at the time of investment.
             *
             * This ensures:
             * - Disclosure immutability
             * - Auditability during disputes or regulatory review
             * - Protection against later page changes
             */
            if (!Schema::hasColumn('investment_disclosure_snapshots', 'public_page_view_snapshot')) {
                $table->json('public_page_view_snapshot')
                    ->nullable()
                    ->after('governance_snapshot')
                    ->comment(
                        'Complete public company page data investor saw: {disclosures, platform_context, warnings, etc.}'
                    );
            }

            /*
             * PHASE 5 - Issue 4: Acknowledgements snapshot
             *
             * Represents the system-calculated acknowledgement state at the
             * moment of investment.
             *
             * Example payload:
             * {
             *   all_acknowledged: true,
             *   missing: [],
             *   expired: [],
             *   valid: ["illiquidity", "no_guarantee"]
             * }
             *
             * This snapshot answers:
             * "Was the investor allowed to proceed at this moment?"
             */
            if (!Schema::hasColumn('investment_disclosure_snapshots', 'acknowledgements_snapshot')) {
                $table->json('acknowledgements_snapshot')
                    ->nullable()
                    ->after('public_page_view_snapshot')
                    ->comment(
                        'Status of all risk acknowledgements at snapshot time: {all_acknowledged, missing, expired, valid}'
                    );
            }

            /*
             * PHASE 5 - Issue 4: Acknowledgements granted
             *
             * Records ONLY the acknowledgements explicitly granted during
             * the investment flow that led to this snapshot.
             *
             * Example payload:
             * {
             *   illiquidity: 123,
             *   no_guarantee: 124
             * }
             *
             * Where values reference investor_risk_acknowledgements IDs.
             *
             * This provides:
             * - Fine-grained traceability
             * - Legal defensibility
             * - Clear linkage between consent and investment action
             */
            if (!Schema::hasColumn('investment_disclosure_snapshots', 'acknowledgements_granted')) {
                $table->json('acknowledgements_granted')
                    ->nullable()
                    ->after('acknowledgements_snapshot')
                    ->comment(
                        'Specific acknowledgements investor granted during investment flow: {acknowledgement_type: acknowledgement_id}'
                    );
            }
        });
    }

    /**
     * Reverse the migration.
     *
     * IMPORTANT:
     * - Column drops are also guarded.
     * - This prevents rollback failures in environments where
     *   only a subset of these columns exist.
     */
    public function down(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {

            /*
             * Collect only columns that actually exist before attempting
             * to drop them. This keeps rollbacks safe and predictable.
             */
            $columnsToDrop = [];

            foreach ([
                'governance_snapshot',
                'public_page_view_snapshot',
                'acknowledgements_snapshot',
                'acknowledgements_granted',
            ] as $column) {
                if (Schema::hasColumn('investment_disclosure_snapshots', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            /*
             * Drop columns only if at least one exists.
             * Avoids SQL errors in partially-applied environments.
             */
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
