<?php
// V-FINAL-1730-499 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-PROD-007: Company Information
     */
    public function up(): void
    {
        // 1. Key Highlights (Bullet Points)
        Schema::create('product_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('content');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // 2. Founders
        Schema::create('product_founders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('title');
            $table->string('photo_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('bio')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // 3. Funding Rounds
        Schema::create('product_funding_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('round_name'); // e.g., "Series A"
            $table->date('date');
            $table->decimal('amount', 14, 2); // e.g., 50,000,000
            $table->decimal('valuation', 14, 2); // e.g., 500,000,000
            $table->text('investors'); // Comma-separated or simple text
            $table->timestamps();
        });

        // 4. Key Metrics
        Schema::create('product_key_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('metric_name'); // e.g., "Revenue (FY2023)"
            $table->string('value');      // e.g., "500"
            $table->string('unit');       // e.g., "Crores"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_key_metrics');
        Schema::dropIfExists('product_funding_rounds');
        Schema::dropIfExists('product_founders');
        Schema::dropIfExists('product_highlights');
    }
};