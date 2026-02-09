<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISCLOSURE TIMELINE EVENTS - Append-Only Audit Trail
 *
 * PURPOSE:
 * Creates immutable timeline for disclosure threads (like GitHub PR timeline).
 * Every action in the disclosure lifecycle is recorded as an event.
 *
 * EVENT TYPES:
 * - submission: Company submits/resubmits disclosure
 * - clarification: Platform requests additional information
 * - response: Company responds to clarification
 * - approval: Platform approves disclosure
 * - status_change: Status transitions (system-generated)
 *
 * IMMUTABILITY PRINCIPLE:
 * - No updates allowed
 * - No deletes allowed
 * - Corrections are new events
 * - Complete audit trail preserved
 *
 * RELATION TO OTHER TABLES:
 * - company_disclosures: Parent thread
 * - disclosure_documents: Attachments for this event
 * - disclosure_clarifications: Referenced by clarification events
 * - users: Actor who created event
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disclosure_events', function (Blueprint $table) {
            $table->id();

            // =========================================================================
            // PARENT THREAD
            // =========================================================================
            $table->foreignId('company_disclosure_id')
                ->constrained('company_disclosures')
                ->cascadeOnDelete()
                ->comment('Disclosure thread this event belongs to');

            // =========================================================================
            // EVENT CLASSIFICATION
            // =========================================================================
            $table->enum('event_type', [
                'submission',       // Initial or resubmission
                'clarification',    // Platform requests info
                'response',         // Company responds
                'approval',         // Platform approves
                'status_change',    // Status transition
                'rejection'         // Platform rejects (rare, prefer clarification)
            ])->comment('Type of timeline event');

            // =========================================================================
            // ACTOR INFORMATION
            // =========================================================================
            // Using morphs() which creates actor_type (varchar) and actor_id columns
            // actor_type will be: 'App\Models\CompanyUser', 'App\Models\User', or NULL for system
            $table->morphs('actor', 'idx_disclosure_events_actor');

            $table->string('actor_name', 255)
                ->comment('Cached name for display (denormalized for performance)');

            // =========================================================================
            // EVENT CONTENT
            // =========================================================================
            $table->text('message')->nullable()
                ->comment('Event message or description');

            $table->json('metadata')->nullable()
                ->comment('Additional structured data: clarification_id, status transitions, etc');

            // =========================================================================
            // REFERENCES
            // =========================================================================
            $table->foreignId('disclosure_clarification_id')->nullable()
                ->constrained('disclosure_clarifications')
                ->nullOnDelete()
                ->comment('Link to clarification if event_type=clarification');

            // =========================================================================
            // STATUS TRANSITIONS (for status_change events)
            // =========================================================================
            $table->string('status_from', 50)->nullable()
                ->comment('Previous status (for status_change events)');

            $table->string('status_to', 50)->nullable()
                ->comment('New status (for status_change events)');

            // =========================================================================
            // AUDIT TRAIL
            // =========================================================================
            $table->string('ip_address', 45)->nullable()
                ->comment('IP address of actor');

            $table->text('user_agent')->nullable()
                ->comment('User agent string');

            $table->timestamp('created_at')
                ->comment('When event occurred (immutable)');

            // =========================================================================
            // INDEXES
            // =========================================================================
            $table->index(['company_disclosure_id', 'created_at'], 'idx_disclosure_events_thread_time');
            $table->index(['event_type', 'created_at'], 'idx_disclosure_events_type_time');
            $table->index('actor_type', 'idx_disclosure_events_actor_type');
        });

        // IMPORTANT: Disable updates and deletes at application level
        // These should only be enforced by model policies and service layer
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disclosure_events');
    }
};
