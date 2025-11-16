<?php
// V-PHASE2-1730-036 (Created) | V-FINAL-1730-494 (Advanced Pricing) | V-FINAL-1730-612 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sector')->nullable();
            
            // Pricing
            $table->decimal('face_value_per_unit', 10, 2);
            $table->decimal('current_market_price', 10, 2)->nullable();
            $table->timestamp('last_price_update')->nullable();
            $table->boolean('auto_update_price')->default(false);
            $table->string('price_api_endpoint')->nullable();
            
            $table->decimal('min_investment', 10, 2);
            $table->date('expected_ipo_date')->nullable();
            $table->string('status')->default('active'); // active, upcoming, listed, closed
            
            // Compliance
            $table->string('sebi_approval_number')->nullable();
            $table->date('sebi_approval_date')->nullable();
            $table->text('compliance_notes')->nullable();
            $table->text('regulatory_warnings')->nullable();
            
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            
            $table->json('description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Dependent Tables for FSD-PROD-007, 009
        Schema::create('product_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('content');
            $table->integer('display_order')->default(0);
        });

        Schema::create('product_founders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('title');
            $table->string('photo_url')->nullable();
            $table->string('linkedin_url')->nullable();
        });

        Schema::create('product_funding_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('round_name');
            $table->date('date');
            $table->decimal('amount', 14, 2);
            $table->decimal('valuation', 14, 2);
            $table->text('investors');
        });

        Schema::create('product_key_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('metric_name');
            $table->string('value');
            $table->string('unit');
        });
        
        Schema::create('product_risk_disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('risk_category');
            $table->string('severity');
            $table->string('risk_title');
            $table->text('risk_description');
            $table->integer('display_order')->default(0);
        });
        
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->date('recorded_at');
            $table->timestamps();
            $table->unique(['product_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
        Schema::dropIfExists('product_risk_disclosures');
        Schema::dropIfExists('product_key_metrics');
        Schema::dropIfExists('product_funding_rounds');
        Schema::dropIfExists('product_founders');
        Schema::dropIfExists('product_highlights');
        Schema::dropIfExists('products');
    }
};