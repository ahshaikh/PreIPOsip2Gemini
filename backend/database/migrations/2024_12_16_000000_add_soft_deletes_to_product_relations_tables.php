<?php
// V-AUDIT-MODULE6-006: Add soft deletes support to product relationship tables
// This migration adds the 'deleted_at' column to all product relationship models for data safety

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'deleted_at' timestamp column to all product relationship tables.
     * This enables soft deletes, preventing accidental data loss.
     *
     * @return void
     */
    public function up(): void
    {
        // Add soft deletes to product_highlights
        Schema::table('product_highlights', function (Blueprint $table) {
            $table->softDeletes(); // Adds 'deleted_at' column
        });

        // Add soft deletes to product_founders
        Schema::table('product_founders', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to product_funding_rounds
        Schema::table('product_funding_rounds', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to product_key_metrics
        Schema::table('product_key_metrics', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to product_risk_disclosures
        Schema::table('product_risk_disclosures', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes the 'deleted_at' column from all product relationship tables.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove soft deletes from product_highlights
        Schema::table('product_highlights', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Drops 'deleted_at' column
        });

        // Remove soft deletes from product_founders
        Schema::table('product_founders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from product_funding_rounds
        Schema::table('product_funding_rounds', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from product_key_metrics
        Schema::table('product_key_metrics', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from product_risk_disclosures
        Schema::table('product_risk_disclosures', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
