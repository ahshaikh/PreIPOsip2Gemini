<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

/**
 * FIX 10 (P2): Migrate All Monetary Fields to Integer Paise
 *
 * Adds integer paise fields alongside existing decimal fields for backward compatibility
 * Prevents floating-point precision errors in financial calculations
 *
 * Strategy:
 * 1. Add _paise columns (nullable for backward compatibility)
 * 2. Migrate existing data to _paise columns
 * 3. Application code gradually migrates to use _paise fields
 * 4. Future migration can drop old decimal columns once all code updated
 */
return new class extends Migration
{
    public function up(): void
    {
        // ========================================
        // PAYMENTS TABLE
        // ========================================
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'amount_paise')) {
                    $table->bigInteger('amount_paise')->nullable()->after('amount');
                    $table->index('amount_paise');
                }
            });

            // Migrate existing data
            DB::statement('UPDATE payments SET amount_paise = ROUND(amount * 100) WHERE amount_paise IS NULL');
        }

        // ========================================
        // WITHDRAWALS TABLE
        // ========================================
        // CANONICAL-PAISE: Withdrawals paise columns are now in canonical create_wallets_table
        // No action needed here - columns exist from canonical schema

        // ========================================
        // BULK_PURCHASES TABLE
        // ========================================
        if (Schema::hasTable('bulk_purchases')) {
            Schema::table('bulk_purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('bulk_purchases', 'face_value_purchased_paise')) {
                    $table->bigInteger('face_value_purchased_paise')->nullable()->after('face_value_purchased');
                }
                if (!Schema::hasColumn('bulk_purchases', 'actual_cost_paid_paise')) {
                    $table->bigInteger('actual_cost_paid_paise')->nullable()->after('actual_cost_paid');
                }
                if (!Schema::hasColumn('bulk_purchases', 'total_value_received_paise')) {
                    $table->bigInteger('total_value_received_paise')->nullable()->after('total_value_received');
                }
                if (!Schema::hasColumn('bulk_purchases', 'value_remaining_paise')) {
                    $table->bigInteger('value_remaining_paise')->nullable()->after('value_remaining');
                }

                $table->index('value_remaining_paise');
            });

            // Migrate existing data
            DB::statement('UPDATE bulk_purchases SET face_value_purchased_paise = ROUND(face_value_purchased * 100) WHERE face_value_purchased_paise IS NULL AND face_value_purchased IS NOT NULL');
            DB::statement('UPDATE bulk_purchases SET actual_cost_paid_paise = ROUND(actual_cost_paid * 100) WHERE actual_cost_paid_paise IS NULL AND actual_cost_paid IS NOT NULL');
            DB::statement('UPDATE bulk_purchases SET total_value_received_paise = ROUND(total_value_received * 100) WHERE total_value_received_paise IS NULL AND total_value_received IS NOT NULL');
            DB::statement('UPDATE bulk_purchases SET value_remaining_paise = ROUND(value_remaining * 100) WHERE value_remaining_paise IS NULL AND value_remaining IS NOT NULL');
        }

        // ========================================
        // USER_INVESTMENTS TABLE (if exists)
        // ========================================
        if (Schema::hasTable('user_investments')) {
            Schema::table('user_investments', function (Blueprint $table) {
                if (!Schema::hasColumn('user_investments', 'value_allocated_paise')) {
                    $table->bigInteger('value_allocated_paise')->nullable()->after('value_allocated');
                }

                $table->index('value_allocated_paise');
            });

            DB::statement('UPDATE user_investments SET value_allocated_paise = ROUND(value_allocated * 100) WHERE value_allocated_paise IS NULL AND value_allocated IS NOT NULL');
        }

        // ========================================
        // SUBSCRIPTIONS TABLE (if exists)
        // ========================================
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'amount_paise')) {
                    $table->bigInteger('amount_paise')->nullable()->after('amount');
                }

                $table->index('amount_paise');
            });

            DB::statement('UPDATE subscriptions SET amount_paise = ROUND(amount * 100) WHERE amount_paise IS NULL AND amount IS NOT NULL');
        }
    }

    public function down(): void
    {
        // Drop paise columns (data loss acceptable for rollback)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex(['amount_paise']);
                $table->dropColumn('amount_paise');
            });
        }

        // CANONICAL-PAISE: Withdrawals paise columns are managed by canonical create_wallets_table
        // Do not drop them here

        if (Schema::hasTable('bulk_purchases')) {
            Schema::table('bulk_purchases', function (Blueprint $table) {
                $table->dropIndex(['value_remaining_paise']);
                $table->dropColumn([
                    'face_value_purchased_paise',
                    'actual_cost_paid_paise',
                    'total_value_received_paise',
                    'value_remaining_paise',
                ]);
            });
        }

        if (Schema::hasTable('user_investments')) {
            Schema::table('user_investments', function (Blueprint $table) {
                $table->dropIndex(['value_allocated_paise']);
                $table->dropColumn('value_allocated_paise');
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndex(['amount_paise']);
                $table->dropColumn('amount_paise');
            });
        }
    }
};
