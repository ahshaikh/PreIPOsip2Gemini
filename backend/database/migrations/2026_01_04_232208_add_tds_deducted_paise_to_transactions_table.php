<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'tds_deducted_paise')) {
                $table->bigInteger('tds_deducted_paise')
                      ->default(0)
                      ->after('amount_paise');
            }
        });

        /**
         * BACKFILL (if legacy tds_deducted exists in rupees)
         * Safe no-op if column does not exist.
         */
        if (Schema::hasColumn('transactions', 'tds_deducted')) {
            DB::statement("
                UPDATE transactions
                SET tds_deducted_paise = CAST(tds_deducted * 100 AS UNSIGNED)
            ");
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'tds_deducted_paise')) {
                $table->dropColumn('tds_deducted_paise');
            }
        });
    }
};
