<?php
// V-WAVE3-REVERSAL-2026: Add recovery mode flag to wallets
// When true, account is in financial recovery mode:
// - Withdrawals are blocked
// - Share transfers are blocked
// - Bonus accrual is blocked
// - Only deposits are allowed

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->boolean('is_recovery_mode')->default(false)->after('locked_balance_paise');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('is_recovery_mode');
        });
    }
};
