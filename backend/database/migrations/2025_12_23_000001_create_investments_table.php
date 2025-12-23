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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->string('investment_code')->unique();
            $table->integer('shares_allocated');
            $table->decimal('price_per_share', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['pending', 'active', 'exited', 'cancelled'])->default('pending');
            $table->timestamp('invested_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->decimal('exit_price_per_share', 15, 2)->nullable();
            $table->decimal('exit_amount', 15, 2)->nullable();
            $table->decimal('profit_loss', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['subscription_id']);
            $table->index(['deal_id']);
            $table->index('status');
            $table->index('invested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
