<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PHASE 3 HARDENING - Issue 3: Clarification Time Semantics
     *
     * PURPOSE:
     * Add platform-owned timeout rules for clarification responses.
     * Prevents disclosures from being stuck indefinitely.
     *
     * TIMEOUT RULES:
     * - Issuer response: 5 business days from clarification asked
     * - Admin response: 3 business days from answer submitted
     * - Expired clarifications: Auto-escalate or mark as stale
     *
     * FIELDS ADDED:
     * - issuer_response_due_at: When issuer must respond by
     * - issuer_response_overdue: Boolean flag for expired issuer response
     * - admin_review_due_at: When admin must review answer by
     * - admin_review_overdue: Boolean flag for expired admin review
     * - escalated_at: When clarification was escalated due to timeout
     * - escalation_reason: Why it was escalated
     * - is_expired: Clarification expired, no longer active
     */
    public function up(): void
    {
        Schema::table('disclosure_clarifications', function (Blueprint $table) {
            // Issuer response timeout
            $table->timestamp('issuer_response_due_at')->nullable()->after('asked_at')
                ->comment('Deadline for issuer to respond (5 business days from asked_at)');
            $table->boolean('issuer_response_overdue')->default(false)->after('issuer_response_due_at')
                ->comment('True if issuer has not responded by due date');

            // Admin review timeout
            $table->timestamp('admin_review_due_at')->nullable()->after('answered_at')
                ->comment('Deadline for admin to review answer (3 business days from answered_at)');
            $table->boolean('admin_review_overdue')->default(false)->after('admin_review_due_at')
                ->comment('True if admin has not reviewed by due date');

            // Escalation tracking
            $table->timestamp('escalated_at')->nullable()->after('admin_review_overdue')
                ->comment('When clarification was escalated due to timeout');
            $table->string('escalation_reason', 500)->nullable()->after('escalated_at')
                ->comment('Why clarification was escalated');
            $table->unsignedBigInteger('escalated_to_admin_id')->nullable()->after('escalation_reason')
                ->comment('Admin user ID clarification was escalated to');

            // Expiry tracking
            $table->boolean('is_expired')->default(false)->after('escalated_to_admin_id')
                ->comment('Clarification expired, no longer active');
            $table->timestamp('expired_at')->nullable()->after('is_expired')
                ->comment('When clarification was marked as expired');
            $table->string('expiry_reason', 500)->nullable()->after('expired_at')
                ->comment('Why clarification expired');

            // Add indexes for performance
            $table->index('issuer_response_due_at');
            $table->index('admin_review_due_at');
            $table->index(['issuer_response_overdue', 'status']);
            $table->index(['admin_review_overdue', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disclosure_clarifications', function (Blueprint $table) {
            $table->dropIndex(['admin_review_overdue', 'status']);
            $table->dropIndex(['issuer_response_overdue', 'status']);
            $table->dropIndex(['admin_review_due_at']);
            $table->dropIndex(['issuer_response_due_at']);

            $table->dropColumn([
                'issuer_response_due_at',
                'issuer_response_overdue',
                'admin_review_due_at',
                'admin_review_overdue',
                'escalated_at',
                'escalation_reason',
                'escalated_to_admin_id',
                'is_expired',
                'expired_at',
                'expiry_reason',
            ]);
        });
    }
};
