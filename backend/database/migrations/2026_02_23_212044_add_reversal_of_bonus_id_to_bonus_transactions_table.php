<?php
// V-WAVE3-REVERSAL-AUDIT: Add relational link for bonus reversals
// Replaces brittle description-based reversal detection with deterministic FK

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            // Self-referential FK: points to the original bonus being reversed
            // NULL for original bonuses, populated for reversal records
            $table->foreignId('reversal_of_bonus_id')
                ->nullable()
                ->after('payment_id')
                ->constrained('bonus_transactions')
                ->onDelete('set null');

            // Index for quick lookup of reversals
            $table->index('reversal_of_bonus_id');
        });
    }

    public function down(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_bonus_id']);
            $table->dropColumn('reversal_of_bonus_id');
        });
    }
};
