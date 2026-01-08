<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX 3 (P0): Create reconciliation_logs table
     * Stores daily reconciliation results for audit and monitoring
     */
    public function up(): void
    {
        Schema::create('reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->date('run_date');
            $table->time('run_time');
            $table->boolean('success')->default(false);
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->integer('checks_performed')->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->index(['run_date', 'success']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_logs');
    }
};
