<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deal Approval Workflow (FIX 49)
     *
     * Purpose:
     * - Track deal approval workflow from submission to publication
     * - Enable multi-stage approval process with SLA tracking
     * - Support approval analytics and compliance reporting
     * - Integrate with company version snapshots at approval
     */
    public function up(): void
    {
        Schema::create('deal_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->onDelete('cascade');

            // Workflow status
            $table->enum('status', [
                'draft',
                'pending_review',
                'under_review',
                'approved',
                'rejected',
                'published',
                'archived'
            ])->default('draft')->index();

            // Workflow tracking
            $table->timestamp('submitted_at')->nullable()->comment('When deal was submitted for review');
            $table->timestamp('review_started_at')->nullable()->comment('When review began');
            $table->timestamp('reviewed_at')->nullable()->comment('When review completed');
            $table->timestamp('published_at')->nullable()->comment('When deal was published');

            // User tracking
            $table->foreignId('submitter_id')->nullable()->constrained('users')->onDelete('set null')->comment('User who submitted for approval');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null')->comment('Admin reviewer');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null')->comment('Final approver');
            $table->foreignId('publisher_id')->nullable()->constrained('users')->onDelete('set null')->comment('User who published');

            // Review details
            $table->text('submission_notes')->nullable()->comment('Notes from submitter');
            $table->text('review_notes')->nullable()->comment('Notes from reviewer');
            $table->text('rejection_reason')->nullable()->comment('Reason if rejected');
            $table->json('checklist_items')->nullable()->comment('Approval checklist results');

            // SLA tracking
            $table->integer('sla_hours')->default(168)->comment('SLA target in hours (default 7 days)');
            $table->timestamp('sla_deadline')->nullable()->comment('Calculated SLA deadline');
            $table->boolean('is_overdue')->default(false)->index()->comment('True if past SLA deadline');
            $table->integer('days_pending')->nullable()->comment('Days in pending_review status');

            // Approval impact (FIX 35 - Snapshot creation)
            $table->foreignId('company_version_id')->nullable()->constrained('company_versions')->onDelete('set null')->comment('Immutable snapshot created on approval');
            $table->boolean('snapshot_created')->default(false)->comment('True if approval snapshot was created');

            // Priority & flags
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->index();
            $table->boolean('is_expedited')->default(false)->comment('Expedited approval flag');
            $table->text('expedited_reason')->nullable();

            // Compliance & metadata
            $table->json('compliance_checks')->nullable()->comment('Compliance validation results');
            $table->json('metadata')->nullable()->comment('Additional contextual data');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('deal_id');
            $table->index('status');
            $table->index('submitter_id');
            $table->index('reviewer_id');
            $table->index('submitted_at');
            $table->index('sla_deadline');
            $table->index(['status', 'sla_deadline']); // For queue queries
            $table->index(['status', 'submitted_at']); // For analytics
            $table->index(['deal_id', 'status']);
            $table->index('is_overdue');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_approvals');
    }
};
