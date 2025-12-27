<?php
/**
 * [P2.2 FIX]: Add allocation status tracking for queue-based allocation.
 *
 * WHY: Enables async share allocation via queued jobs, preventing HTTP timeout
 * and database lock contention under high concurrency.
 *
 * BEFORE: Allocation happened synchronously in controller (line 286 of InvestmentController)
 * AFTER: Controller dispatches job, returns immediately, job processes allocation
 *
 * Status Flow:
 * - 'pending': Investment created, allocation job not yet processed
 * - 'processing': Job picked up, allocation in progress
 * - 'completed': Shares allocated successfully, UserInvestment records created
 * - 'failed': Allocation failed (insufficient inventory, etc.)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            // [P2.2 FIX]: Track async allocation status
            $table->enum('allocation_status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->after('status');

            // Timestamp for when allocation was completed/failed
            $table->timestamp('allocated_at')->nullable()->after('allocation_status');

            // Error message if allocation failed
            $table->text('allocation_error')->nullable()->after('allocated_at');

            // Index for querying pending allocations
            $table->index('allocation_status');
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropIndex(['allocation_status']);
            $table->dropColumn(['allocation_status', 'allocated_at', 'allocation_error']);
        });
    }
};
