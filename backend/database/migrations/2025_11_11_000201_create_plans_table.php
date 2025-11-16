<?php
// V-DEPLOY-1730-003 (Created) | V-PHASE2-1730-025 | V-FINAL-1730-611 (Consolidated) | V-FINAL-1730-619 (Timestamps Fix)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('razorpay_plan_id')->nullable();
            $table->decimal('monthly_amount', 10, 2);
            $table->integer('duration_months')->default(36);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            
            // Scheduling
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();

            // Rules
            $table->integer('max_subscriptions_per_user')->default(1);
            $table->boolean('allow_pause')->default(true);
            $table->integer('max_pause_count')->default(3);
            $table->integer('max_pause_duration_months')->default(3);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('feature_text');
            $table->string('icon')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps(); // <-- THE MISSING LINE
        });

        Schema::create('plan_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('config_key');
            $table->json('value');
            $table->timestamps();
            
            $table->unique(['plan_id', 'config_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_configs');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};