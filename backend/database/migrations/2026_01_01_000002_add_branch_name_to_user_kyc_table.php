<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V-FIX-BANK-BRANCH: Add branch_name column to user_kyc table
     *
     * Frontend was losing branch_name after save because backend wasn't storing it.
     * This migration adds the column so branch name persists properly.
     */
    public function up(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->string('bank_branch', 100)->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropColumn('bank_branch');
        });
    }
};
