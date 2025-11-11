<?php
// V-PHASE3-1730-061

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
            $table->string('subscription_code')->unique();
            $table->string('status')->default('active'); // active, paused, cancelled
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_payment_date');
            $table->decimal('bonus_multiplier', 5, 2)->default(1.0);
            $table->integer('consecutive_payments_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};