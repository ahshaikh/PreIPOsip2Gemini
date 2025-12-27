<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove stored inventory fields from deals.
     * These will be calculated dynamically from BulkPurchase.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Make product_id required - every deal MUST have a product for inventory tracking
            $table->foreignId('product_id')->nullable(false)->change();

            // Remove stored inventory fields - will calculate from BulkPurchase
            $table->dropColumn(['total_shares', 'available_shares']);
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->integer('total_shares')->nullable();
            $table->integer('available_shares')->nullable();
        });
    }
};
