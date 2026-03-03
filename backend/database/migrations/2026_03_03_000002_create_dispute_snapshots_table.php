<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Create dispute_snapshots table for immutable state capture
 *
 * PURPOSE:
 * At dispute filing, capture the complete state of:
 * - The disputable entity (payment amount, investment shares, etc.)
 * - User's wallet state
 * - Related transactions
 * - System configuration at that moment
 *
 * This creates an immutable record for:
 * - Regulatory defense
 * - Audit trail
 * - Fair dispute resolution (what did user see?)
 *
 * IMMUTABILITY ENFORCEMENT:
 * - Database trigger prevents any UPDATE operations
 * - Hash column enables integrity verification
 * - No soft deletes - records are permanent
 *
 * USED BY:
 * - DisputeSnapshotService: Creates snapshots at filing
 * - SnapshotIntegrityService: Verifies hash integrity
 * - Admin dispute review panel
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispute_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('disputes')->onDelete('cascade');

            // Snapshot of the disputable entity
            $table->json('disputable_snapshot')
                ->comment('Complete state of Payment/Investment/Withdrawal/etc at filing time');

            // Snapshot of user wallet state
            $table->json('wallet_snapshot')
                ->comment('User wallet balances at filing time');

            // Snapshot of related transactions (ledger entries, etc.)
            $table->json('related_transactions_snapshot')
                ->comment('Related ledger entries, bonus transactions, etc.');

            // Snapshot of system state (settings, configs)
            $table->json('system_state_snapshot')
                ->comment('Relevant system settings at filing time');

            // Integrity hash - SHA256 of all snapshot data
            $table->string('integrity_hash', 64)
                ->comment('SHA256 hash for tamper detection');

            // Metadata
            $table->foreignId('captured_by_user_id')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Admin who triggered snapshot (null if auto-captured)');
            $table->string('capture_trigger')
                ->comment('What triggered capture: dispute_filed, admin_request, auto_escalation');

            $table->timestamp('created_at')->useCurrent();
            // No updated_at - snapshots are immutable

            // Indexes
            $table->unique('dispute_id'); // One snapshot per dispute
            $table->index('integrity_hash');
            $table->index('capture_trigger');
        });

        // Create trigger to prevent updates (MySQL)
        DB::unprepared("
            CREATE TRIGGER dispute_snapshots_prevent_update
            BEFORE UPDATE ON dispute_snapshots
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Dispute snapshots are immutable. Updates are not allowed.';
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop trigger first
        DB::unprepared("DROP TRIGGER IF EXISTS dispute_snapshots_prevent_update");

        Schema::dropIfExists('dispute_snapshots');
    }
};
