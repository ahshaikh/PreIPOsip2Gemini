<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // 1. Add new atomic integer columns
            if (!Schema::hasColumn('transactions', 'amount_paise')) {
                $table->bigInteger('amount_paise')->default(0)->after('type');
            }
            if (!Schema::hasColumn('transactions', 'balance_before_paise')) {
                $table->bigInteger('balance_before_paise')->default(0)->after('amount_paise');
            }
            if (!Schema::hasColumn('transactions', 'balance_after_paise')) {
                $table->bigInteger('balance_after_paise')->default(0)->after('balance_before_paise');
            }
        });

        // 2. Migrate existing data (Convert Float to Integer)
        // Check if old columns exist before trying to read them
        if (Schema::hasColumn('transactions', 'amount')) {
            DB::statement('UPDATE transactions SET amount_paise = CAST(amount * 100 AS UNSIGNED)');
        }
        if (Schema::hasColumn('transactions', 'balance_before')) {
            DB::statement('UPDATE transactions SET balance_before_paise = CAST(balance_before * 100 AS UNSIGNED)');
        }
        if (Schema::hasColumn('transactions', 'balance_after')) {
            DB::statement('UPDATE transactions SET balance_after_paise = CAST(balance_after * 100 AS UNSIGNED)');
        }

        // 3. Remove old float columns to enforce strict integer usage
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['amount', 'balance_before', 'balance_after']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
        });

        DB::statement('UPDATE transactions SET amount = amount_paise / 100');
        DB::statement('UPDATE transactions SET balance_before = balance_before_paise / 100');
        DB::statement('UPDATE transactions SET balance_after = balance_after_paise / 100');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['amount_paise', 'balance_before_paise', 'balance_after_paise']);
        });
    }
};