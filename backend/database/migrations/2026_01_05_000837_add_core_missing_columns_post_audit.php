<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CORE ADDITIVE SCHEMA FIX
     * Post-Audit / Production-Safe
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | COMPANIES
        |--------------------------------------------------------------------------
        */
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'max_users_quota')) {
                $table->unsignedInteger('max_users_quota')
                      ->default(5)
                      ->after('status')
                      ->comment('Maximum number of company users allowed');
            }

            if (!Schema::hasColumn('companies', 'settings')) {
                $table->json('settings')
                      ->nullable()
                      ->after('max_users_quota')
                      ->comment('Company-specific configuration');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | TRANSACTIONS (ATOMIC LEDGER FIX)
        |--------------------------------------------------------------------------
        */
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'tds_deducted_paise')) {
                $table->bigInteger('tds_deducted_paise')
                      ->default(0)
                      ->after('amount_paise')
                      ->comment('TDS deducted in paise');
            }

            if (!Schema::hasColumn('transactions', 'is_reversed')) {
                $table->boolean('is_reversed')
                      ->default(false)
                      ->after('status')
                      ->comment('Ledger reversal flag (append-only invariant)');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | AUDIT LOGS (POSITION-AGNOSTIC FIX)
        |--------------------------------------------------------------------------
        */
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')
                    ->nullable()
                    ->comment('Admin responsible for audited action');
            }
        });
    }

    /**
     * IMPORTANT:
     * Down() intentionally NOT destructive.
     * Core audit policy = forward-only schema evolution.
     */
    public function down(): void
    {
        // No-op by design
    }
};
