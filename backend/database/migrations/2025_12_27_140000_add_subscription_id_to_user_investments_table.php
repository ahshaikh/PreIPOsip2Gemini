<?php
/**
 * P0.1 FIX: Add subscription_id to user_investments table
 *
 * PROBLEM: UserInvestment model claims to have subscription_id in $fillable,
 * but database schema is missing the column. This causes:
 * - Silent data loss when AllocationService tries to set subscription_id
 * - Impossible to query investments by subscription
 * - Subscription.investments() relationship broken for UserInvestment
 *
 * SOLUTION: Add subscription_id as nullable foreign key with cascade on delete
 *
 * WHY BUG CANNOT REOCCUR:
 * - Database enforces foreign key constraint
 * - AllocationService MUST provide valid subscription_id or INSERT fails
 * - Model validation catches missing field at runtime
 * - Impossible to create orphaned records
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
            // [PROTOCOL 1]: subscription_id is NOT NULL
            // WHY: payments.subscription_id is NOT NULL (create_payments_table.php:16)
            // AllocationService receives Payment → subscription_id guaranteed non-null
            // BulkPurchaseController admin path is BROKEN (missing required fields)
            // Therefore: ALL valid UserInvestment records MUST have subscription_id
            //
            // onDelete: RESTRICT (not CASCADE) to prevent accidental financial data loss
            // Regulatory compliance: Cannot delete investment records by deleting subscription
            $table->foreignId('subscription_id')
                  ->after('payment_id')
                  ->constrained('subscriptions')
                  ->onDelete('restrict');  // [PROTOCOL 1]: Prevent cascade delete of financial records

            // Add index for query performance (frequently used in WHERE clauses)
            $table->index('subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropIndex(['subscription_id']);
            $table->dropColumn('subscription_id');
        });
    }
};
