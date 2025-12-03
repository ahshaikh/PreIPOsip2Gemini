<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('code')->unique();
            $table->text('description');
            $table->longText('long_description')->nullable();
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->enum('discount_type', ['percentage', 'fixed_amount'])->default('fixed_amount');
            $table->decimal('discount_percent', 5, 2)->nullable(); // e.g., 25.00%
            $table->decimal('discount_amount', 10, 2)->nullable(); // e.g., 500.00
            $table->decimal('min_investment', 10, 2)->nullable(); // Minimum investment required
            $table->decimal('max_discount', 10, 2)->nullable(); // Maximum discount cap
            $table->unsignedInteger('usage_limit')->nullable(); // Total usage limit
            $table->unsignedInteger('usage_count')->default(0); // Current usage count
            $table->unsignedInteger('user_usage_limit')->nullable(); // Per-user usage limit
            $table->timestamp('expiry')->nullable(); // Expiry date
            $table->string('image_url')->nullable(); // Card/thumbnail image
            $table->string('hero_image')->nullable(); // Hero/banner image
            $table->string('video_url')->nullable(); // Promotional video
            $table->json('features')->nullable(); // List of features/benefits
            $table->json('terms')->nullable(); // Terms and conditions
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index('code');
            $table->index('status');
            $table->index('expiry');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
