<?php
// V-AUDIT-FIX-LEARNING-CENTER | [AUDIT FIX] Learning Center Backend - High Priority #2
// Migration to create tutorials table for learning center content management

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Guard required for migrate:fresh and legacy schema safety
        if (! Schema::hasTable('tutorials')) {

            Schema::create('tutorials', function (Blueprint $table) {
                $table->id();

                // Basic Information
                $table->string('slug')->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->longText('content')->nullable(); // Article content (markdown/HTML)
                $table->string('category', 100)->index(); // getting-started, investing-basics, etc.

                // Media
                $table->string('thumbnail_url')->nullable(); // New field
                $table->string('thumbnail')->nullable(); // Legacy field
                $table->string('video_url')->nullable();

                // Targeting & Classification
                $table->enum('user_role', ['all', 'user', 'company', 'admin'])->default('all');
                $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
                $table->integer('estimated_minutes')->nullable(); // New field
                $table->integer('duration_minutes')->nullable(); // Legacy field

                // Auto-launch Configuration
                $table->boolean('auto_launch')->default(false);
                $table->string('trigger_page')->nullable(); // URL pattern for auto-launch
                $table->json('trigger_conditions')->nullable(); // Additional conditions

                // Content Structure (Legacy - JSON fields)
                $table->json('steps')->nullable(); // Step-by-step tutorial content
                $table->json('resources')->nullable(); // Related resources/downloads
                $table->json('tags')->nullable(); // Tags for searchability

                // Tracking Metrics
                $table->unsignedInteger('views_count')->default(0);
                $table->unsignedInteger('completions_count')->default(0);
                $table->decimal('avg_completion_rate', 5, 2)->default(0); // Percentage
                $table->unsignedInteger('likes_count')->default(0); // Legacy field
                $table->decimal('rating', 3, 2)->nullable(); // Legacy field (e.g., 4.5)

                // Ordering & Visibility
                $table->integer('sort_order')->default(0)->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();

                // Status (Legacy field - kept for backwards compatibility)
                $table->enum('status', ['draft', 'published', 'archived'])->default('published')->index();

                // Timestamps
                $table->timestamps();
                $table->softDeletes();

                // Composite Indexes for performance
                $table->index(['category', 'is_active']);
                $table->index(['status', 'is_active']);
                $table->index(['is_featured', 'sort_order']);
            });

        }
    }

    /**
     * Reverse the migrations.
     *
     * FK-SAFE: Drop dependent tables first to avoid FK constraint violations.
     * user_tutorial_progress has FK to tutorials, must be dropped first.
     *
     * @return void
     */
    public function down(): void
    {
        // FK-SAFE: Drop dependent table first
        Schema::dropIfExists('user_tutorial_progress');
        Schema::dropIfExists('tutorials');
    }
};
