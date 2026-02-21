<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * V-AUDIT-FIX-2026: Add reversal tracking columns for chargeback processing
     */
    public function up(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            if (!Schema::hasColumn('user_investments', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('is_reversed');
            }
            if (!Schema::hasColumn('user_investments', 'reversal_reason')) {
                $table->string('reversal_reason')->nullable()->after('reversed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            if (Schema::hasColumn('user_investments', 'reversed_at')) {
                $table->dropColumn('reversed_at');
            }
            if (Schema::hasColumn('user_investments', 'reversal_reason')) {
                $table->dropColumn('reversal_reason');
            }
        });
    }
};
