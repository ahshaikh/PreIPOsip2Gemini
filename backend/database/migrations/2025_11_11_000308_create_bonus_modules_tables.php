<?php
// V-DEPLOY-1730-016 (Created) |  V-PHASE3-1730-069 | V-FINAL-1730-617 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lucky Draw
        Schema::create('lucky_draws', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('draw_date');
            $table->json('prize_structure');
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lucky_draw_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lucky_draw_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            
            $table->integer('base_entries')->default(0);
            $table->integer('bonus_entries')->default(0);

            $table->boolean('is_winner')->default(false);
            $table->integer('prize_rank')->nullable();
            $table->decimal('prize_amount', 10, 2)->nullable();
            
            $table->timestamps();
            $table->unique(['user_id', 'lucky_draw_id']);
        });

        // Profit Sharing
        Schema::create('profit_shares', function (Blueprint $table) {
            $table->id();
            $table->string('period_name')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_pool', 14, 2);
            $table->decimal('net_profit', 14, 2);
            $table->string('status')->default('pending');
            $table->foreignId('admin_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('user_profit_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('profit_share_id')->constrained('profit_shares')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->foreignId('bonus_transaction_id')->nullable()->constrained('bonus_transactions');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
        // Celebration Bonuses
        Schema::create('celebration_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('event_date');
            $table->json('bonus_amount_by_plan');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring_annually')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('celebration_events');
        Schema::dropIfExists('user_profit_shares');
        Schema::dropIfExists('profit_shares');
        Schema::dropIfExists('lucky_draw_entries');
        Schema::dropIfExists('lucky_draws');
    }
};