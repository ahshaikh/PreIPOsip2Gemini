<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add approved_at and review_started_at Columns to deal_approvals Table
 *
 * PROTOCOL 1 FIX:
 * - SQL Error 1: "Column not found: 1054 Unknown column 'approved_at' in 'where clause'"
 *   Query: SELECT COUNT(*) FROM deal_approvals WHERE status = 'approved' AND approved_at BETWEEN...
 * - SQL Error 2: "Column not found: 1054 Unknown column 'reviewed_by' in 'field list'"
 *   Query: SELECT reviewed_by, COUNT(*) FROM deal_approvals WHERE reviewed_by IS NOT NULL AND review_started_at BETWEEN...
 *   Endpoint: GET /admin/deal-approvals/analytics (line 71)
 *
 * ROOT CAUSE:
 * - Backend analytics query expects 'approved_at', 'reviewed_by', 'review_started_at' columns
 * - Columns don't exist in existing deal_approvals table schema
 *
 * FIX:
 * - Add 'approved_at' nullable timestamp column
 * - Add 'approved_by' foreign key to track which admin approved
 * - Add 'reviewed_by' foreign key to track which admin reviewed
 * - Add 'review_started_at' timestamp to track when review began (CRITICAL for analytics)
 * - Add 'rejected_at' and 'rejected_by' for complete audit trail
 *
 * WHY BUG CANNOT REOCCUR:
 * - All required columns now exist at schema level
 * - Analytics queries (approved_at BETWEEN, review_started_at BETWEEN) will succeed
 * - Follows existing approval pattern (withdrawals, campaigns use same columns)
 * - Migration is idempotent and version-controlled
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only add columns if deal_approvals table exists
        if (Schema::hasTable('deal_approvals')) {
            Schema::table('deal_approvals', function (Blueprint $table) {
                // PROTOCOL 1 FIX: Add review tracking columns
                // EXECUTION PATH: Analytics queries these columns for "who reviewed" stats
                if (!Schema::hasColumn('deal_approvals', 'reviewed_by')) {
                    $table->foreignId('reviewed_by')
                        ->nullable()
                        ->after('status')
                        ->constrained('users')
                        ->onDelete('set null')
                        ->comment('Admin user who started reviewing the deal');
                }

                // CRITICAL: review_started_at is queried in analytics
                // SQL: WHERE reviewed_by IS NOT NULL AND review_started_at BETWEEN...
                if (!Schema::hasColumn('deal_approvals', 'review_started_at')) {
                    $table->timestamp('review_started_at')
                        ->nullable()
                        ->after('reviewed_by')
                        ->comment('Timestamp when review was started');
                }

                // Add approval tracking columns if they don't exist
                if (!Schema::hasColumn('deal_approvals', 'approved_by')) {
                    $table->foreignId('approved_by')
                        ->nullable()
                        ->after('review_started_at')
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

                // Add indexes for analytics queries (frequently filtered columns)
                if (!Schema::hasColumn('deal_approvals', 'approved_at')) {
                    $table->index('approved_at', 'deal_approvals_approved_at_index');
                }

                if (!Schema::hasColumn('deal_approvals', 'review_started_at')) {
                    $table->index('review_started_at', 'deal_approvals_review_started_at_index');
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
                // Drop indexes first (must exist before dropping)
                if (Schema::hasColumn('deal_approvals', 'approved_at')) {
                    $table->dropIndex('deal_approvals_approved_at_index');
                }
                if (Schema::hasColumn('deal_approvals', 'review_started_at')) {
                    $table->dropIndex('deal_approvals_review_started_at_index');
                }

                // Drop columns in reverse order (last added -> first added)
                $table->dropColumn([
                    'rejected_at',
                    'rejected_by',
                    'approved_at',
                    'approved_by',
                    'review_started_at',
                    'reviewed_by',
                ]);
            });
        }
    }
};
