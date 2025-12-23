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
        // 1. Add new integer columns
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'balance_paise')) {
                $table->bigInteger('balance_paise')->default(0)->after('user_id');
            }
            if (!Schema::hasColumn('wallets', 'locked_balance_paise')) {
                $table->bigInteger('locked_balance_paise')->default(0)->after('balance_paise');
            }
        });

        // 2. Migrate existing data (Convert Rupees to Paise)
        DB::statement('UPDATE wallets SET balance_paise = CAST(balance * 100 AS UNSIGNED)');
        DB::statement('UPDATE wallets SET locked_balance_paise = CAST(locked_balance * 100 AS UNSIGNED)');

        // 3. Drop old decimal columns
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['balance', 'locked_balance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('locked_balance', 15, 2)->default(0);
        });

        DB::statement('UPDATE wallets SET balance = balance_paise / 100');
        DB::statement('UPDATE wallets SET locked_balance = locked_balance_paise / 100');

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['balance_paise', 'locked_balance_paise']);
        });
    }
};