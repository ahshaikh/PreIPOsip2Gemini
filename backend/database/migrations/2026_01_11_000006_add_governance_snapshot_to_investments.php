<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 2 HARDENING - Issue 2: Investor Snapshot Governance State
 *
 * PROBLEM:
 * Investment snapshots from Phase 1 don't capture Phase 2 governance state
 * (lifecycle_state, buying_enabled, tier approvals, suspension status).
 *
 * SURGICAL FIX:
 * Add governance_snapshot column to investment_disclosure_snapshots.
 * Captures complete governance state at purchase time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {
            $table->json('governance_snapshot')->nullable()->after('valuation_context_snapshot')
                ->comment('Phase 2 governance state: {lifecycle_state, buying_enabled, governance_state_version, tier_approvals, suspension_status}');
        });
    }

    public function down(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {
            $table->dropColumn('governance_snapshot');
        });
    }
};

