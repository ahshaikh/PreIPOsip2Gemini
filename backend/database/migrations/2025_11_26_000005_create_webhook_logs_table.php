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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('webhook_id')->nullable()->index();
            $table->text('payload');
            $table->text('headers')->nullable();
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'max_retries_reached'])->default('pending')->index();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(5);
            $table->text('response')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'next_retry_at']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
