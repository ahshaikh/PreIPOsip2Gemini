<?php
// V-CMS-ENHANCEMENT-002 | Add Missing Blog Post Fields
// Created: 2025-12-10 | Purpose: Add fields expected by frontend (excerpt, tags, SEO, featured flag, old category)

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
        Schema::table('blog_posts', function (Blueprint $table) {
            // Content fields
            $table->text('excerpt')->nullable()->after('content');
            $table->string('category')->nullable()->after('author_id'); // Old string-based category (for backward compatibility)

            // SEO fields
            $table->string('seo_title')->nullable()->after('excerpt');
            $table->text('seo_description')->nullable()->after('seo_title');

            // Features
            $table->boolean('is_featured')->default(false)->after('status');
            $table->json('tags')->nullable()->after('seo_description'); // Array of tags

            // Indexes
            $table->index('is_featured');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['excerpt', 'category', 'seo_title', 'seo_description', 'is_featured', 'tags']);
        });
    }
};
