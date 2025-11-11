// V-PHASE3-1730-067
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->string('type'); // progressive, milestone, consistency, referral, profit_share, lucky_draw, celebration
            $table->decimal('amount', 10, 2);
            $table->decimal('multiplier_applied', 5, 2)->default(1.0);
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_transactions');
    }
};