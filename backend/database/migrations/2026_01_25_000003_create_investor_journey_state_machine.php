<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 FIX (GAP 18-20): Investor Journey State Machine
 *
 * Creates tables for tracking investor journey through investment flow:
 * - investor_journeys: Current journey state per user/company
 * - investor_journey_transitions: Immutable log of all state transitions
 *
 * JOURNEY STATES:
 * 1. initiated     - Investor started viewing company
 * 2. viewing       - Actively viewing company details
 * 3. acknowledging - Reading and accepting risk disclosures
 * 4. reviewing     - Reviewing investment terms and conditions
 * 5. confirming    - Final confirmation before payment
 * 6. processing    - Payment in progress
 * 7. invested      - Successfully invested (terminal state)
 * 8. blocked       - Blocked due to compliance/eligibility (terminal state)
 * 9. abandoned     - Journey abandoned/expired (terminal state)
 *
 * ENFORCEMENT:
 * - Cannot skip states (must follow sequence)
 * - Each transition logged with timestamp and snapshot
 * - Acknowledgements bound to specific journey state
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // TABLE 1: Investor Journeys
        // Current state of investor's journey for each company
        // =====================================================================
        Schema::create('investor_journeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Current journey state
            $table->string('current_state', 30)->default('initiated');
            $table->timestamp('state_entered_at');

            // Journey tracking
            $table->string('journey_token', 64)->unique(); // Unique token for this journey
            $table->timestamp('journey_started_at');
            $table->timestamp('journey_completed_at')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->string('completion_type', 20)->nullable(); // invested, blocked, abandoned

            // Snapshot binding (GAP 20)
            $table->unsignedBigInteger('platform_snapshot_id')->nullable();
            $table->unsignedBigInteger('investment_snapshot_id')->nullable();
            $table->timestamp('snapshot_bound_at')->nullable();

            // Acknowledgement tracking
            $table->json('acknowledged_risks')->nullable(); // Array of acknowledged risk IDs
            $table->timestamp('risks_acknowledged_at')->nullable();
            $table->json('accepted_terms')->nullable(); // Array of accepted term IDs
            $table->timestamp('terms_accepted_at')->nullable();

            // Investment reference (if completed)
            $table->unsignedBigInteger('company_investment_id')->nullable();

            // Blocking info (if blocked)
            $table->string('block_reason')->nullable();
            $table->string('block_code', 50)->nullable();
            $table->timestamp('blocked_at')->nullable();

            // Session info for audit
            $table->string('session_id', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_fingerprint', 100)->nullable();

            // Expiry (journeys expire after inactivity)
            $table->timestamp('expires_at');
            $table->boolean('is_expired')->default(false);

            $table->timestamps();

            // INDEXES
            $table->unique(['user_id', 'company_id', 'journey_token']);
            $table->index(['user_id', 'company_id', 'is_complete']);
            $table->index(['current_state']);
            $table->index(['journey_token']);
            $table->index(['expires_at', 'is_expired']);
        });

        // =====================================================================
        // TABLE 2: Investor Journey Transitions
        // Immutable audit log of all state transitions
        // =====================================================================
        Schema::create('investor_journey_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained('investor_journeys')->onDelete('cascade');

            // Transition details
            $table->string('from_state', 30);
            $table->string('to_state', 30);
            $table->string('transition_type', 30); // advance, block, abandon, timeout

            // Validation at transition
            $table->boolean('was_valid_transition')->default(true);
            $table->string('validation_result', 50)->nullable();

            // Data captured at transition
            $table->json('state_data')->nullable(); // Data specific to this state
            $table->json('acknowledgements_at_transition')->nullable();
            $table->unsignedBigInteger('snapshot_id_at_transition')->nullable();

            // Audit fields
            $table->string('triggered_by', 30); // user_action, system, timeout, admin
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('transitioned_at');

            // INDEXES
            $table->index(['journey_id', 'transitioned_at']);
            $table->index(['from_state', 'to_state']);
        });

        // =====================================================================
        // TABLE 3: Journey Acknowledgement Bindings
        // Links acknowledgements to specific journey states
        // =====================================================================
        Schema::create('journey_acknowledgement_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained('investor_journeys')->onDelete('cascade');

            // What was acknowledged
            $table->string('acknowledgement_type', 50); // risk_disclosure, terms, privacy, investment_terms
            $table->string('acknowledgement_key', 100); // Specific item acknowledged
            $table->string('acknowledgement_version', 20)->nullable(); // Version of the document

            // Journey state when acknowledged
            $table->string('journey_state_at_ack', 30);
            $table->unsignedBigInteger('transition_id')->nullable();

            // Snapshot binding
            $table->unsignedBigInteger('snapshot_id_at_ack')->nullable();
            $table->json('snapshot_hash')->nullable(); // Hash of snapshot content for integrity

            // Proof of acknowledgement
            $table->text('acknowledgement_text')->nullable(); // Exact text user saw
            $table->boolean('explicit_consent')->default(false); // User explicitly clicked/checked
            $table->timestamp('acknowledged_at');

            // Audit
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // INDEXES
            $table->index(['journey_id', 'acknowledgement_type']);
            $table->unique(['journey_id', 'acknowledgement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_acknowledgement_bindings');
        Schema::dropIfExists('investor_journey_transitions');
        Schema::dropIfExists('investor_journeys');
    }
};
