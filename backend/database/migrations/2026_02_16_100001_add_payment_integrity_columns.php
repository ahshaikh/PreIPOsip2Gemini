<?php

/**
 * V-PAYMENT-INTEGRITY-2026: Payment Domain Hardening
 *
 * Adds columns required for:
 * 1. Settlement tracking (settled_at, settlement_id)
 * 2. Refund tracking (refund_amount_paise, refund_id)
 * 3. Currency enforcement
 *
 * NOTE: amount_paise already exists from migration 2026_01_07_100003
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Check which columns need to be added BEFORE the Schema::table call
        $hasSettledAt = Schema::hasColumn('payments', 'settled_at');
        $hasSettlementId = Schema::hasColumn('payments', 'settlement_id');
        $hasSettlementStatus = Schema::hasColumn('payments', 'settlement_status');
        $hasRefundAmountPaise = Schema::hasColumn('payments', 'refund_amount_paise');
        $hasRefundGatewayId = Schema::hasColumn('payments', 'refund_gateway_id');
        $hasExpectedCurrency = Schema::hasColumn('payments', 'expected_currency');

        Schema::table('payments', function (Blueprint $table) use (
            $hasSettledAt,
            $hasSettlementId,
            $hasSettlementStatus,
            $hasRefundAmountPaise,
            $hasRefundGatewayId,
            $hasExpectedCurrency
        ) {
            // Settlement tracking for reconciliation
            if (!$hasSettledAt) {
                $table->timestamp('settled_at')->nullable()->after('paid_at');
            }
            if (!$hasSettlementId) {
                $table->string('settlement_id')->nullable()->after('settled_at')->index();
            }
            if (!$hasSettlementStatus) {
                $table->string('settlement_status')->default('pending')->after('settlement_id');
            }

            // Refund tracking
            if (!$hasRefundAmountPaise) {
                $table->bigInteger('refund_amount_paise')->default(0)->after('refunded_at');
            }
            if (!$hasRefundGatewayId) {
                $table->string('refund_gateway_id')->nullable()->after('refund_amount_paise')->index();
            }

            // Currency enforcement (must match expected)
            if (!$hasExpectedCurrency) {
                $table->string('expected_currency', 3)->default('INR')->after('currency');
            }
        });

        Log::info('V-PAYMENT-INTEGRITY-2026: Payment integrity columns added');
    }

    public function down(): void
    {
        $hasSettledAt = Schema::hasColumn('payments', 'settled_at');
        $hasSettlementId = Schema::hasColumn('payments', 'settlement_id');
        $hasSettlementStatus = Schema::hasColumn('payments', 'settlement_status');
        $hasRefundAmountPaise = Schema::hasColumn('payments', 'refund_amount_paise');
        $hasRefundGatewayId = Schema::hasColumn('payments', 'refund_gateway_id');
        $hasExpectedCurrency = Schema::hasColumn('payments', 'expected_currency');

        Schema::table('payments', function (Blueprint $table) use (
            $hasSettledAt,
            $hasSettlementId,
            $hasSettlementStatus,
            $hasRefundAmountPaise,
            $hasRefundGatewayId,
            $hasExpectedCurrency
        ) {
            // Drop indexes first
            if ($hasSettlementId) {
                $table->dropIndex(['settlement_id']);
            }
            if ($hasRefundGatewayId) {
                $table->dropIndex(['refund_gateway_id']);
            }

            // Collect columns to drop
            $columnsToDrop = [];
            if ($hasSettledAt) $columnsToDrop[] = 'settled_at';
            if ($hasSettlementId) $columnsToDrop[] = 'settlement_id';
            if ($hasSettlementStatus) $columnsToDrop[] = 'settlement_status';
            if ($hasRefundAmountPaise) $columnsToDrop[] = 'refund_amount_paise';
            if ($hasRefundGatewayId) $columnsToDrop[] = 'refund_gateway_id';
            if ($hasExpectedCurrency) $columnsToDrop[] = 'expected_currency';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
