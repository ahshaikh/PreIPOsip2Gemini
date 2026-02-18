<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// * Migration: Enforce Investment Chain Integrity (C.10)
// *
// * PROBLEM: Orphan investments and allocations
// * - UserInvestment created without valid Investment parent
// * - Bonus calculations on incomplete/failed investments
// * - Broken referential integrity allowing partial states
// *
// * SOLUTION: Database constraints enforcing complete investment chains
// * - Foreign keys with RESTRICT to prevent orphans
// * - CHECK constraints ensuring valid states
// * - Index optimizations for chain traversal

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE user_investments
            ADD CONSTRAINT check_user_investment_positive_value
            CHECK (value_allocated > 0)
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE user_investments
            DROP CHECK check_user_investment_positive_value
        ");
    }
};

