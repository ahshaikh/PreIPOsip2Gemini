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
        // Enhance Email Logs Table with tracking features
        Schema::table('email_logs', function (Blueprint $table) {
            $table->foreignId('email_template_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            $table->string('recipient_name')->nullable()->after('to_email');
            $table->string('provider')->nullable()->after('status'); // smtp, sendgrid, mailgun, etc.
            $table->string('provider_message_id')->nullable()->after('provider');
            $table->json('provider_response')->nullable()->after('provider_message_id');
            $table->timestamp('sent_at')->nullable()->after('provider_response');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('opened_at')->nullable()->after('delivered_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
            $table->timestamp('bounced_at')->nullable()->after('clicked_at');
            $table->timestamp('complained_at')->nullable()->after('bounced_at');
            $table->string('ip_address')->nullable()->after('complained_at');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->integer('open_count')->default(0)->after('user_agent');
            $table->integer('click_count')->default(0)->after('open_count');
            $table->json('metadata')->nullable()->after('click_count');

            // Add indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['email_template_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('provider_message_id');
            $table->index('sent_at');
            $table->index('opened_at');
        });

        // Enhance SMS Logs Table with tracking features
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->foreignId('sms_template_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            $table->string('recipient_name')->nullable()->after('to_mobile');
            $table->renameColumn('to_mobile', 'recipient_mobile');
            $table->string('provider')->nullable()->after('status'); // msg91, twilio, etc.
            $table->string('provider_message_id')->nullable()->after('provider');
            $table->json('provider_response')->nullable()->after('provider_message_id');
            $table->timestamp('sent_at')->nullable()->after('gateway_message_id');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
            $table->decimal('credits_used', 8, 2)->nullable()->after('failed_at');
            $table->json('metadata')->nullable()->after('credits_used');

            // Add indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['sms_template_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('provider_message_id');
            $table->index('sent_at');
        });

        // Create Push Notification Logs Table (new)
        Schema::create('push_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('device_token');
            $table->string('device_type')->nullable(); // ios, android, web
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Custom data payload
            $table->string('status')->default('pending'); // pending, queued, sent, delivered, opened, failed
            $table->string('provider')->nullable(); // fcm, onesignal, etc.
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('priority')->default('normal'); // high, normal
            $table->integer('ttl')->nullable(); // Time to live in seconds
            $table->string('image_url')->nullable();
            $table->string('action_url')->nullable();
            $table->integer('badge_count')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['device_token', 'created_at']);
            $table->index('device_type');
            $table->index('provider_message_id');
            $table->index('sent_at');
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop push_logs table
        Schema::dropIfExists('push_logs');

        // Revert sms_logs changes
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropForeign(['sms_template_id']);
            $table->dropColumn([
                'sms_template_id',
                'recipient_name',
                'provider',
                'provider_message_id',
                'provider_response',
                'sent_at',
                'delivered_at',
                'failed_at',
                'credits_used',
                'metadata',
            ]);
            $table->renameColumn('recipient_mobile', 'to_mobile');
        });

        // Revert email_logs changes
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropForeign(['email_template_id']);
            $table->dropColumn([
                'email_template_id',
                'recipient_name',
                'provider',
                'provider_message_id',
                'provider_response',
                'sent_at',
                'delivered_at',
                'opened_at',
                'clicked_at',
                'bounced_at',
                'complained_at',
                'ip_address',
                'user_agent',
                'open_count',
                'click_count',
                'metadata',
            ]);
        });
    }
};
