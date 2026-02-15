<?php

/**
 * V-PAYMENT-INTEGRITY-2026: Payment Domain Hardening
 *
 * Adds columns required for:
 * 1. Integer paise storage (amount_paise)
 * 2. Settlement tracking (settled_at, settlement_id)
 * 3. Refund tracking (refund_amount_paise, refund_id)
 * 4. Currency enforcement
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // V-PRECISION-2026: Integer paise storage (authoritative)
            $table->bigInteger('amount_paise')->nullable()->after('amount');

            // Settlement tracking for reconciliation
            $table->timestamp('settled_at')->nullable()->after('paid_at');
            $table->string('settlement_id')->nullable()->after('settled_at')->index();
            $table->string('settlement_status')->default('pending')->after('settlement_id');

            // Refund tracking
            $table->bigInteger('refund_amount_paise')->default(0)->after('refunded_at');
            $table->string('refund_gateway_id')->nullable()->after('refund_amount_paise')->index();

            // Currency enforcement (must match expected)
            $table->string('expected_currency', 3)->default('INR')->after('currency');
        });

        // Migrate existing amount (rupees) to amount_paise
        // Skip for SQLite (test environment)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('UPDATE payments SET amount_paise = ROUND(amount * 100) WHERE amount_paise IS NULL');

            // After data migration, make amount_paise NOT NULL
            Schema::table('payments', function (Blueprint $table) {
                $table->bigInteger('amount_paise')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'amount_paise',
                'settled_at',
                'settlement_id',
                'settlement_status',
                'refund_amount_paise',
                'refund_gateway_id',
                'expected_currency',
            ]);
        });
    }
};
