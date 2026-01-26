<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 FIX (GAP 13): Platform Context Maker-Checker Tables
 *
 * Creates tables for dual-control approval workflow:
 * - platform_context_approval_requests: Pending and historical approval requests
 * - platform_context_approval_logs: Audit trail for all actions
 *
 * Enforcement: Critical platform decisions require two separate admins.
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // TABLE 1: Platform Context Approval Requests
        // Stores maker-checker workflow state
        // =====================================================================
        Schema::create('platform_context_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Action type (what change is being requested)
            $table->string('action_type', 50);
            // Status: pending_approval, approved, rejected, expired, cancelled
            $table->string('status', 30)->default('pending_approval');

            // MAKER INFORMATION (who initiated the change)
            $table->foreignId('maker_user_id')->constrained('users');
            $table->string('maker_role', 50);
            $table->timestamp('initiated_at')->useCurrent();
            $table->text('maker_reason'); // Why this change is needed (REQUIRED)
            $table->string('maker_ip', 45)->nullable();
            $table->text('maker_user_agent')->nullable();

            // CHECKER INFORMATION (who approved/rejected)
            $table->foreignId('checker_user_id')->nullable()->constrained('users');
            $table->string('checker_role', 50)->nullable();
            $table->string('checker_decision', 20)->nullable(); // approved, rejected
            $table->text('checker_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('checker_ip', 45)->nullable();
            $table->text('checker_user_agent')->nullable();

            // CHANGE DATA
            $table->json('proposed_changes'); // What will change
            $table->json('current_state'); // Snapshot of state before change
            $table->json('supporting_data')->nullable(); // Evidence/documentation

            // EXPIRY (requests expire if not acted upon)
            // Note: Application sets actual expiry; useCurrent() satisfies MySQL NOT NULL requirement
            $table->timestamp('expires_at')->useCurrent();
            $table->boolean('is_expired')->default(false);

            // AUDIT
            $table->timestamps();

            // INDEXES
            $table->index(['company_id', 'status']);
            $table->index(
                ['company_id', 'action_type', 'status'],
                'pc_approval_company_action_status_idx'
            );
            $table->index(['maker_user_id']);
            $table->index(['checker_user_id']);
            $table->index(['status', 'expires_at']);
        });

        // =====================================================================
        // TABLE 2: Platform Context Approval Logs
        // Immutable audit trail for all maker-checker actions
        // =====================================================================
        Schema::create('platform_context_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('platform_context_approval_requests');

            // Action performed: initiated, approved, rejected, expired, cancelled
            $table->string('action', 30);

            // Who performed the action
            $table->foreignId('actor_user_id')->constrained('users');
            $table->string('actor_role', 50);

            // Context data
            $table->json('context')->nullable();

            // Audit fields
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // INDEXES
            $table->index(['approval_request_id']);
            $table->index(['actor_user_id']);
            $table->index(['action']);
        });

        // =====================================================================
        // ALTER: Add approval_request_id to platform_governance_log
        // Links governance actions to their approval requests
        // =====================================================================
        if (Schema::hasTable('platform_governance_log')) {
            Schema::table('platform_governance_log', function (Blueprint $table) {
                if (!Schema::hasColumn('platform_governance_log', 'approval_request_id')) {
                    $table->unsignedBigInteger('approval_request_id')->nullable()->after('decided_by');
                    $table->index('approval_request_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Remove column from platform_governance_log
        if (Schema::hasTable('platform_governance_log')) {
            Schema::table('platform_governance_log', function (Blueprint $table) {
                if (Schema::hasColumn('platform_governance_log', 'approval_request_id')) {
                    $table->dropIndex(['approval_request_id']);
                    $table->dropColumn('approval_request_id');
                }
            });
        }

        Schema::dropIfExists('platform_context_approval_logs');
        Schema::dropIfExists('platform_context_approval_requests');
    }
};
