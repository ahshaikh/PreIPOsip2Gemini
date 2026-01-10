<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 - MIGRATION 6/6: Create Disclosure Approvals Table
 *
 * PURPOSE:
 * Creates the workflow tracking system for disclosure approval processes.
 * Records every approval request, decision, and state change for regulatory
 * audit and compliance reporting.
 *
 * KEY CONCEPTS:
 * - WORKFLOW HISTORY: Complete audit trail of approval lifecycle
 * - MULTI-APPROVER READY: Designed for future multi-stage approvals
 * - REVOCATION SUPPORT: Can revoke approvals if issues discovered
 * - REGULATORY REPORTING: Tracks compliance with SEBI approval timelines
 *
 * WORKFLOW EXAMPLE:
 * 1. Company submits "Business Model" disclosure (status: pending)
 * 2. Admin reviews, requests clarifications (status: clarification_required)
 * 3. Company answers, resubmits (status: pending)
 * 4. Admin approves (status: approved)
 * 5. Later, discovers issue, revokes approval (status: revoked)
 * 6. Company fixes, resubmits (new approval record created)
 *
 * DIFFERENCE FROM company_disclosures.status:
 * - company_disclosures.status = CURRENT state of disclosure
 * - disclosure_approvals = HISTORY of all approval attempts
 *
 * EXAMPLE RECORDS for One Disclosure:
 * | id | disclosure_id | status                 | created_at |
 * |----|---------------|------------------------|------------|
 * | 1  | 42            | pending                | 2024-01-10 |
 * | 2  | 42            | clarification_required | 2024-01-12 |
 * | 3  | 42            | pending                | 2024-01-15 |
 * | 4  | 42            | approved               | 2024-01-18 |
 *
 * RELATION TO OTHER TABLES:
 * - company_disclosures: Parent disclosure
 * - disclosure_versions: Version that was approved (if approved)
 * - disclosure_clarifications: Linked clarifications (if status=clarification_required)
 * - users: Admin who made the decision
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disclosure_approvals', function (Blueprint $table) {
            $table->id();

            // =====================================================================
            // PARENT REFERENCES
            // =====================================================================

            $table->foreignId('company_disclosure_id')
                ->constrained('company_disclosures')
                ->cascadeOnDelete()
                ->comment('Disclosure being approved/reviewed');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Denormalized for query performance');

            $table->foreignId('disclosure_module_id')
                ->constrained('disclosure_modules')
                ->restrictOnDelete()
                ->comment('Denormalized for query performance');

            // =====================================================================
            // APPROVAL REQUEST
            // =====================================================================

            $table->enum('request_type', [
                'initial_submission',  // Company's first submission
                'resubmission',       // After clarifications answered
                'revision',           // Voluntary update by company
                'correction'          // Emergency fix after approval
            ])->comment('Type of approval request');

            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('CompanyUser who requested approval');

            $table->timestamp('requested_at')
                ->comment('When approval was requested');

            $table->text('submission_notes')->nullable()
                ->comment('Company notes explaining submission/changes');

            // =====================================================================
            // VERSION LINKAGE
            // =====================================================================

            $table->unsignedInteger('disclosure_version_number')
                ->comment('Version number at time of this approval request');

            $table->foreignId('disclosure_version_id')->nullable()
                ->constrained('disclosure_versions')
                ->nullOnDelete()
                ->comment('Approved version (set when status=approved)');

            // =====================================================================
            // APPROVAL DECISION
            // =====================================================================

            $table->enum('status', [
                'pending',                 // Waiting for admin review
                'under_review',            // Admin actively reviewing
                'clarification_required',  // Admin needs more info
                'approved',                // Admin approved
                'rejected',                // Admin rejected
                'revoked'                  // Previously approved, now revoked
            ])->default('pending')
                ->comment('Current status of this approval attempt');

            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who reviewed/decided');

            $table->timestamp('review_started_at')->nullable()
                ->comment('When admin started review');

            $table->timestamp('review_completed_at')->nullable()
                ->comment('When admin completed review (approved/rejected/clarification)');

            $table->unsignedInteger('review_duration_minutes')->nullable()
                ->comment('How long review took (for SLA tracking)');

            // =====================================================================
            // DECISION DETAILS
            // =====================================================================

            $table->text('decision_notes')->nullable()
                ->comment('Admin explanation of decision (required for rejection)');

            $table->json('checklist_completed')->nullable()
                ->comment('Approval checklist items verified: [{"item":"Verify revenue","checked":true,"notes":"Bank statement provided"}]');

            $table->json('identified_issues')->nullable()
                ->comment('Issues found during review: [{"field":"revenue_streams","issue":"Missing Q4 data","severity":"high"}]');

            // =====================================================================
            // CLARIFICATION TRACKING
            // =====================================================================

            $table->unsignedInteger('clarifications_requested')->default(0)
                ->comment('Number of clarifications requested during this approval cycle');

            $table->timestamp('clarifications_due_date')->nullable()
                ->comment('Deadline for company to answer clarifications');

            $table->boolean('all_clarifications_answered')->default(false)
                ->comment('Whether all clarifications have been answered');

            // =====================================================================
            // APPROVAL CONDITIONS
            // =====================================================================
            // Special conditions or restrictions on the approval

            $table->json('approval_conditions')->nullable()
                ->comment('Conditions attached to approval: [{"condition":"Must update quarterly","due":"2024-04-01"}]');

            $table->timestamp('conditional_approval_expires_at')->nullable()
                ->comment('When conditional approval expires (if applicable)');

            // =====================================================================
            // REVOCATION (Emergency Override)
            // =====================================================================

            $table->boolean('is_revoked')->default(false)
                ->comment('Whether this approval has been revoked');

            $table->foreignId('revoked_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who revoked the approval');

            $table->timestamp('revoked_at')->nullable()
                ->comment('When approval was revoked');

            $table->text('revocation_reason')->nullable()
                ->comment('REQUIRED: Reason for revoking approval (regulatory requirement)');

            $table->boolean('investor_notification_required')->default(false)
                ->comment('Whether investors must be notified of revocation');

            // =====================================================================
            // COMPLIANCE & SLA TRACKING
            // =====================================================================

            $table->timestamp('sla_due_date')->nullable()
                ->comment('SLA deadline for admin to complete review (typically 5 business days)');

            $table->boolean('sla_breached')->default(false)
                ->comment('Whether SLA was breached');

            $table->unsignedInteger('business_days_to_review')->nullable()
                ->comment('Business days from submission to decision (SLA metric)');

            $table->string('sebi_compliance_status', 50)->nullable()
                ->comment('SEBI compliance flag: "compliant", "delayed", "non_compliant"');

            // =====================================================================
            // MULTI-APPROVER SUPPORT (Future)
            // =====================================================================
            // Designed for future multi-stage approval workflows

            $table->unsignedInteger('approval_stage')->default(1)
                ->comment('Approval stage (1=primary review, 2=secondary, etc.) - Future use');

            $table->json('approval_chain')->nullable()
                ->comment('Multi-approver chain (Future): [{"stage":1,"approver_id":5,"status":"approved","date":"2024-01-18"}]');

            // =====================================================================
            // INTERNAL TRACKING
            // =====================================================================

            $table->text('internal_notes')->nullable()
                ->comment('Admin-only internal notes (not visible to company)');

            $table->unsignedInteger('reminder_count')->default(0)
                ->comment('Reminders sent to admin for pending review');

            $table->timestamp('last_reminder_at')->nullable()
                ->comment('When last reminder sent to admin');

            // =====================================================================
            // AUDIT TRAIL
            // =====================================================================

            $table->string('requested_by_ip', 45)->nullable()
                ->comment('IP address when approval requested');

            $table->string('reviewed_by_ip', 45)->nullable()
                ->comment('IP address when review completed');

            $table->text('requested_by_user_agent')->nullable()
                ->comment('User agent when approval requested');

            // =====================================================================
            // TIMESTAMPS
            // =====================================================================

            $table->timestamps();
            $table->softDeletes();

            // =====================================================================
            // INDEXES
            // =====================================================================

            $table->index('status', 'idx_approvals_status');
            $table->index(['company_disclosure_id', 'created_at'], 'idx_approvals_disclosure_timeline');
            $table->index(['company_id', 'status'], 'idx_approvals_company_status');
            $table->index(['reviewed_by', 'status'], 'idx_approvals_reviewer_status');
            $table->index('sla_due_date', 'idx_approvals_sla');
            $table->index(['sla_breached', 'status'], 'idx_approvals_sla_breach');
            $table->index('is_revoked', 'idx_approvals_revoked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disclosure_approvals');
    }
};
