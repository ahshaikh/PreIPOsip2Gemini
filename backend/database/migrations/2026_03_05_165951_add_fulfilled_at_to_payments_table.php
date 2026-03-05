<?php
// V-ORCHESTRATION-2026: Add fulfilled_at column for lifecycle idempotency

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * V-ORCHESTRATION-2026:
     * - fulfilled_at marks when payment lifecycle completed (wallet credited, bonuses awarded)
     * - Distinct from paid_at (payment captured) and settled_at (funds settled to merchant)
     * - Used for idempotency in FinancialOrchestrator::processSuccessfulPayment()
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('fulfilled_at')
                ->nullable()
                ->after('paid_at')
                ->comment('V-ORCHESTRATION-2026: When payment lifecycle completed (wallet credited, bonuses awarded)');

            $table->index('fulfilled_at', 'payments_fulfilled_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_fulfilled_at_index');
            $table->dropColumn('fulfilled_at');
        });
    }
};
