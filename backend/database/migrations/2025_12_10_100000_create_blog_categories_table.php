<?php
// V-CMS-ENHANCEMENT-001 | Blog Categories System
// Created: 2025-12-10 | Purpose: Replace hardcoded blog categories with dynamic database-driven system

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates blog_categories table and updates blog_posts with category_id
     */
    public function up(): void
    {
        // Create blog_categories table
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Investment Tips"
            $table->string('slug')->unique(); // e.g., "investment-tips"
            $table->text('description')->nullable();
            $table->string('color')->default('#667eea'); // Hex color for category badge
            $table->string('icon')->nullable(); // Icon name (lucide-react icon)
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('slug');
            $table->index('is_active');
            $table->index('display_order');
        });

        // Add category_id to blog_posts (keep old 'category' column for backward compatibility)
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('author_id')
                ->constrained('blog_categories')
                ->onDelete('set null');

            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key and column from blog_posts
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('blog_categories');
    }
};
