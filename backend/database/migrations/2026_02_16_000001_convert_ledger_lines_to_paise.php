<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FINANCIAL INTEGRITY HARDENING: Convert ledger_lines to BIGINT Paise
 *
 * RATIONALE:
 * - DECIMAL fields allow floating-point representation which can cause precision errors
 * - Integer paise storage guarantees exact arithmetic
 * - Aligns ledger_lines with wallets.balance_paise and transactions.amount_paise
 *
 * MIGRATION STRATEGY:
 * 1. Add new amount_paise (BIGINT) column
 * 2. Migrate existing data: amount_paise = amount * 100
 * 3. Drop legacy amount (DECIMAL) column
 *
 * ROLLBACK SAFE: Down() restores decimal column from paise
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if ledger_lines doesn't exist
        if (!Schema::hasTable('ledger_lines')) {
            Log::info('ledger_lines table does not exist - skipping migration');
            return;
        }

        // Skip if already migrated
        if (Schema::hasColumn('ledger_lines', 'amount_paise')) {
            Log::info('ledger_lines.amount_paise already exists - skipping migration');
            return;
        }

        // Step 1: Add amount_paise column (BIGINT)
        Schema::table('ledger_lines', function (Blueprint $table) {
            $table->bigInteger('amount_paise')->unsigned()->default(0)
                  ->after('direction')
                  ->comment('Amount in paise (integer) - 1 rupee = 100 paise');
        });

        // Step 2: Migrate data (DECIMAL rupees → INTEGER paise)
        // Using ROUND to handle any floating point artifacts
        DB::statement('UPDATE ledger_lines SET amount_paise = ROUND(amount * 100)');

        // Step 3: Verify migration integrity
        $mismatchCount = DB::table('ledger_lines')
            ->whereRaw('ABS(amount_paise - ROUND(amount * 100)) > 0')
            ->count();

        if ($mismatchCount > 0) {
            throw new \RuntimeException(
                "MIGRATION INTEGRITY FAILURE: {$mismatchCount} rows have amount_paise mismatch. " .
                "Aborting migration. Manual intervention required."
            );
        }

        // Step 4: Drop legacy decimal column
        Schema::table('ledger_lines', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        Log::info('ledger_lines successfully migrated to BIGINT paise storage');
    }

    public function down(): void
    {
        if (!Schema::hasTable('ledger_lines')) {
            return;
        }

        if (!Schema::hasColumn('ledger_lines', 'amount_paise')) {
            return;
        }

        // Restore decimal column
        Schema::table('ledger_lines', function (Blueprint $table) {
            $table->decimal('amount', 18, 2)->unsigned()->default(0)
                  ->after('direction')
                  ->comment('Amount in rupees');
        });

        // Migrate back: paise → rupees
        DB::statement('UPDATE ledger_lines SET amount = amount_paise / 100');

        // Drop paise column
        Schema::table('ledger_lines', function (Blueprint $table) {
            $table->dropColumn('amount_paise');
        });
    }
};
