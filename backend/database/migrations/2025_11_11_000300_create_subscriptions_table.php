<?php
// V-PHASE3-1730-061 (Created) | V-FINAL-1730-613 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 10, 2); // The actual amount
            $table->string('subscription_code')->unique();
            $table->string('razorpay_subscription_id')->nullable();
            
            $table->string('status')->default('active'); // active, paused, cancelled, completed
            $table->boolean('is_auto_debit')->default(false);
            
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_payment_date');
            
            $table->decimal('bonus_multiplier', 5, 2)->default(1.00);
            $table->integer('consecutive_payments_count')->default(0);
            
            // Pause Fields
            $table->integer('pause_count')->default(0);
            $table->date('pause_start_date')->nullable();
            $table->date('pause_end_date')->nullable();
            
            // Cancel Fields
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};