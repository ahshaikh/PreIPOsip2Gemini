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
        // Add internal notes field to support_messages
        Schema::table('support_messages', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('is_admin_reply');
            $table->json('mentioned_users')->nullable()->after('is_internal'); // Array of user IDs mentioned
        });

        // Create ticket assignments and collaborations table
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('role', ['primary', 'collaborator'])->default('primary');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->unique(['support_ticket_id', 'assigned_to_user_id', 'role'], 'uniq_ticket_assign_role');
            $table->index('assigned_to_user_id', 'idx_ticket_assign_user');
            $table->index(['support_ticket_id', 'role'], 'idx_ticket_assign_ticket_role');
        });

        // Create ticket watchers table (for notifications)
        Schema::create('ticket_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('notify_on_update')->default(true);
            $table->boolean('notify_on_internal_note')->default(true);
            $table->timestamps();

            // Indexes
            $table->unique(['support_ticket_id', 'user_id'], 'uniq_ticket_watcher');
            $table->index('user_id', 'idx_ticket_watcher_user');
        });

        // Create agent activity tracking
        Schema::create('ticket_agent_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            $table->enum('activity_type', [
                'viewing',
                'typing',
                'editing_reply',
                'assigned',
                'transferred',
                'status_changed',
                'escalated'
            ]);
            $table->text('activity_data')->nullable(); // JSON data for the activity
            $table->timestamp('activity_at')->useCurrent();

            // Indexes
            $table->index(['support_ticket_id', 'activity_at'], 'idx_ticket_activity_ticket_time');
            $table->index(['agent_id', 'activity_at'], 'idx_ticket_activity_agent_time');
            $table->index(['support_ticket_id', 'agent_id', 'activity_type'], 'idx_ticket_activity_composite');
        });

        // Add collision detection fields to support_tickets
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('currently_viewing_by')->nullable()->constrained('users')->onDelete('set null')->after('assigned_to');
            $table->timestamp('viewing_started_at')->nullable()->after('currently_viewing_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['currently_viewing_by']);
            $table->dropColumn(['currently_viewing_by', 'viewing_started_at']);
        });

        Schema::dropIfExists('ticket_agent_activity');
        Schema::dropIfExists('ticket_watchers');
        Schema::dropIfExists('ticket_assignments');

        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn(['is_internal', 'mentioned_users']);
        });
    }
};
