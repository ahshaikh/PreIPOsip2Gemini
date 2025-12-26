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
        Schema::create('campaign_usages', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Polymorphic relationship to track what the campaign was applied to
            // Can be Investment, Subscription, Payment, etc.
            $table->morphs('applicable'); // Creates applicable_type and applicable_id

            // Financial tracking
            $table->decimal('original_amount', 12, 2); // Amount before discount
            $table->decimal('discount_applied', 12, 2); // Actual discount given
            $table->decimal('final_amount', 12, 2);    // Amount after discount

            // Metadata
            $table->string('campaign_code'); // Store code at time of use (in case campaign code changes)
            $table->json('campaign_snapshot')->nullable(); // Full campaign data at time of use
            $table->string('ip_address', 45)->nullable(); // Track IP for fraud detection
            $table->string('user_agent')->nullable();     // Track device/browser

            // Timestamps
            $table->timestamp('used_at')->useCurrent();
            $table->timestamps();

            // Indexes for performance and queries
            $table->index('campaign_id');
            $table->index('user_id');
            $table->index('campaign_code');
            $table->index('used_at');
            // Note: morphs('applicable') already creates an index for applicable_type, applicable_id

            // Prevent duplicate application to same entity
            $table->unique(['campaign_id', 'applicable_type', 'applicable_id'], 'unique_campaign_application');

            // Prevent user from exceeding per-user limits (enforced at service layer)
            $table->index(['campaign_id', 'user_id'], 'campaign_user_usage_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_usages');
    }
};
