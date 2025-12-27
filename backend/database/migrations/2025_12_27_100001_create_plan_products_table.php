<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create plan-product eligibility relationship.
     *
     * BUSINESS LOGIC:
     * - Plans define which products subscribers can access
     * - Products can be available to multiple plans
     * - Each plan-product link can have custom discount
     * - NULL allowed_plan_ids on product = available to all plans
     */
    public function up(): void
    {
        Schema::create('plan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Plan-specific product settings
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Extra discount for this plan');
            $table->decimal('min_investment_override', 15, 2)->nullable()->comment('Override product min_investment');
            $table->decimal('max_investment_override', 15, 2)->nullable()->comment('Override product max_investment');
            $table->boolean('is_featured')->default(false)->comment('Featured product for this plan');
            $table->integer('priority')->default(0)->comment('Display order for this plan');

            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['plan_id', 'product_id']);

            // Indexes for performance
            $table->index(['plan_id', 'is_featured']);
            $table->index(['product_id', 'plan_id']);
        });

        // Add plan eligibility mode to products
        Schema::table('products', function (Blueprint $table) {
            $table->enum('eligibility_mode', ['all_plans', 'specific_plans', 'premium_only'])
                  ->default('all_plans')
                  ->after('status')
                  ->comment('Controls which plans can access this product');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('eligibility_mode');
        });

        Schema::dropIfExists('plan_products');
    }
};
