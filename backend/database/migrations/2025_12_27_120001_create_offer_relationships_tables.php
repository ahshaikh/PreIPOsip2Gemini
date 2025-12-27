<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campaign Integration - Link Offers to Products, Deals, and Plans.
     *
     * BUSINESS VALUE:
     * - Run targeted promotions for specific products
     * - Create deal-specific campaigns
     * - Offer plan-tier exclusive promotions
     * - Track campaign performance by product/deal/plan
     */
    public function up(): void
    {
        // Update offers table to add scope and target settings
        Schema::table('offers', function (Blueprint $table) {
            $table->enum('scope', ['global', 'products', 'deals', 'plans'])->default('global')
                  ->after('code')
                  ->comment('Scope of offer: global (all), products, deals, or plans');

            $table->boolean('auto_apply')->default(false)
                  ->after('user_usage_limit')
                  ->comment('Auto-apply to eligible transactions');

            $table->json('eligible_user_segments')->nullable()
                  ->after('auto_apply')
                  ->comment('User segments eligible for this offer (KYC status, plan tier, etc)');
        });

        // Offer-Product Relationships (e.g., "20% off on Tech Sector products")
        Schema::create('offer_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('custom_discount_percent', 5, 2)->nullable()->comment('Override offer discount for this product');
            $table->decimal('custom_discount_amount', 10, 2)->nullable()->comment('Override offer discount for this product');
            $table->boolean('is_featured')->default(false)->comment('Feature this product in the offer');
            $table->integer('priority')->default(0)->comment('Display priority');
            $table->timestamps();

            $table->unique(['offer_id', 'product_id']);
            $table->index('is_featured');
        });

        // Offer-Deal Relationships (e.g., "Early bird offer for XYZ Company deal")
        Schema::create('offer_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->decimal('custom_discount_percent', 5, 2)->nullable();
            $table->decimal('custom_discount_amount', 10, 2)->nullable();
            $table->decimal('min_investment_override', 15, 2)->nullable()->comment('Override min investment for this campaign');
            $table->boolean('is_featured')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->unique(['offer_id', 'deal_id']);
        });

        // Offer-Plan Relationships (e.g., "Premium members get 15% off all products")
        Schema::create('offer_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->decimal('additional_discount_percent', 5, 2)->nullable()->comment('Stack with plan discount');
            $table->boolean('is_exclusive')->default(false)->comment('Exclusive to this plan tier');
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->unique(['offer_id', 'plan_id']);
        });

        // Offer Usage Tracking (per user, per product/deal)
        Schema::create('offer_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('deal_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_applied', 10, 2)->comment('Actual discount amount given');
            $table->decimal('investment_amount', 15, 2)->comment('Total investment with discount applied');
            $table->string('code_used')->nullable()->comment('Promo code used');
            $table->timestamp('used_at');

            $table->index(['offer_id', 'user_id']);
            $table->index(['offer_id', 'product_id']);
            $table->index(['offer_id', 'deal_id']);
            $table->index('used_at');
        });

        // Campaign Performance Stats (aggregated metrics)
        Schema::create('offer_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade')->comment('Stats per product');
            $table->foreignId('deal_id')->nullable()->constrained()->onDelete('cascade')->comment('Stats per deal');
            $table->date('stat_date')->comment('Daily aggregation');

            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_applications')->default(0);
            $table->unsignedInteger('total_conversions')->default(0)->comment('Completed investments');
            $table->decimal('total_discount_given', 15, 2)->default(0);
            $table->decimal('total_revenue_generated', 20, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0)->comment('Percentage');

            $table->timestamps();

            $table->unique(['offer_id', 'product_id', 'deal_id', 'stat_date']);
            $table->index(['offer_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_statistics');
        Schema::dropIfExists('offer_usages');
        Schema::dropIfExists('offer_plans');
        Schema::dropIfExists('offer_deals');
        Schema::dropIfExists('offer_products');

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['scope', 'auto_apply', 'eligible_user_segments']);
        });
    }
};
