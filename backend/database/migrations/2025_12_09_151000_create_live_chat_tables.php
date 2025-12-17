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
        // Live Chat Sessions
        Schema::create('live_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('waiting'); // waiting, active, closed, archived
            $table->string('subject')->nullable();
            $table->text('initial_message')->nullable();
            $table->integer('unread_user_count')->default(0);
            $table->integer('unread_agent_count')->default(0);
            $table->unsignedTinyInteger('user_rating')->nullable();
            $table->text('user_feedback')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_by_type')->nullable(); // user, agent, system
            $table->foreignId('closed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('agent_id');
        });

        // Live Chat Messages
        Schema::create('live_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_chat_sessions')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('sender_type'); // user, agent, system
            $table->text('message');
            $table->string('type')->default('text'); // text, file, image, system
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
        });

        // Chat Agent Status
        Schema::create('chat_agent_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('status')->default('offline'); // online, away, busy, offline
            $table->integer('active_chats_count')->default(0);
            $table->integer('max_concurrent_chats')->default(5);
            $table->boolean('is_accepting_chats')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Chat Typing Indicators
        Schema::create('chat_typing_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_chat_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('user_type'); // user, agent
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();

            $table->unique(['session_id', 'user_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_typing_indicators');
        Schema::dropIfExists('chat_agent_status');
        Schema::dropIfExists('live_chat_messages');
        Schema::dropIfExists('live_chat_sessions');
    }
};
