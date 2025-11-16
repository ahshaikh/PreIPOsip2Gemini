<?php
// V-FINAL-1730-565 (Created) | V-FINAL-1730-569 (Defensive Fix)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-REPORT-017: Add TDS fields
     */
    public function up(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            // --- FIX: Check before adding ---
            if (!Schema::hasColumn('bonus_transactions', 'tds_deducted')) {
                $table->decimal('tds_deducted', 10, 2)->default(0)->after('amount');
            }
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            // --- FIX: Check before adding ---
            if (!Schema::hasColumn('withdrawals', 'tds_deducted')) {
                $table->decimal('tds_deducted', 10, 2)->default(0)->after('fee');
            }
        });
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
        
        Schema::table('withdrawals', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawals', 'tds_deducted')) {
                $table->dropColumn('tds_deducted');
            }
        });
    }
};