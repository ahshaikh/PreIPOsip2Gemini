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
        // Help content for tooltips
        Schema::create('help_tooltips', function (Blueprint $table) {
            $table->id();
            $table->string('element_id')->unique(); // DOM element or feature identifier
            $table->string('title');
            $table->text('content');
            $table->enum('position', ['top', 'bottom', 'left', 'right', 'auto'])->default('auto');

            // Targeting
            $table->string('page_url')->nullable(); // Specific page or pattern
            $table->enum('user_role', ['all', 'user', 'admin', 'company'])->default('all');
            $table->json('conditions')->nullable(); // Advanced targeting rules

            // Media
            $table->string('icon')->nullable(); // Icon class or URL
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();

            // Behavior
            $table->boolean('show_once')->default(false); // Show only first time
            $table->boolean('dismissible')->default(true);
            $table->integer('auto_hide_seconds')->nullable(); // Auto-hide after X seconds
            $table->integer('priority')->default(0); // Higher priority shown first

            // Links
            $table->string('learn_more_url')->nullable();
            $table->string('cta_text')->nullable(); // Call to action button text
            $table->string('cta_url')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['page_url', 'is_active']);
            $table->index('user_role');
        });

        // Interactive tutorials (step-by-step guides)
	if (!Schema::hasTable('tutorials')) {
        Schema::create('tutorials', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable(); // Getting Started, Advanced, etc.
            $table->string('thumbnail_url')->nullable();

            // Targeting
            $table->enum('user_role', ['all', 'user', 'admin', 'company'])->default('all');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('estimated_minutes')->default(5);

            // Auto-launch conditions
            $table->boolean('auto_launch')->default(false);
            $table->string('trigger_page')->nullable(); // URL pattern to trigger on
            $table->json('trigger_conditions')->nullable(); // When to auto-launch

            // Tracking
            $table->integer('views_count')->default(0);
            $table->integer('completions_count')->default(0);
            $table->decimal('avg_completion_rate', 5, 2)->default(0);

            // Order and status
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['category', 'is_active']);
            $table->index(['user_role', 'is_active']);
            $table->index('is_featured');
        });
	}
        // Tutorial steps
        Schema::create('tutorial_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutorial_id')->constrained()->onDelete('cascade');
            $table->integer('step_number');
            $table->string('title');
            $table->text('content');

            // Element highlighting
            $table->string('target_element')->nullable(); // CSS selector to highlight
            $table->enum('highlight_style', ['pulse', 'glow', 'border', 'none'])->default('pulse');

            // Positioning
            $table->enum('position', ['top', 'bottom', 'left', 'right', 'center', 'modal'])->default('center');
            $table->integer('offset_x')->default(0);
            $table->integer('offset_y')->default(0);

            // Media
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('gif_url')->nullable();

            // Actions
            $table->boolean('requires_action')->default(false); // Must complete action to proceed
            $table->string('action_type')->nullable(); // click, type, scroll, etc.
            $table->string('action_target')->nullable(); // Element to interact with
            $table->text('action_validation')->nullable(); // How to verify action completed

            // Navigation
            $table->boolean('can_skip')->default(true);
            $table->string('next_button_text')->default('Next');
            $table->string('back_button_text')->default('Back');

            $table->timestamps();

            // Indexes
            $table->index(['tutorial_id', 'step_number']);
            $table->unique(['tutorial_id', 'step_number']);
        });

        // User progress tracking
        Schema::create('user_tutorial_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tutorial_id')->constrained()->onDelete('cascade');

            $table->integer('current_step')->default(1);
            $table->integer('total_steps');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();

            $table->integer('time_spent_seconds')->default(0); // Total time
            $table->json('steps_completed')->default('[]'); // Array of completed step numbers

            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'tutorial_id']);
            $table->index(['user_id', 'completed']);
            $table->index('tutorial_id');
        });

        // User help interactions (for analytics)
        Schema::create('user_help_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // For anonymous users

            // What was viewed
            $table->enum('interaction_type', [
                'tooltip_viewed',
                'tooltip_dismissed',
                'tutorial_started',
                'tutorial_completed',
                'tutorial_abandoned',
                'help_searched',
                'article_clicked',
                'video_watched'
            ]);

            $table->string('element_id')->nullable(); // Tooltip or tutorial ID
            $table->string('page_url');
            $table->json('metadata')->nullable(); // Additional context

            // Tracking
            $table->integer('duration_seconds')->nullable();
            $table->timestamp('interacted_at')->useCurrent();

            // Indexes
            $table->index(['user_id', 'interaction_type']);
            $table->index('interacted_at');
            $table->index('element_id');
        });

        // Contextual help suggestions (AI-powered)
        Schema::create('contextual_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('page_pattern'); // URL pattern like /dashboard*, /kyc*
            $table->string('trigger_element')->nullable(); // Element that triggers suggestion

            // Suggestion content
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['tip', 'warning', 'info', 'success'])->default('tip');

            // Related resources
            $table->json('related_articles')->nullable(); // KB article IDs
            $table->json('related_tutorials')->nullable(); // Tutorial IDs
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();

            // Conditions
            $table->json('display_conditions')->nullable(); // When to show
            $table->integer('max_displays')->default(-1); // -1 = unlimited
            $table->integer('days_between_displays')->default(0);

            // Priority
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['page_pattern', 'is_active']);
            $table->index('priority');
        });

        // User dismissed suggestions (to avoid repeat)
        Schema::create('user_dismissed_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('contextual_suggestion_id')->constrained()->onDelete('cascade');
            $table->integer('display_count')->default(1);
            $table->timestamp('first_displayed_at')->useCurrent();
            $table->timestamp('last_displayed_at')->useCurrent();
            $table->timestamp('dismissed_at')->useCurrent();

            $table->unique(['user_id', 'contextual_suggestion_id'], 'user_suggestion_unique');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_dismissed_suggestions');
        Schema::dropIfExists('contextual_suggestions');
        Schema::dropIfExists('user_help_interactions');
        Schema::dropIfExists('user_tutorial_progress');
        Schema::dropIfExists('tutorial_steps');
        Schema::dropIfExists('tutorials');
        Schema::dropIfExists('help_tooltips');
    }
};
