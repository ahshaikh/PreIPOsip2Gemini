<?php

/**
 * V-DISPUTE-REMEDIATION-2026: Enforce UNIQUE on refund_gateway_id
 *
 * AUDIT FIX: refund_gateway_id had only an INDEX, not a UNIQUE constraint.
 * This allowed potential duplicate refund processing from webhook replay.
 *
 * This migration:
 * 1. Drops the existing index on refund_gateway_id
 * 2. Adds a UNIQUE constraint to enforce idempotency at DB level
 *
 * DEFENSIVE: If duplicates exist, migration will fail (intentionally).
 * Duplicates must be resolved manually before migration can succeed.
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
        // DEFENSIVE CHECK: Ensure no duplicates exist before adding unique constraint
        $duplicates = DB::table('payments')
            ->select('refund_gateway_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('refund_gateway_id')
            ->groupBy('refund_gateway_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $duplicateIds = $duplicates->pluck('refund_gateway_id')->implode(', ');
            Log::critical('V-DISPUTE-REMEDIATION-2026: Cannot add UNIQUE constraint - duplicates exist', [
                'duplicate_refund_gateway_ids' => $duplicateIds,
                'action_required' => 'Resolve duplicate refund_gateway_id values before migration',
            ]);

            throw new \RuntimeException(
                "Cannot add UNIQUE constraint on refund_gateway_id. " .
                "Duplicate values found: {$duplicateIds}. " .
                "Resolve duplicates before running this migration."
            );
        }

        // Check if column exists
        if (!Schema::hasColumn('payments', 'refund_gateway_id')) {
            Log::warning('V-DISPUTE-REMEDIATION-2026: refund_gateway_id column does not exist, skipping');
            return;
        }

        // Check for existing index and drop it
        $indexExists = $this->indexExists('payments', 'payments_refund_gateway_id_index');
        $uniqueExists = $this->indexExists('payments', 'payments_refund_gateway_id_unique');

        Schema::table('payments', function (Blueprint $table) use ($indexExists, $uniqueExists) {
            // Drop existing index if present
            if ($indexExists) {
                $table->dropIndex('payments_refund_gateway_id_index');
            }

            // Add unique constraint if not already present
            if (!$uniqueExists) {
                $table->unique('refund_gateway_id', 'payments_refund_gateway_id_unique');
            }
        });

        Log::info('V-DISPUTE-REMEDIATION-2026: UNIQUE constraint enforced on refund_gateway_id');
    }

    public function down(): void
    {
        $uniqueExists = $this->indexExists('payments', 'payments_refund_gateway_id_unique');

        Schema::table('payments', function (Blueprint $table) use ($uniqueExists) {
            // Drop unique constraint
            if ($uniqueExists) {
                $table->dropUnique('payments_refund_gateway_id_unique');
            }

            // Restore regular index
            $table->index('refund_gateway_id', 'payments_refund_gateway_id_index');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );

        return ($result[0]->count ?? 0) > 0;
    }
};
