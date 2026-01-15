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
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {
            // PHASE 2 HARDENING: Governance snapshot
            $table->json('governance_snapshot')->nullable()->after('valuation_context_snapshot')
                ->comment('Governance state at snapshot time: {lifecycle_state, buying_enabled, tier approvals, etc.}');

            // PHASE 5 - Issue 4: Public page view snapshot
            $table->json('public_page_view_snapshot')->nullable()->after('governance_snapshot')
                ->comment('Complete public company page data investor saw: {disclosures, platform_context, warnings, etc.}');

            // PHASE 5 - Issue 4: Acknowledgements snapshot
            $table->json('acknowledgements_snapshot')->nullable()->after('public_page_view_snapshot')
                ->comment('Status of all risk acknowledgements at snapshot time: {all_acknowledged, missing, expired, valid}');

            // PHASE 5 - Issue 4: Acknowledgements granted
            $table->json('acknowledgements_granted')->nullable()->after('acknowledgements_snapshot')
                ->comment('Specific acknowledgements investor granted during investment flow: {acknowledgement_type: acknowledgement_id}');
        });
    }

    public function down(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'governance_snapshot',
                'public_page_view_snapshot',
                'acknowledgements_snapshot',
                'acknowledgements_granted',
            ]);
        });
    }
};
