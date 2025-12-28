<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Job Idempotency and State Tracking (G.22-G.24)
 *
 * PURPOSE:
 * - G.22: Make all async jobs idempotent (safe to run twice)
 * - G.23: Detect and surface partial completion
 * - G.24: Add timeout and escalation for stuck states
 *
 * TABLES:
 * 1. job_executions - Track every job execution for idempotency
 * 2. job_state_tracking - Track workflow/saga state for partial completion detection
 * 3. stuck_state_alerts - Track stuck states requiring escalation
 *
 * MECHANISM:
 * - Before executing financial operations, check if already executed
 * - Track workflow state transitions (pending → processing → completed/failed)
 * - Detect stuck states (processing for too long, pending too long)
 * - Escalate via alerts, auto-resolution, or manual review queue
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===================================================================
        // TABLE 1: job_executions - Idempotency Tracking
        // ===================================================================

        Schema::create('job_executions', function (Blueprint $table) {
            $table->id();

            // Job identification
            $table->string('job_class');           // 'App\Jobs\ProcessSuccessfulPaymentJob'
            $table->string('idempotency_key');     // Unique key for this operation
            $table->string('job_queue')->nullable(); // Queue name

            // Execution tracking
            $table->string('status');              // 'pending', 'processing', 'completed', 'failed'
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Result tracking
            $table->text('result')->nullable();    // JSON result data
            $table->text('error_message')->nullable();
            $table->text('stack_trace')->nullable();

            // Metadata
            $table->json('input_data')->nullable(); // Job parameters (for debugging)
            $table->integer('attempt_number')->default(1);
            $table->integer('max_attempts')->default(3);

            $table->timestamps();

            // Indexes
            $table->unique(['job_class', 'idempotency_key'], 'idx_job_idempotency');
            $table->index('status');
            $table->index('started_at');
            $table->index('created_at');
        });

        // ===================================================================
        // TABLE 2: job_state_tracking - Workflow State Tracking
        // ===================================================================

        Schema::create('job_state_tracking', function (Blueprint $table) {
            $table->id();

            // Workflow identification
            $table->string('workflow_type');       // 'payment_processing', 'allocation', 'bonus_calculation'
            $table->string('workflow_id');         // Reference to entity (payment_id, investment_id)
            $table->unsignedBigInteger('entity_id');

            // State tracking
            $table->string('current_state');       // 'pending', 'wallet_credited', 'shares_allocated', 'bonuses_awarded'
            $table->string('previous_state')->nullable();

            // Completion tracking
            $table->json('completed_steps')->nullable(); // ['wallet_credit', 'share_allocation']
            $table->json('pending_steps')->nullable();   // ['bonus_calculation', 'email_notification']
            $table->json('failed_steps')->nullable();    // ['referral_processing']

            // Progress metrics
            $table->integer('total_steps')->default(0);
            $table->integer('completed_steps_count')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);

            // Timeout detection
            $table->timestamp('started_at');
            $table->timestamp('last_updated_at');
            $table->timestamp('expected_completion_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Stuck state detection
            $table->boolean('is_stuck')->default(false);
            $table->string('stuck_reason')->nullable();
            $table->timestamp('stuck_detected_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['workflow_type', 'entity_id'], 'idx_workflow_entity');
            $table->index('current_state');
            $table->index('is_stuck');
            $table->index('started_at');
            $table->index('last_updated_at');
        });

        // ===================================================================
        // TABLE 3: stuck_state_alerts - Escalation Tracking
        // ===================================================================

        Schema::create('stuck_state_alerts', function (Blueprint $table) {
            $table->id();

            // Alert identification
            $table->string('alert_type');          // 'stuck_payment', 'stuck_allocation', 'stuck_bonus'
            $table->string('severity');            // 'low', 'medium', 'high', 'critical'

            // Affected entity
            $table->string('entity_type');         // 'payment', 'investment', 'bonus'
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('user_id')->nullable();

            // Stuck state details
            $table->string('stuck_state');         // 'processing_too_long', 'pending_too_long'
            $table->text('description');
            $table->integer('stuck_duration_seconds');
            $table->timestamp('stuck_since');

            // Auto-resolution
            $table->boolean('auto_resolvable')->default(false);
            $table->string('auto_resolution_action')->nullable(); // 'retry', 'cancel', 'escalate'
            $table->boolean('auto_resolved')->default(false);
            $table->timestamp('auto_resolved_at')->nullable();

            // Manual resolution
            $table->boolean('requires_manual_review')->default(false);
            $table->boolean('reviewed')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();

            // Escalation tracking
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->unsignedBigInteger('escalated_to')->nullable();

            // Notification tracking
            $table->boolean('admin_notified')->default(false);
            $table->boolean('user_notified')->default(false);
            $table->timestamp('admin_notified_at')->nullable();
            $table->timestamp('user_notified_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['entity_type', 'entity_id']);
            $table->index('severity');
            $table->index('reviewed');
            $table->index('escalated');
            $table->index('created_at');
        });

        // ===================================================================
        // Constraints
        // ===================================================================

        // job_executions: Status must be valid
        DB::statement("
            ALTER TABLE job_executions
            ADD CONSTRAINT check_job_execution_status
            CHECK (status IN ('pending', 'processing', 'completed', 'failed'))
        ");

        // stuck_state_alerts: Severity must be valid
        DB::statement("
            ALTER TABLE stuck_state_alerts
            ADD CONSTRAINT check_stuck_alert_severity
            CHECK (severity IN ('low', 'medium', 'high', 'critical'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints first
        DB::statement("ALTER TABLE job_executions DROP CONSTRAINT IF EXISTS check_job_execution_status");
        DB::statement("ALTER TABLE stuck_state_alerts DROP CONSTRAINT IF EXISTS check_stuck_alert_severity");

        // Drop tables
        Schema::dropIfExists('stuck_state_alerts');
        Schema::dropIfExists('job_state_tracking');
        Schema::dropIfExists('job_executions');
    }
};
