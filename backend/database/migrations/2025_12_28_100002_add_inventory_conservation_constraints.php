<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PROTOCOL: Enforce Inventory Conservation at Database Level
     *
     * INVARIANT:
     *   bulk_purchases.value_remaining >= 0 (cannot go negative)
     *   bulk_purchases.value_remaining <= total_value_received (cannot exceed total)
     *
     * FAILURE SEMANTICS:
     * - Database rejects any update that would violate conservation
     * - Prevents race conditions at SQL level
     * - Ensures mathematical consistency even if application logic fails
     */
    public function up(): void
    {
        // Add CHECK constraint: value_remaining cannot be negative
        DB::statement("
            ALTER TABLE bulk_purchases
            ADD CONSTRAINT check_value_remaining_non_negative
            CHECK (value_remaining >= 0)
        ");

        // Add CHECK constraint: value_remaining cannot exceed total_value_received
        DB::statement("
            ALTER TABLE bulk_purchases
            ADD CONSTRAINT check_value_remaining_not_over_total
            CHECK (value_remaining <= total_value_received)
        ");

        // Add CHECK constraint: total_value_received is properly calculated
        DB::statement("
            ALTER TABLE bulk_purchases
            ADD CONSTRAINT check_total_value_calculation
            CHECK (
                ABS(
                    total_value_received -
                    (face_value_purchased * (1 + extra_allocation_percentage / 100))
                ) < 0.01
            )
        ");

        // Create index for efficient conservation queries
        Schema::table('bulk_purchases', function (Blueprint $table) {
            $table->index(['product_id', 'value_remaining'], 'idx_product_remaining');
        });

        // Create index for user investments conservation queries
        Schema::table('user_investments', function (Blueprint $table) {
            if (!Schema::hasColumn('user_investments', 'product_id')) {
                // This should already exist, but adding check for safety
                return;
            }
            $table->index(['product_id', 'is_reversed'], 'idx_product_active_allocations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_value_remaining_non_negative');
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_value_remaining_not_over_total');
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_total_value_calculation');

        Schema::table('bulk_purchases', function (Blueprint $table) {
            $table->dropIndex('idx_product_remaining');
        });

        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropIndex('idx_product_active_allocations');
        });
    }
};
