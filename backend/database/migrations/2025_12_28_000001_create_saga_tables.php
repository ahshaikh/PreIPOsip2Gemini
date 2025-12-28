<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PROTOCOL:
     * - saga_executions: Tracks saga lifecycle (initiated, executing, completed, failed, compensated)
     * - saga_steps: Tracks individual operation execution and compensation
     * - Enables crash-safe recovery and full provenance auditing
     */
    public function up(): void
    {
        Schema::create('saga_executions', function (Blueprint $table) {
            $table->id();
            $table->uuid('saga_id')->unique();
            $table->enum('status', [
                'initiated',
                'executing',
                'completed',
                'failed',
                'compensated',
                'manually_resolved'
            ])->default('initiated');

            // Saga metadata (user_id, payment_id, investment_id, etc.)
            $table->json('metadata');

            // Progress tracking
            $table->integer('steps_total')->default(0);
            $table->integer('steps_completed')->default(0);

            // Failure information
            $table->integer('failure_step')->nullable();
            $table->text('failure_reason')->nullable();

            // Manual resolution
            $table->json('resolution_data')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');

            // Timestamps
            $table->timestamp('initiated_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('compensated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // Indexes for querying
            $table->index('saga_id');
            $table->index('status');
            $table->index(['status', 'failed_at']);
        });

        Schema::create('saga_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saga_execution_id')->constrained()->onDelete('cascade');
            $table->integer('step_number');
            $table->string('operation_class'); // Fully qualified class name

            // Step status
            $table->enum('status', ['completed', 'failed'])->default('completed');

            // Compensation status
            $table->enum('compensation_status', [
                'not_compensated',
                'compensated',
                'compensation_failed'
            ])->default('not_compensated');

            // Result data (for provenance)
            $table->json('result_data')->nullable();

            // Compensation error (if failed)
            $table->text('compensation_error')->nullable();

            // Timestamps
            $table->timestamp('executed_at');
            $table->timestamp('compensated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('saga_execution_id');
            $table->index(['saga_execution_id', 'step_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saga_steps');
        Schema::dropIfExists('saga_executions');
    }
};
