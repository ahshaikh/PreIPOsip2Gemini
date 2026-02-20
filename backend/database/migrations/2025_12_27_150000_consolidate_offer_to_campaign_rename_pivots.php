<?php
/**
 * P0.2 FIX: Consolidate Offer → Campaign (Make Dual Models IMPOSSIBLE)
 *
 * PROBLEM:
 * - Migration 2025_12_26_000001 renamed `offers` → `campaigns`
 * - Migration 2025_12_27_120001 created pivot tables with `offer_id` (references non-existent offers table)
 * - Both Campaign and Offer models exist
 * - Routes use both /offers and /campaigns
 * - Result: Schema conflicts, dual semantics, query branching required
 *
 * SOLUTION:
 * - Rename all offer_* pivot tables to campaign_*
 * - Rename offer_id columns to campaign_id
 * - Update foreign key constraints to reference campaigns table
 * - Drop offer_usages, offer_statistics (replaced by campaign_usages)
 * - Make Offer model structurally IMPOSSIBLE to use
 *
 * WHY BUG CANNOT REOCCUR (PROTOCOL 1):
 * - No offers table exists (renamed to campaigns)
 * - No offer_* pivot tables exist (renamed to campaign_*)
 * - All foreign keys reference campaigns.id (NOT offers.id)
 * - Offer model queries will FAIL (no table to query)
 * - Database enforces single source of truth at schema level
 */

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
        // [PROTOCOL 2]: Verify campaigns table exists before proceeding
        if (!Schema::hasTable('campaigns')) {
            throw new \Exception(
                'MIGRATION FAILED: campaigns table does not exist. ' .
                'Run migration 2025_12_26_000001_rename_offers_to_campaigns first.'
            );
        }

        // [PROTOCOL 1]: Drop offer_usages and offer_statistics tables
        // WHY: Replaced by campaign_usages (created in 2025_12_26_000002)
        // These tables reference offer_id which will no longer exist after renaming
        if (Schema::hasTable('offer_usages')) {
            Schema::dropIfExists('offer_usages');
        }

        if (Schema::hasTable('offer_statistics')) {
            Schema::dropIfExists('offer_statistics');
        }

        // [PROTOCOL 1]: Rename pivot tables and update foreign keys
        // This makes Offer model STRUCTURALLY IMPOSSIBLE to use

        // 1. Rename offer_products → campaign_products
        if (Schema::hasTable('offer_products')) {
            Schema::rename('offer_products', 'campaign_products');

            Schema::table('campaign_products', function (Blueprint $table) {
                // Drop old foreign key constraint
                $table->dropForeign(['offer_id']);

                // Rename column
                $table->renameColumn('offer_id', 'campaign_id');
            });

            // Add new foreign key constraint referencing campaigns table
            Schema::table('campaign_products', function (Blueprint $table) {
                $table->foreign('campaign_id')
                      ->references('id')
                      ->on('campaigns')
                      ->onDelete('cascade');

                // Update unique constraint
                $table->dropUnique(['campaign_id', 'product_id']); // May not exist, ignore error
                $table->unique(['campaign_id', 'product_id']);
            });
        }

        // 2. Rename offer_deals → campaign_deals
        if (Schema::hasTable('offer_deals')) {
            Schema::rename('offer_deals', 'campaign_deals');

            Schema::table('campaign_deals', function (Blueprint $table) {
                $table->dropForeign(['offer_id']);
                $table->renameColumn('offer_id', 'campaign_id');
            });

            Schema::table('campaign_deals', function (Blueprint $table) {
                $table->foreign('campaign_id')
                      ->references('id')
                      ->on('campaigns')
                      ->onDelete('cascade');

                $table->dropUnique(['campaign_id', 'deal_id']);
                $table->unique(['campaign_id', 'deal_id']);
            });
        }

        // 3. Rename offer_plans → campaign_plans
        if (Schema::hasTable('offer_plans')) {
            Schema::rename('offer_plans', 'campaign_plans');

            Schema::table('campaign_plans', function (Blueprint $table) {
                $table->dropForeign(['offer_id']);
                $table->renameColumn('offer_id', 'campaign_id');
            });

            Schema::table('campaign_plans', function (Blueprint $table) {
                $table->foreign('campaign_id')
                      ->references('id')
                      ->on('campaigns')
                      ->onDelete('cascade');

                $table->dropUnique(['campaign_id', 'plan_id']);
                $table->unique(['campaign_id', 'plan_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    // Intentionally left blank.
    // This migration consolidates schema permanently.

    }
};
