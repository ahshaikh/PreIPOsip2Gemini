<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Push Notification Device Tokens Storage
     * Stores FCM/OneSignal device tokens for each user's devices
     * Required for SendPushCampaignJob to actually send notifications
     */
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Device identification
            $table->string('device_token')->unique(); // FCM token or OneSignal player ID
            $table->string('device_type')->nullable(); // ios, android, web
            $table->string('device_name')->nullable(); // "John's iPhone 12"
            $table->string('device_model')->nullable(); // "iPhone 12 Pro"
            $table->string('os_version')->nullable(); // "iOS 15.2"
            $table->string('app_version')->nullable(); // "2.1.0"

            // Provider info
            $table->string('provider')->default('fcm'); // fcm, onesignal

            // Device metadata
            $table->string('platform')->nullable(); // ios, android, web
            $table->string('browser')->nullable(); // for web push
            $table->json('metadata')->nullable(); // Additional device info

            // Status tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('registered_at')->nullable();

            // Token refresh (FCM tokens can expire/change)
            $table->timestamp('token_refreshed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('user_id');
            $table->index(['user_id', 'is_active']);
            $table->index('device_type');
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
