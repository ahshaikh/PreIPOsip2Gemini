<?php

/**
 * V-DISPUTE-REMEDIATION-2026: Add Chargeback Columns to Payments
 *
 * AUDIT FIX: Payment model declares chargeback fields but migration was missing.
 * This migration adds the required columns for chargeback tracking:
 *
 * 1. chargeback_initiated_at - When bank notified of dispute
 * 2. chargeback_confirmed_at - When bank ruled in customer's favor (terminal)
 * 3. chargeback_gateway_id - Gateway's dispute/chargeback ID (UNIQUE for idempotency)
 * 4. chargeback_reason - Reason code from gateway
 * 5. chargeback_amount_paise - Amount being disputed (may differ from payment amount)
 *
 * IDEMPOTENCY: chargeback_gateway_id has UNIQUE constraint to prevent
 * duplicate chargeback processing from webhook replay.
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
        // P0-FIX-5: DEFENSIVE CHECK - Detect pre-existing duplicate chargeback_gateway_ids
        // Must fail before attempting UNIQUE constraint if duplicates exist.
        // (Only check if column already exists from partial migration)
        if (Schema::hasColumn('payments', 'chargeback_gateway_id')) {
            $duplicates = DB::table('payments')
                ->select('chargeback_gateway_id', DB::raw('COUNT(*) as count'))
                ->whereNotNull('chargeback_gateway_id')
                ->groupBy('chargeback_gateway_id')
                ->having(DB::raw('COUNT(*)'), '>', 1)
                ->get();

            if ($duplicates->isNotEmpty()) {
                $duplicateIds = $duplicates->pluck('chargeback_gateway_id')->implode(', ');
                Log::critical('V-DISPUTE-REMEDIATION-2026: Cannot add UNIQUE constraint - duplicate chargeback_gateway_ids exist', [
                    'duplicate_chargeback_gateway_ids' => $duplicateIds,
                    'action_required' => 'Resolve duplicate chargeback_gateway_id values before migration',
                ]);

                throw new \RuntimeException(
                    "Cannot add UNIQUE constraint on chargeback_gateway_id. " .
                    "Duplicate values found: {$duplicateIds}. " .
                    "Resolve duplicates before running this migration."
                );
            }
        }

        // Check which columns need to be added BEFORE the Schema::table call
        $hasChargebackInitiatedAt = Schema::hasColumn('payments', 'chargeback_initiated_at');
        $hasChargebackConfirmedAt = Schema::hasColumn('payments', 'chargeback_confirmed_at');
        $hasChargebackGatewayId = Schema::hasColumn('payments', 'chargeback_gateway_id');
        $hasChargebackReason = Schema::hasColumn('payments', 'chargeback_reason');
        $hasChargebackAmountPaise = Schema::hasColumn('payments', 'chargeback_amount_paise');

        Schema::table('payments', function (Blueprint $table) use (
            $hasChargebackInitiatedAt,
            $hasChargebackConfirmedAt,
            $hasChargebackGatewayId,
            $hasChargebackReason,
            $hasChargebackAmountPaise
        ) {
            // Chargeback tracking columns
            if (!$hasChargebackInitiatedAt) {
                $table->timestamp('chargeback_initiated_at')->nullable()->after('refunded_at');
            }

            if (!$hasChargebackConfirmedAt) {
                $table->timestamp('chargeback_confirmed_at')->nullable()->after('chargeback_initiated_at');
            }

            if (!$hasChargebackGatewayId) {
                // UNIQUE constraint for idempotency - prevents duplicate chargeback processing
                // Nullable unique allows multiple NULL values (MySQL behavior)
                $table->string('chargeback_gateway_id')->nullable()->after('chargeback_confirmed_at');
            }

            if (!$hasChargebackReason) {
                $table->text('chargeback_reason')->nullable()->after('chargeback_gateway_id');
            }

            if (!$hasChargebackAmountPaise) {
                // Default 0 - will be set from webhook payload
                // May differ from payment amount for partial chargebacks
                $table->bigInteger('chargeback_amount_paise')->default(0)->after('chargeback_reason');
            }
        });

        // Add UNIQUE index separately to handle nullable unique properly
        // This must be done outside the column definition for proper index naming
        if (!$hasChargebackGatewayId) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('chargeback_gateway_id', 'payments_chargeback_gateway_id_unique');
            });
        }

        Log::info('V-DISPUTE-REMEDIATION-2026: Chargeback columns added to payments table');
    }

    public function down(): void
    {
        $hasChargebackInitiatedAt = Schema::hasColumn('payments', 'chargeback_initiated_at');
        $hasChargebackConfirmedAt = Schema::hasColumn('payments', 'chargeback_confirmed_at');
        $hasChargebackGatewayId = Schema::hasColumn('payments', 'chargeback_gateway_id');
        $hasChargebackReason = Schema::hasColumn('payments', 'chargeback_reason');
        $hasChargebackAmountPaise = Schema::hasColumn('payments', 'chargeback_amount_paise');

        Schema::table('payments', function (Blueprint $table) use (
            $hasChargebackInitiatedAt,
            $hasChargebackConfirmedAt,
            $hasChargebackGatewayId,
            $hasChargebackReason,
            $hasChargebackAmountPaise
        ) {
            // Drop unique index first
            if ($hasChargebackGatewayId) {
                $table->dropUnique('payments_chargeback_gateway_id_unique');
            }

            // Collect columns to drop
            $columnsToDrop = [];
            if ($hasChargebackInitiatedAt) $columnsToDrop[] = 'chargeback_initiated_at';
            if ($hasChargebackConfirmedAt) $columnsToDrop[] = 'chargeback_confirmed_at';
            if ($hasChargebackGatewayId) $columnsToDrop[] = 'chargeback_gateway_id';
            if ($hasChargebackReason) $columnsToDrop[] = 'chargeback_reason';
            if ($hasChargebackAmountPaise) $columnsToDrop[] = 'chargeback_amount_paise';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
