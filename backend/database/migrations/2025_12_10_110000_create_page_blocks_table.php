<?php
// V-CMS-ENHANCEMENT-010 | Page Blocks System
// Created: 2025-12-10 | Purpose: Enable block-based page builder with 15+ reusable block types

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates page_blocks table for flexible, reusable content blocks
     */
    public function up(): void
    {
        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();

            // Association
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');

            // Block Configuration
            $table->string('type'); // hero, cta, features, testimonials, stats, gallery, video, accordion, tabs, pricing, team, logos, timeline, newsletter, social, richtext
            $table->string('name')->nullable(); // Optional human-readable name for admin

            // Content and Configuration (JSON for flexibility)
            $table->json('config'); // Block-specific configuration (titles, images, colors, etc.)

            // Layout and Display
            $table->integer('display_order')->default(0);
            $table->string('container_width')->default('full'); // full, boxed, narrow
            $table->string('background_type')->default('none'); // none, color, gradient, image
            $table->json('background_config')->nullable(); // Colors, image URL, gradient stops
            $table->json('spacing')->nullable(); // padding, margin settings

            // Visibility Control
            $table->boolean('is_active')->default(true);
            $table->string('visibility')->default('always'); // always, desktop_only, mobile_only

            // A/B Testing & Analytics
            $table->string('variant')->nullable(); // For A/B testing
            $table->integer('views_count')->default(0);
            $table->integer('clicks_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['page_id', 'display_order']);
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_blocks');
    }
};
