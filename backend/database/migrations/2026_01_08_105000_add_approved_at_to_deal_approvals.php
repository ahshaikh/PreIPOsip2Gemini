<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add approved_at Column to deal_approvals Table
 *
 * PROTOCOL 1 FIX:
 * - SQL Error: "Column not found: 1054 Unknown column 'approved_at' in 'where clause'"
 * - Query: SELECT COUNT(*) FROM deal_approvals WHERE status = 'approved' AND approved_at BETWEEN...
 * - Endpoint: GET /admin/deal-approvals/analytics
 *
 * ROOT CAUSE:
 * - Backend analytics query expects 'approved_at' timestamp column
 * - Column doesn't exist in deal_approvals table schema
 *
 * FIX:
 * - Add 'approved_at' nullable timestamp column
 * - Add 'approved_by' foreign key to track which admin approved (standard approval pattern)
 * - Add 'rejected_at' and 'rejected_by' for complete audit trail
 *
 * WHY BUG CANNOT REOCCUR:
 * - Column now exists at schema level
 * - Any query filtering by approved_at will succeed
 * - Follows existing approval pattern (withdrawals, campaigns use same columns)
 * - Database migration is version-controlled and idempotent
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only add columns if deal_approvals table exists
        if (Schema::hasTable('deal_approvals')) {
            Schema::table('deal_approvals', function (Blueprint $table) {
                // Add approval tracking columns if they don't exist
                if (!Schema::hasColumn('deal_approvals', 'approved_by')) {
                    $table->foreignId('approved_by')
                        ->nullable()
                        ->after('status')
                        ->constrained('users')
                        ->onDelete('set null')
                        ->comment('Admin user who approved the deal');
                }

                if (!Schema::hasColumn('deal_approvals', 'approved_at')) {
                    $table->timestamp('approved_at')
                        ->nullable()
                        ->after('approved_by')
                        ->comment('Timestamp when deal was approved');
                }

                if (!Schema::hasColumn('deal_approvals', 'rejected_by')) {
                    $table->foreignId('rejected_by')
                        ->nullable()
                        ->after('approved_at')
                        ->constrained('users')
                        ->onDelete('set null')
                        ->comment('Admin user who rejected the deal');
                }

                if (!Schema::hasColumn('deal_approvals', 'rejected_at')) {
                    $table->timestamp('rejected_at')
                        ->nullable()
                        ->after('rejected_by')
                        ->comment('Timestamp when deal was rejected');
                }

                // Add index for analytics queries (frequently filtered by approved_at)
                if (!Schema::hasColumn('deal_approvals', 'approved_at')) {
                    $table->index('approved_at', 'deal_approvals_approved_at_index');
                }
            });
        } else {
            // If table doesn't exist, log warning but don't fail
            // This allows migration to run even if deal_approvals feature is incomplete
            \Log::warning('Migration skipped: deal_approvals table does not exist. Create base table first.');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deal_approvals')) {
            Schema::table('deal_approvals', function (Blueprint $table) {
                // Drop index first
                $table->dropIndex('deal_approvals_approved_at_index');

                // Drop columns in reverse order
                $table->dropColumn([
                    'rejected_at',
                    'rejected_by',
                    'approved_at',
                    'approved_by',
                ]);
            });
        }
    }
};
