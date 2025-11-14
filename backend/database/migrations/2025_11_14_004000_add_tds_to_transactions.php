<?php
// V-FINAL-1730-407 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add TDS fields as per FSD-REPORT-017.
     */
    public function up(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            $table->decimal('tds_deducted', 10, 2)->default(0)->after('amount');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->decimal('tds_deducted', 10, 2)->default(0)->after('fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            $table->dropColumn('tds_deducted');
        });
        
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn('tds_deducted');
        });
    }
};