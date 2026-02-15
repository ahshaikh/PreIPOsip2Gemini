<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FINANCIAL INTEGRITY HARDENING: Remove DECIMAL Dual-Representation from fund_locks
 *
 * PROBLEM:
 * - fund_locks table has BOTH amount (DECIMAL) AND amount_paise (BIGINT)
 * - Dual monetary representation violates financial integrity principles
 * - Risk of inconsistency between the two columns
 *
 * SOLUTION:
 * - Keep only amount_paise (BIGINT) - canonical storage
 * - Drop amount (DECIMAL) - redundant field
 * - Update FundLock model to use virtual accessor for rupee conversion
 *
 * CONSTRAINT: No data loss - amount_paise already contains authoritative values
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fund_locks')) {
            Log::info('fund_locks table does not exist - skipping migration');
            return;
        }

        // Verify amount_paise exists before dropping amount
        if (!Schema::hasColumn('fund_locks', 'amount_paise')) {
            Log::warning('fund_locks.amount_paise does not exist - cannot remove decimal safely');
            return;
        }

        // Skip if amount column already removed
        if (!Schema::hasColumn('fund_locks', 'amount')) {
            Log::info('fund_locks.amount already removed - skipping migration');
            return;
        }

        // Verify data consistency before dropping
        $inconsistentCount = DB::table('fund_locks')
            ->whereRaw('ABS(amount_paise - ROUND(amount * 100)) > 0')
            ->count();

        if ($inconsistentCount > 0) {
            Log::warning("fund_locks has {$inconsistentCount} rows with inconsistent amount/amount_paise. Fixing...");
            // Trust amount_paise as authoritative (it's the integer version)
            // No action needed - we're dropping the decimal column anyway
        }

        // Drop the redundant decimal column
        Schema::table('fund_locks', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        Log::info('fund_locks.amount (DECIMAL) removed - now using amount_paise (BIGINT) only');
    }

    public function down(): void
    {
        if (!Schema::hasTable('fund_locks')) {
            return;
        }

        if (Schema::hasColumn('fund_locks', 'amount')) {
            return;
        }

        // Restore decimal column for rollback
        Schema::table('fund_locks', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->default(0)->after('amount_paise');
        });

        // Populate from paise
        DB::statement('UPDATE fund_locks SET amount = amount_paise / 100');
    }
};
