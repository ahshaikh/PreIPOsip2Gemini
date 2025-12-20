<?php
// V-AUDIT-FIX-LEARNING-CENTER | [AUDIT FIX] Learning Center Backend - High Priority #2
// Migration to create user_tutorial_progress table for tracking user's learning progress

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
        if (! Schema::hasTable('user_tutorial_progress')) {

            Schema::create('user_tutorial_progress', function (Blueprint $table) {
                $table->id();

                // Relationships
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('tutorial_id')->constrained('tutorials')->onDelete('cascade');

                // Progress Tracking
                $table->integer('current_step')->default(1);
                $table->integer('total_steps')->default(1);
                $table->json('steps_completed')->nullable(); // Array of completed step numbers

                // Completion Status
                $table->boolean('completed')->default(false)->index();
                $table->timestamp('completed_at')->nullable();

                // Time Tracking
                $table->timestamp('started_at')->nullable();
                $table->timestamp('last_activity_at')->nullable()->index();
                $table->unsignedInteger('time_spent_seconds')->default(0); // Total time spent

                // Timestamps
                $table->timestamps();

                // Ensure one progress record per user per tutorial
                $table->unique(['user_id', 'tutorial_id']);

                // Indexes for performance
                $table->index(['user_id', 'completed']);
                $table->index(['tutorial_id', 'completed']);
                $table->index('last_activity_at');
            });

        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tutorial_progress');
    }
};
