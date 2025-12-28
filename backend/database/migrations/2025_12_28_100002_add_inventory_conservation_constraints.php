<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Skip if bulk_purchases table doesn't exist
        if (!Schema::hasTable('bulk_purchases')) {
            Log::warning("bulk_purchases table does not exist - skipping inventory conservation constraints");
            return;
        }

        // Add CHECK constraint: value_remaining cannot be negative
        if (Schema::hasColumn('bulk_purchases', 'value_remaining')) {
            try {
                DB::statement("
                    ALTER TABLE bulk_purchases
                    ADD CONSTRAINT check_value_remaining_non_negative
                    CHECK (value_remaining >= 0)
                ");
            } catch (\Exception $e) {
                Log::warning("Could not add check_value_remaining_non_negative constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Add CHECK constraint: value_remaining cannot exceed total_value_received
        if (Schema::hasColumn('bulk_purchases', 'value_remaining') &&
            Schema::hasColumn('bulk_purchases', 'total_value_received')) {
            try {
                DB::statement("
                    ALTER TABLE bulk_purchases
                    ADD CONSTRAINT check_value_remaining_not_over_total
                    CHECK (value_remaining <= total_value_received)
                ");
            } catch (\Exception $e) {
                Log::warning("Could not add check_value_remaining_not_over_total constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Add CHECK constraint: total_value_received is properly calculated
        if (Schema::hasColumn('bulk_purchases', 'total_value_received') &&
            Schema::hasColumn('bulk_purchases', 'face_value_purchased') &&
            Schema::hasColumn('bulk_purchases', 'extra_allocation_percentage')) {
            try {
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
            } catch (\Exception $e) {
                Log::warning("Could not add check_total_value_calculation constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Create index for efficient conservation queries
        if (Schema::hasColumn('bulk_purchases', 'product_id') &&
            Schema::hasColumn('bulk_purchases', 'value_remaining')) {
            if (!$this->indexExists('bulk_purchases', 'idx_product_remaining')) {
                Schema::table('bulk_purchases', function (Blueprint $table) {
                    $table->index(['product_id', 'value_remaining'], 'idx_product_remaining');
                });
            }
        }

        // Create index for user investments conservation queries
        if (Schema::hasTable('user_investments')) {
            if (Schema::hasColumn('user_investments', 'product_id') &&
                Schema::hasColumn('user_investments', 'is_reversed')) {
                if (!$this->indexExists('user_investments', 'idx_product_active_allocations')) {
                    Schema::table('user_investments', function (Blueprint $table) {
                        $table->index(['product_id', 'is_reversed'], 'idx_product_active_allocations');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_value_remaining_non_negative');
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_value_remaining_not_over_total');
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_total_value_calculation');

        if (Schema::hasTable('bulk_purchases')) {
            if ($this->indexExists('bulk_purchases', 'idx_product_remaining')) {
                Schema::table('bulk_purchases', function (Blueprint $table) {
                    $table->dropIndex('idx_product_remaining');
                });
            }
        }

        if (Schema::hasTable('user_investments')) {
            if ($this->indexExists('user_investments', 'idx_product_active_allocations')) {
                Schema::table('user_investments', function (Blueprint $table) {
                    $table->dropIndex('idx_product_active_allocations');
                });
            }
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
        return !empty($indexes);
    }
};
