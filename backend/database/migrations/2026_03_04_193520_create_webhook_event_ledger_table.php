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
        Schema::create('webhook_event_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('event_id')->index();
            $table->string('payload_hash')->index();
            $table->integer('payload_size');
            $table->string('headers_hash')->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->boolean('timestamp_valid')->default(false);
            $table->boolean('replay_detected')->default(false)->index();
            $table->enum('processing_status', ['pending', 'processing', 'success', 'failed'])->default('pending')->index();
            $table->timestamp('received_at')->nullable()->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Institutional-Grade Protection: Unique constraint prevents double-entry
            $table->unique(['provider', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_event_ledger');
    }
};
