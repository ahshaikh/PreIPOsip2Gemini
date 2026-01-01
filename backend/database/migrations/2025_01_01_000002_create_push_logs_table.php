<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Push Notification Delivery Logs
     * Tracks every push notification sent, delivered, opened
     * Required for NotificationController stats and analytics
     */
    public function up(): void
    {
        Schema::create('push_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Device info
            $table->string('device_token')->nullable();
            $table->string('device_type')->nullable(); // ios, android, web

            // Notification content
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Custom payload data

            // Delivery status
            $table->enum('status', ['pending', 'queued', 'sent', 'delivered', 'opened', 'clicked', 'failed'])->default('pending');

            // Provider details
            $table->string('provider')->nullable(); // fcm, onesignal
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();

            // Timestamps for tracking delivery funnel
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Notification options
            $table->string('priority')->default('normal'); // high, normal
            $table->integer('ttl')->nullable(); // Time to live in seconds
            $table->string('image_url')->nullable();
            $table->string('action_url')->nullable();
            $table->integer('badge_count')->nullable();

            // Additional metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for analytics queries
            $table->index('user_id');
            $table->index('status');
            $table->index('provider');
            $table->index('sent_at');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_logs');
    }
};
