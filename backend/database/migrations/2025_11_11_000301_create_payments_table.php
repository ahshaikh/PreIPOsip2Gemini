// V-PHASE3-1730-062
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->string('gateway')->nullable(); // e.g., 'razorpay'
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->boolean('is_on_time')->default(false);
            $table->boolean('shares_allocated')->default(false);
            $table->boolean('bonuses_processed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};