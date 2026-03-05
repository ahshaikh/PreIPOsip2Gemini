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
        Schema::create('webhook_dead_letters', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('event_id')->index();
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->integer('attempts');
            $table->timestamp('failed_at')->useCurrent();
            $table->timestamps();

            // Unique index for idempotency with historical tracking
            $table->unique(['provider', 'event_id', 'failed_at']);
            
            // Helpful for searching by resource
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_dead_letters');
    }
};
