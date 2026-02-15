<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * FINANCIAL INTEGRITY HARDENING: Admin Adjustment Idempotency
 *
 * PROBLEM:
 * - Admin wallet adjustments can be accidentally applied twice
 * - No database-level enforcement of uniqueness
 * - Manual operations are high-risk for duplicate execution
 *
 * SOLUTION:
 * - Add idempotency_key to admin_action_audit table
 * - Unique constraint prevents duplicate adjustments
 * - Service-level check + DB constraint = defense in depth
 *
 * USAGE:
 * - Every admin adjustment generates a unique idempotency key
 * - Format: "adj:{admin_id}:{wallet_id}:{amount_paise}:{timestamp_minute}"
 * - Prevents identical adjustments within same minute window
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add to admin_action_audit if exists
        if (Schema::hasTable('admin_action_audit')) {
            if (!Schema::hasColumn('admin_action_audit', 'idempotency_key')) {
                Schema::table('admin_action_audit', function (Blueprint $table) {
                    $table->string('idempotency_key', 255)->nullable()->unique()
                          ->after('action_type')
                          ->comment('Unique key to prevent duplicate admin actions');
                });

                Log::info('Added idempotency_key to admin_action_audit table');
            }
        }

        // Add idempotency tracking to transactions for admin adjustments
        if (Schema::hasTable('transactions')) {
            if (!Schema::hasColumn('transactions', 'idempotency_key')) {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->string('idempotency_key', 255)->nullable()
                          ->after('reference_id')
                          ->comment('Idempotency key for admin adjustments and manual operations');

                    $table->index('idempotency_key', 'idx_transactions_idempotency');
                });

                Log::info('Added idempotency_key to transactions table');
            }

            // Add unique constraint only for admin_adjustment type
            // This allows same key for different transaction types
            if (!$this->constraintExists('transactions', 'uq_transactions_admin_idempotency')) {
                try {
                    // Create partial unique index for admin adjustments
                    // MySQL 8.0+ supports functional indexes
                    \DB::statement("
                        CREATE UNIQUE INDEX uq_transactions_admin_idempotency
                        ON transactions (idempotency_key)
                        WHERE idempotency_key IS NOT NULL AND type = 'admin_adjustment'
                    ");
                } catch (\Exception $e) {
                    // MySQL < 8.0.13 doesn't support partial indexes
                    // Fall back to application-level enforcement only
                    Log::warning('Could not create partial unique index - enforcing at application level', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_action_audit')) {
            Schema::table('admin_action_audit', function (Blueprint $table) {
                if (Schema::hasColumn('admin_action_audit', 'idempotency_key')) {
                    $table->dropColumn('idempotency_key');
                }
            });
        }

        if (Schema::hasTable('transactions')) {
            // Drop index first
            try {
                \DB::statement('DROP INDEX uq_transactions_admin_idempotency ON transactions');
            } catch (\Exception $e) {
                // Index might not exist
            }

            Schema::table('transactions', function (Blueprint $table) {
                if ($this->indexExists('transactions', 'idx_transactions_idempotency')) {
                    $table->dropIndex('idx_transactions_idempotency');
                }
                if (Schema::hasColumn('transactions', 'idempotency_key')) {
                    $table->dropColumn('idempotency_key');
                }
            });
        }
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        try {
            $result = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$constraint}'");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $result = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
};
