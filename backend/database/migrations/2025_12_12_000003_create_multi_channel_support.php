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
        // Communication channels configuration
        Schema::create('communication_channels', function (Blueprint $table) {
            $table->id();
            $table->enum('channel_type', [
                'email',
                'sms',
                'whatsapp',
                'telegram',
                'twitter',
                'linkedin',
                'in_app'
            ])->unique();
            $table->string('channel_name'); // Display name
            $table->boolean('is_enabled')->default(false);

            // Channel-specific configuration (JSON)
            // For Email: SMTP settings, templates
            // For WhatsApp: Business API credentials, phone number
            // For SMS: Twilio/MSG91 credentials
            // For Telegram: Bot token, webhook URL
            $table->json('configuration')->nullable();

            // Auto-response settings
            $table->boolean('auto_reply_enabled')->default(false);
            $table->text('auto_reply_message')->nullable();

            // Business hours for this channel
            $table->time('available_from')->default('09:00:00');
            $table->time('available_to')->default('18:00:00');
            $table->json('available_days')->default('[1,2,3,4,5]'); // Mon-Fri

            $table->timestamps();
        });

        // Track which channel each message came from
        Schema::table('support_messages', function (Blueprint $table) {
            $table->foreignId('channel_id')->nullable()->after('support_ticket_id')
                ->constrained('communication_channels')->onDelete('set null');
            $table->string('external_message_id')->nullable()->after('channel_id'); // Original message ID from platform
            $table->json('channel_metadata')->nullable()->after('external_message_id'); // Platform-specific data
        });

        // Unified inbox - all messages across all channels
        Schema::create('unified_inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('communication_channels')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Linked user

            // Message details
            $table->string('sender_identifier'); // Email, phone number, username, etc.
            $table->string('sender_name')->nullable();
            $table->text('message_content');
            $table->json('attachments')->nullable(); // URLs, file paths
            $table->enum('direction', ['inbound', 'outbound']);

            // Ticket association
            $table->foreignId('support_ticket_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('ticket_created')->default(false);

            // Processing status
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->text('processing_error')->nullable();

            // Replied status
            $table->boolean('replied')->default(false);
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('replied_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Raw data from platform
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['channel_id', 'created_at']);
            $table->index(['user_id', 'channel_id']);
            $table->index(['sender_identifier', 'channel_id']);
            $table->index(['status', 'created_at']);
            $table->index('support_ticket_id');
        });

        // User's preferred communication channels
        Schema::create('user_channel_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->constrained('communication_channels')->onDelete('cascade');

            // Verification
            $table->string('channel_identifier'); // email, phone, username
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token')->nullable();

            // Preferences
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            // Unique constraint
            $table->unique(['user_id', 'channel_id', 'channel_identifier'], 'user_channel_identifier_unique');
            $table->index('user_id');
        });

        // Channel templates for automated messages
        Schema::create('channel_message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('communication_channels')->onDelete('cascade');
            $table->string('template_key'); // ticket_created, ticket_updated, etc.
            $table->string('template_name');
            $table->text('template_content'); // With variables: {{ticket_id}}, {{user_name}}, etc.
            $table->json('variables')->nullable(); // List of available variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['channel_id', 'template_key']);
            $table->unique(['channel_id', 'template_key']);
        });

        // Outbound message queue (for bulk sending)
        Schema::create('outbound_message_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('communication_channels')->onDelete('cascade');
            $table->string('recipient_identifier'); // Email, phone, etc.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('message_content');
            $table->json('attachments')->nullable();
            $table->foreignId('support_ticket_id')->nullable()->constrained()->onDelete('set null');

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Processing
            $table->enum('status', ['pending', 'sending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);

            // Tracking
            $table->string('external_message_id')->nullable(); // ID from provider
            $table->boolean('delivered')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'scheduled_at']);
            $table->index(['channel_id', 'status']);
            $table->index('support_ticket_id');
        });

        // Add channel preference to support_tickets
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('preferred_channel_id')->nullable()->after('status')
                ->constrained('communication_channels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['preferred_channel_id']);
            $table->dropColumn('preferred_channel_id');
        });

        Schema::dropIfExists('outbound_message_queue');
        Schema::dropIfExists('channel_message_templates');
        Schema::dropIfExists('user_channel_preferences');
        Schema::dropIfExists('unified_inbox_messages');

        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn(['channel_id', 'external_message_id', 'channel_metadata']);
        });

        Schema::dropIfExists('communication_channels');
    }
};
