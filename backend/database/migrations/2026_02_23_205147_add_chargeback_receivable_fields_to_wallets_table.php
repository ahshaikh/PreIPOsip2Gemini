<?php
// V-WAVE3-REVERSAL-HARDENING: Add receivable tracking fields to wallets
// Enables automated recovery mode exit when receivable is settled

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Total outstanding receivable (what user owes us) in paise
            $table->bigInteger('receivable_balance_paise')->default(0)->after('is_recovery_mode');

            // Timestamp when last receivable was created
            $table->timestamp('receivable_created_at')->nullable()->after('receivable_balance_paise');

            // Timestamp when receivable was fully settled (for audit)
            $table->timestamp('receivable_settled_at')->nullable()->after('receivable_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['receivable_balance_paise', 'receivable_created_at', 'receivable_settled_at']);
        });
    }
};
