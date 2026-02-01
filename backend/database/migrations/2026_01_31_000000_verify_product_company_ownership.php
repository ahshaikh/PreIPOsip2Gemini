<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 1 AUDIT: Verify Product Company Ownership Before Enforcement
 *
 * INVARIANT: Every product MUST belong to a company.
 *
 * PURPOSE:
 * This migration runs BEFORE the NOT NULL enforcement migration.
 * It verifies that all products have company_id assigned.
 * If any orphan products exist, it FAILS with actionable information.
 *
 * WHY THIS EXISTS:
 * The backfill migration (2026_01_29_192522) maps products via bulk_purchases.
 * Products without bulk_purchases remain orphaned.
 * This migration identifies those orphans so they can be manually resolved.
 *
 * BEHAVIOR:
 * - If all products have company_id: migration passes (no-op)
 * - If orphan products exist: migration FAILS with product IDs
 *
 * REQUIRED MANUAL ACTION:
 * Before re-running this migration, for each orphan product either:
 * 1. Assign it to the correct company: UPDATE products SET company_id = X WHERE id = Y
 * 2. Delete it if it's test/invalid data: DELETE FROM products WHERE id = Y
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $orphans = DB::table('products')
            ->whereNull('company_id')
            ->select(['id', 'name', 'slug', 'created_at'])
            ->get();

        if ($orphans->isEmpty()) {
            // All products have company_id - precondition met
            return;
        }

        // Build actionable error message
        $orphanList = $orphans->map(function ($p) {
            return "  - ID: {$p->id}, Name: " . ($p->name ?? 'NULL') . ", Slug: " . ($p->slug ?? 'NULL') . ", Created: {$p->created_at}";
        })->join("\n");

        throw new \RuntimeException(
            "PHASE 1 AUDIT PRECONDITION FAILED: {$orphans->count()} product(s) have NULL company_id.\n\n" .
            "ORPHAN PRODUCTS:\n{$orphanList}\n\n" .
            "REQUIRED ACTION:\n" .
            "For each orphan product, either:\n" .
            "1. Assign to correct company: UPDATE products SET company_id = <company_id> WHERE id = <product_id>\n" .
            "2. Delete if test/invalid: DELETE FROM products WHERE id = <product_id>\n\n" .
            "Then re-run: php artisan migrate"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verification-only migration - nothing to reverse
    }
};
