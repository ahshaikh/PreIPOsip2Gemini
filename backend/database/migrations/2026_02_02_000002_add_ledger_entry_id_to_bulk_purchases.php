<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 4.1: Add Double-Entry Ledger Reference to Bulk Purchases
 *
 * CONTEXT:
 * - bulk_purchases.platform_ledger_entry_id references the LEGACY platform_ledger_entries table
 * - New double-entry ledger uses ledger_entries table (Phase 4)
 * - This migration adds ledger_entry_id to reference the new double-entry system
 *
 * MIGRATION PATH:
 * 1. Add new ledger_entry_id column (nullable for existing records)
 * 2. New purchases will use double-entry ledger exclusively
 * 3. Legacy platform_ledger_entry_id is deprecated but preserved for audit trail
 *
 * POST-CONDITIONS:
 * - New bulk purchases link to ledger_entries via ledger_entry_id
 * - Historical bulk purchases retain platform_ledger_entry_id for audit
 * - platform_ledger_entry_id will be dropped in future migration after data verification
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bulk_purchases', function (Blueprint $table) {
            // Add reference to new double-entry ledger (idempotent)
            if (!Schema::hasColumn('bulk_purchases', 'ledger_entry_id')) {
                $table->unsignedBigInteger('ledger_entry_id')
                    ->nullable()
                    ->after('platform_ledger_entry_id')
                    ->comment('Reference to double-entry ledger_entries table (Phase 4.1)');

                // Add foreign key to ledger_entries table
                $table->foreign('ledger_entry_id')
                    ->references('id')
                    ->on('ledger_entries')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulk_purchases', function (Blueprint $table) {
            $table->dropForeign(['ledger_entry_id']);
            $table->dropColumn('ledger_entry_id');
        });
    }
};
