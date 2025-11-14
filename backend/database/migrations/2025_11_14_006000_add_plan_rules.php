<?php
// V-FINAL-1730-449 (Created)

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
        // Add rules to the plans themselves
        Schema::table('plans', function (Blueprint $table) {
            // Test: test_subscription_create_max_subscriptions_per_user
            $table->integer('max_subscriptions_per_user')->default(1);
            
            // Test: test_subscription_pause_max_pause_count_exceeded
            $table->boolean('allow_pause')->default(true);
            $table->integer('max_pause_count')->default(3);
            $table->integer('max_pause_duration_months')->default(3);
        });

        // Add tracking to the subscription
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('pause_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_subscriptions_per_user', 'allow_pause', 'max_pause_count', 'max_pause_duration_months']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('pause_count');
        });
    }
};