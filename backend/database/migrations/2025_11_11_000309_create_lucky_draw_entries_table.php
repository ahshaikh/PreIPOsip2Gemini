<?php
// V-PHASE3-1730-070 (Created) | V-FINAL-1730-363 (Refactored)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_draw_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lucky_draw_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            
            // New structure: one row per user/draw
            $table->integer('base_entries')->default(0);
            $table->integer('bonus_entries')->default(0);

            // Winner info
            $table->boolean('is_winner')->default(false);
            $table->integer('prize_rank')->nullable();
            $table->decimal('prize_amount', 10, 2)->nullable();
            
            $table->timestamps();

            // Ensure a user can only have one entry row per draw
            $table->unique(['user_id', 'lucky_draw_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_draw_entries');
    }
};