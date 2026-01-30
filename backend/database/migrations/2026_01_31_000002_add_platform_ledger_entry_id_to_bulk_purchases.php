<?php

/**
 * EPIC 4 - GAP 1: Link BulkPurchase to Platform Ledger
 *
 * INVARIANT: Every BulkPurchase must have a corresponding platform ledger debit.
 * This column creates a direct FK link to enforce and audit this invariant.
 *
 * WHY: Without this link, verifying that every inventory item has proven capital
 * movement requires complex joins. With this FK, the invariant is provable
 * directly from the BulkPurchase record.
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
        Schema::table('bulk_purchases', function (Blueprint $table) {
            // Link to the platform ledger entry that proves capital movement
            $table->unsignedBigInteger('platform_ledger_entry_id')
                  ->nullable()
                  ->after('approved_by_admin_id');

            // Foreign key constraint
            $table->foreign('platform_ledger_entry_id')
                  ->references('id')
                  ->on('platform_ledger_entries')
                  ->onDelete('restrict'); // Cannot delete ledger entry if BulkPurchase references it

            // Index for lookups
            $table->index('platform_ledger_entry_id', 'idx_bp_platform_ledger');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulk_purchases', function (Blueprint $table) {
            $table->dropForeign(['platform_ledger_entry_id']);
            $table->dropIndex('idx_bp_platform_ledger');
            $table->dropColumn('platform_ledger_entry_id');
        });
    }
};
