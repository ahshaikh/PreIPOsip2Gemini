<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Create dispute_timelines table for append-only event log
 *
 * PURPOSE:
 * Every action on a dispute creates a timeline entry:
 * - Status transitions
 * - Comments from admin/investor
 * - Evidence uploads
 * - Assignment changes
 * - Settlement actions
 *
 * APPEND-ONLY ENFORCEMENT:
 * - Database triggers prevent UPDATE and DELETE operations
 * - Entries can only be created, never modified or removed
 * - This ensures complete audit trail integrity
 *
 * USED BY:
 * - DisputeStateMachine: Records state transitions
 * - DisputeService: Records all dispute actions
 * - Admin/Investor dispute view: Shows complete history
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispute_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('disputes')->onDelete('cascade');

            // Event classification
            $table->string('event_type')
                ->comment('Type: status_change, comment, evidence_added, assigned, escalated, settlement, etc.');

            // Actor information
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->onDelete('set null')
                ->comment('User who performed action');
            $table->string('actor_role')
                ->comment('Role at time of action: admin, investor, system');

            // Event details
            $table->string('title')
                ->comment('Short description: "Status changed to Under Review"');
            $table->text('description')->nullable()
                ->comment('Detailed description or comment content');

            // State transition tracking
            $table->string('old_status')->nullable()
                ->comment('Previous status (for status_change events)');
            $table->string('new_status')->nullable()
                ->comment('New status (for status_change events)');

            // Attachments and evidence
            $table->json('attachments')->nullable()
                ->comment('File paths, URLs, or evidence references');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('Additional event-specific data');

            // Visibility control
            $table->boolean('visible_to_investor')->default(true)
                ->comment('Whether investor can see this entry');
            $table->boolean('is_internal_note')->default(false)
                ->comment('Admin-only internal note');

            $table->timestamp('created_at')->useCurrent();
            // No updated_at - timeline entries are immutable

            // Indexes
            $table->index(['dispute_id', 'created_at']);
            $table->index('event_type');
            $table->index('actor_user_id');
            $table->index('visible_to_investor');
        });

        // Create trigger to prevent updates (MySQL)
        DB::unprepared("
            CREATE TRIGGER dispute_timelines_prevent_update
            BEFORE UPDATE ON dispute_timelines
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Dispute timeline entries are immutable. Updates are not allowed.';
            END
        ");

        // Create trigger to prevent deletes (MySQL)
        DB::unprepared("
            CREATE TRIGGER dispute_timelines_prevent_delete
            BEFORE DELETE ON dispute_timelines
            FOR EACH ROW
            BEGIN
                -- Allow cascade deletes from parent dispute
                -- This is handled by ON DELETE CASCADE constraint
                -- But prevent direct deletes
                IF @allow_cascade_delete IS NULL OR @allow_cascade_delete = 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Dispute timeline entries are append-only. Deletes are not allowed.';
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers first
        DB::unprepared("DROP TRIGGER IF EXISTS dispute_timelines_prevent_update");
        DB::unprepared("DROP TRIGGER IF EXISTS dispute_timelines_prevent_delete");

        Schema::dropIfExists('dispute_timelines');
    }
};
