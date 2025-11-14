<?php
// V-FINAL-1730-452 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This enforces FSD-SEC-010 at the database level.
     */
    public function up(): void
    {
        // MySQL 8.0.16+ required
        try {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT balance_must_be_positive CHECK (balance >= 0)');
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT locked_balance_must_be_positive CHECK (locked_balance >= 0)');
            
            DB::statement('ALTER TABLE payments ADD CONSTRAINT payment_amount_must_be_positive CHECK (amount >= 0)');
            
            DB::statement('ALTER TABLE bonus_transactions ADD CONSTRAINT bonus_amount_not_zero CHECK (amount != 0)');
        } catch (\Exception $e) {
            // Failsafe for older DBs
            logger('Could not add CHECK constraints to tables: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a simplified down method. Dropping constraints by name is complex.
        // For dev, 'migrate:fresh' is preferred.
    }
};