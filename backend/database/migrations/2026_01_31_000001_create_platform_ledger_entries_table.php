<?php

/**
 * EPIC 4 - GAP 4: Platform Ledger Linkage
 *
 * INVARIANT: Every BulkPurchase must have a corresponding platform ledger debit.
 * Inventory existence === proven platform capital movement.
 *
 * This table is APPEND-ONLY and IMMUTABLE:
 * - No updates allowed (use reversals instead)
 * - No deletes allowed (audit requirement)
 * - Entries are additive only
 *
 * COMPLIANCE: Designed for regulator/auditor review. Historical rewrites are impossible.
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
        Schema::create('platform_ledger_entries', function (Blueprint $table) {
            $table->id();

            // Entry type: 'debit' (capital out) or 'credit' (capital in/reversal)
            $table->enum('type', ['debit', 'credit']);

            // Amount in paise (smallest currency unit for precision)
            // Stored as integer to avoid floating point errors
            $table->bigInteger('amount_paise');

            // Running balance tracking (for audit verification)
            $table->bigInteger('balance_before_paise');
            $table->bigInteger('balance_after_paise');

            // Currency (ISO 4217 code)
            $table->string('currency', 3)->default('INR');

            // Source reference - what caused this entry
            // For BulkPurchase: source_type = 'bulk_purchase', source_id = bulk_purchase.id
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');

            // Human-readable description for audit trail
            $table->string('description', 500);

            // Reference to paired entry (for double-entry accounting)
            // A debit may have a paired credit (reversal)
            $table->unsignedBigInteger('entry_pair_id')->nullable();

            // Actor who initiated the entry
            $table->unsignedBigInteger('actor_id')->nullable();

            // Metadata for forensic analysis
            $table->json('metadata')->nullable();

            // Timestamp - immutable after creation
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['source_type', 'source_id'], 'idx_platform_ledger_source');
            $table->index(['type'], 'idx_platform_ledger_type');
            $table->index(['created_at'], 'idx_platform_ledger_created');
            $table->index(['entry_pair_id'], 'idx_platform_ledger_pair');

            // Foreign key for paired entry (self-referencing)
            $table->foreign('entry_pair_id')
                  ->references('id')
                  ->on('platform_ledger_entries')
                  ->onDelete('restrict');
        });

        // Add trigger/check constraint commentary
        // NOTE: MySQL doesn't support CHECK constraints with subqueries,
        // so immutability is enforced at application layer via model hooks
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_ledger_entries');
    }
};
