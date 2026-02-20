<?php
// V-FINAL-1730-565 (Created) | V-FINAL-1730-569 (Defensive Fix)
// V-CANONICAL-PAISE-2026: Updated for paise-canonical schema

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-REPORT-017: Add TDS fields
     *
     * NOTE: Withdrawals.tds_deducted_paise is now in canonical schema.
     * This migration only handles bonus_transactions for legacy compatibility.
     */
    public function up(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            // --- FIX: Check before adding ---
            if (!Schema::hasColumn('bonus_transactions', 'tds_deducted')) {
                $table->decimal('tds_deducted', 10, 2)->default(0)->after('amount');
            }
        });

        // CANONICAL-PAISE: Withdrawals.tds_deducted_paise is now in canonical create_wallets_table
        // No action needed here - the column exists from canonical schema
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bonus_transactions', 'tds_deducted')) {
                $table->dropColumn('tds_deducted');
            }
        });

        // CANONICAL-PAISE: Do not drop withdrawals.tds_deducted_paise here
        // It's managed by the canonical migration
    }
};