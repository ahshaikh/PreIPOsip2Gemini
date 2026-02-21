<?php
/**
 * V-CHARGEBACK-SEMANTICS-2026: Add reversal_source column to user_investments
 *
 * This column stores the explicit source of a reversal (refund, chargeback, etc.)
 * replacing string-based branching logic like str_contains($reason, 'Chargeback').
 *
 * VALID VALUES (from App\Enums\ReversalSource):
 * - 'refund' - Normal refund, user gets money back
 * - 'chargeback' - Bank-initiated, user owes chargeback amount
 * - 'admin_correction' - Admin action
 * - 'allocation_failure' - System failure compensation
 *
 * DDL-ONLY: This migration adds a nullable column with no data backfill.
 * Existing reversed investments will have NULL reversal_source.
 */

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
        Schema::table('user_investments', function (Blueprint $table) {
            // Add reversal_source column after reversal_reason for logical grouping
            // Nullable because existing reversed investments won't have this set
            $table->string('reversal_source', 32)
                ->nullable()
                ->after('reversal_reason')
                ->comment('Explicit reversal source: refund, chargeback, admin_correction, allocation_failure');

            // Add index for querying by reversal source (audit queries)
            $table->index('reversal_source', 'idx_user_investments_reversal_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropIndex('idx_user_investments_reversal_source');
            $table->dropColumn('reversal_source');
        });
    }
};
