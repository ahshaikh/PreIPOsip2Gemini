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
        Schema::create('processed_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id');
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->string('event_type');
            $table->unsignedBigInteger('event_timestamp')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            // Unique index for idempotency
            $table->unique(['provider', 'event_id']);
            
            // Helpful for cleanup or searching by resource
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_webhook_events');
    }
};
