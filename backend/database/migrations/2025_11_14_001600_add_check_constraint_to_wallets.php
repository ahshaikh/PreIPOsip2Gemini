<?php
// V-FINAL-1730-352 (FIXED)

use Illuminate\Database\Migrations\Migration; // <-- CORRECTED
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This adds a database-level rule to prevent negative balances.
     */
    public function up(): void
    {
        // Add a CHECK constraint
        // Note: CHECK constraints are supported by MySQL 8.0.16+ and PostgreSQL
        try {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE wallets ADD CONSTRAINT balance_must_be_positive CHECK (balance >= 0)');
                DB::statement('ALTER TABLE wallets ADD CONSTRAINT locked_balance_must_be_positive CHECK (locked_balance >= 0)');
            }
        } catch (\Exception $e) {
            // Failsafe for older MySQL or SQLite versions
            logger('Could not add CHECK constraints to wallets table: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        // We must check if constraint exists before dropping (more robust)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('wallets', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('balance_must_be_positive');
                    $table->dropConstrainedForeignId('locked_balance_must_be_positive');
                } catch (\Exception $e) {
                    logger('Could not drop wallet constraints: ' . $e->getMessage());
                }
            });
        }
    }
};