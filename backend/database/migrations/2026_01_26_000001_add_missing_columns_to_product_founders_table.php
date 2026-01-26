<?php

/**
 * Migration: Add missing columns to product_founders table
 *
 * INVARIANT FIX:
 * The ProductFounder model declares 'display_order' and 'bio' in $fillable,
 * and the Product model's founders() relationship orders by 'display_order'.
 * However, these columns were not included in the original migration.
 *
 * This migration adds the missing columns to restore schema-model consistency.
 *
 * Prerequisites: product_founders table must exist (created by 2025_11_11_000204b)
 * Post-conditions: display_order and bio columns exist and are usable
 *
 * GOVERNANCE: This migration is deterministic - no guards, no conditionals.
 * If columns already exist, migration will fail loudly (as it should).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing columns that the ProductFounder model expects:
     * - display_order: For controlling display sequence (referenced in Product::founders() orderBy)
     * - bio: For founder biography text
     */
    public function up(): void
    {
        Schema::table('product_founders', function (Blueprint $table) {
            // Add bio column for founder biography
            $table->text('bio')->nullable()->after('linkedin_url');

            // Add display_order column for sorting (matches Product::founders() relationship)
            $table->integer('display_order')->default(0)->after('bio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_founders', function (Blueprint $table) {
            $table->dropColumn(['bio', 'display_order']);
        });
    }
};
