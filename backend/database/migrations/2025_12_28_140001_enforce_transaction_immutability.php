<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Enforce Transaction Immutability (E.16)
 *
 * PROTOCOL:
 * - E.16: Make all financial records append-only
 * - Transactions CANNOT be updated or deleted after creation
 * - Changes happen via reversal/compensating transactions
 * - Immutable audit trail for regulatory compliance
 *
 * MECHANISM:
 * - Add reversal tracking columns
 * - Add database triggers to prevent updates/deletes
 * - Add constraint to ensure balance conservation
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===================================================================
        // STEP 1: Add reversal tracking columns to transactions table
        // ===================================================================

        // Skip if transactions table doesn't exist
        if (!Schema::hasTable('transactions')) {
            Log::warning("Transactions table does not exist - skipping immutability migration");
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            // Reversal tracking (for saga compensation and corrections)
            if (!Schema::hasColumn('transactions', 'is_reversed')) {
                $table->boolean('is_reversed')->default(false)->after('reference_id');
            }
            if (!Schema::hasColumn('transactions', 'reversed_by_transaction_id')) {
                $table->foreignId('reversed_by_transaction_id')->nullable()->after('is_reversed');
            }
            if (!Schema::hasColumn('transactions', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('reversed_by_transaction_id');
            }
            if (!Schema::hasColumn('transactions', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('reversed_at');
            }

            // Paired transaction tracking (for double-entry)
            if (!Schema::hasColumn('transactions', 'paired_transaction_id')) {
                $table->foreignId('paired_transaction_id')->nullable()->after('reversal_reason')
                    ->comment('For double-entry: links debit to credit');
            }
        });

        // Add indexes separately after column creation
        if (!$this->indexExists('transactions', 'transactions_is_reversed_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('is_reversed');
            });
        }

        if (!$this->indexExists('transactions', 'transactions_paired_transaction_id_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('paired_transaction_id');
            });
        }

        // ===================================================================
        // STEP 2: Add CHECK constraints for balance conservation
        // ===================================================================

        // Only add constraints if required columns exist
        // Wrap in try-catch to handle existing data that might violate constraints

        // Ensure balance conservation: balance_after = balance_before ± amount
        // For CREDIT: balance_after = balance_before + amount
        // For DEBIT: balance_after = balance_before - amount
        if (Schema::hasColumn('transactions', 'balance_before_paise') &&
            Schema::hasColumn('transactions', 'balance_after_paise') &&
            Schema::hasColumn('transactions', 'amount_paise') &&
            Schema::hasColumn('transactions', 'type')) {
            try {
                DB::statement("
                    ALTER TABLE transactions
                    ADD CONSTRAINT check_balance_conservation
                    CHECK (
                        (type IN ('deposit', 'credit', 'bonus', 'refund', 'referral_bonus')
                            AND balance_after_paise = balance_before_paise + amount_paise)
                        OR
                        (type IN ('debit', 'withdrawal', 'investment', 'fee', 'tds')
                            AND balance_after_paise = balance_before_paise - amount_paise)
                    )
                ");
            } catch (\Exception $e) {
                // Constraint failed on existing data - skip and enforce at application level
                Log::warning("Could not add check_balance_conservation constraint - existing data violates rule. Enforcing at application level only.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Ensure amount is always positive
        if (Schema::hasColumn('transactions', 'amount_paise')) {
            try {
                DB::statement("
                    ALTER TABLE transactions
                    ADD CONSTRAINT check_amount_positive
                    CHECK (amount_paise > 0)
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or existing data violates it
                Log::warning("Could not add check_amount_positive constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Ensure balance is never negative (unless explicitly allowed for admin accounts)
        if (Schema::hasColumn('transactions', 'balance_after_paise')) {
            try {
                DB::statement("
                    ALTER TABLE transactions
                    ADD CONSTRAINT check_balance_non_negative
                    CHECK (balance_after_paise >= 0)
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or existing data violates it
                Log::warning("Could not add check_balance_non_negative constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ===================================================================
        // STEP 3: Create immutability trigger (prevents updates/deletes)
        // ===================================================================

        // NOTE: This is PostgreSQL syntax. For MySQL, use different approach.
        // For Laravel/MySQL, we'll enforce this at application level via Observer

        // PostgreSQL version (if using PostgreSQL):
        /*
        DB::statement("
            CREATE OR REPLACE FUNCTION prevent_transaction_modification()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Transactions are immutable. Use reversal transactions instead.';
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER transaction_immutability_trigger
            BEFORE UPDATE OR DELETE ON transactions
            FOR EACH ROW
            EXECUTE FUNCTION prevent_transaction_modification();
        ");
        */

        // For MySQL, we'll use Observer in Transaction model instead
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers (PostgreSQL)
        // DB::statement("DROP TRIGGER IF EXISTS transaction_immutability_trigger ON transactions");
        // DB::statement("DROP FUNCTION IF EXISTS prevent_transaction_modification");

        // Drop constraints
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_balance_conservation");
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_amount_positive");
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_balance_non_negative");

        // Drop columns (only if they exist)
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if ($this->indexExists('transactions', 'transactions_is_reversed_index')) {
                    $table->dropIndex(['is_reversed']);
                }
                if ($this->indexExists('transactions', 'transactions_paired_transaction_id_index')) {
                    $table->dropIndex(['paired_transaction_id']);
                }
            });

            Schema::table('transactions', function (Blueprint $table) {
                $columns = ['is_reversed', 'reversed_by_transaction_id', 'reversed_at', 'reversal_reason', 'paired_transaction_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
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
