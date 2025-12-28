<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Enforce Investment Chain Integrity (C.10)
 *
 * PROBLEM: Orphan investments and allocations
 * - UserInvestment created without valid Investment parent
 * - Bonus calculations on incomplete/failed investments
 * - Broken referential integrity allowing partial states
 *
 * SOLUTION: Database constraints enforcing complete investment chains
 * - Foreign keys with RESTRICT to prevent orphans
 * - CHECK constraints ensuring valid states
 * - Index optimizations for chain traversal
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===================================================================
        // PART 1: USER_INVESTMENTS TABLE - Enforce valid parent references
        // ===================================================================

        // Check if foreign key already exists before adding
        $existingInvestmentFk = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_investments'
              AND COLUMN_NAME = 'investment_id'
              AND REFERENCED_TABLE_NAME = 'investments'
        ");

        if (empty($existingInvestmentFk)) {
            // Add foreign key constraint: user_investments.investment_id → investments.id
            // RESTRICT: Cannot delete Investment if UserInvestments exist
            Schema::table('user_investments', function (Blueprint $table) {
                $table->foreign('investment_id')
                    ->references('id')
                    ->on('investments')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        // Add CHECK constraint: Cannot have allocation without investment
        DB::statement("
            ALTER TABLE user_investments
            ADD CONSTRAINT check_user_investment_has_parent
            CHECK (investment_id IS NOT NULL)
        ");

        // Add CHECK constraint: Allocated value must be positive
        DB::statement("
            ALTER TABLE user_investments
            ADD CONSTRAINT check_user_investment_positive_value
            CHECK (value_allocated > 0)
        ");

        // Add CHECK constraint: Reversed investments cannot be in 'active' status
        DB::statement("
            ALTER TABLE user_investments
            ADD CONSTRAINT check_reversed_not_active
            CHECK (
                (is_reversed = FALSE AND status = 'active')
                OR (is_reversed = TRUE AND status != 'active')
            )
        ");

        // ===================================================================
        // PART 2: INVESTMENTS TABLE - Enforce valid states
        // ===================================================================

        // Add CHECK constraint: Final amount cannot exceed total amount
        DB::statement("
            ALTER TABLE investments
            ADD CONSTRAINT check_final_not_exceed_total
            CHECK (final_amount <= total_amount)
        ");

        // Add CHECK constraint: Completed investments must have allocation
        // This will be enforced at application level, not DB (requires join)

        // ===================================================================
        // PART 3: BONUS_DISTRIBUTIONS - Cannot distribute on orphan investments
        // ===================================================================

        // Ensure bonus distributions reference valid investments
        $existingBonusFk = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'bonus_distributions'
              AND COLUMN_NAME = 'investment_id'
              AND REFERENCED_TABLE_NAME = 'investments'
        ");

        if (empty($existingBonusFk) && Schema::hasTable('bonus_distributions')) {
            Schema::table('bonus_distributions', function (Blueprint $table) {
                $table->foreign('investment_id')
                    ->references('id')
                    ->on('investments')
                    ->onDelete('restrict');
            });
        }

        // ===================================================================
        // PART 4: INDEXES - Optimize chain traversal queries
        // ===================================================================

        Schema::table('user_investments', function (Blueprint $table) {
            // Index for finding allocations by investment
            if (!$this->indexExists('user_investments', 'idx_user_investments_investment')) {
                $table->index('investment_id', 'idx_user_investments_investment');
            }

            // Index for finding non-reversed allocations (used in conservation checks)
            if (!$this->indexExists('user_investments', 'idx_user_investments_active')) {
                $table->index(['investment_id', 'is_reversed'], 'idx_user_investments_active');
            }
        });

        Schema::table('investments', function (Blueprint $table) {
            // Index for finding investments by user and status
            if (!$this->indexExists('investments', 'idx_investments_user_status')) {
                $table->index(['user_id', 'status'], 'idx_investments_user_status');
            }

            // Index for finding completed investments (used in compliance checks)
            if (!$this->indexExists('investments', 'idx_investments_status')) {
                $table->index('status', 'idx_investments_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropIndex('idx_user_investments_investment');
            $table->dropIndex('idx_user_investments_active');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropIndex('idx_investments_user_status');
            $table->dropIndex('idx_investments_status');
        });

        // Drop CHECK constraints
        DB::statement("ALTER TABLE user_investments DROP CONSTRAINT IF EXISTS check_user_investment_has_parent");
        DB::statement("ALTER TABLE user_investments DROP CONSTRAINT IF EXISTS check_user_investment_positive_value");
        DB::statement("ALTER TABLE user_investments DROP CONSTRAINT IF EXISTS check_reversed_not_active");
        DB::statement("ALTER TABLE investments DROP CONSTRAINT IF EXISTS check_final_not_exceed_total");

        // Drop foreign keys
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropForeign(['investment_id']);
        });

        if (Schema::hasTable('bonus_distributions')) {
            Schema::table('bonus_distributions', function (Blueprint $table) {
                $table->dropForeign(['investment_id']);
            });
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
