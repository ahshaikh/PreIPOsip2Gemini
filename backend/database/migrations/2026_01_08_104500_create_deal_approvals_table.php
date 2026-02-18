<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// * Migration: Create deal_approvals Table
// *
// * PROTOCOL 1 ANALYSIS:
// * - Error indicates deal_approvals table EXISTS but missing columns
// * - However, no migration creates this table in repo
// * - Table was likely created manually or in uncommitted migration
// *
// * SOLUTION:
// * - Create complete deal_approvals table with all required columns
// * - Include approved_at, approved_by for analytics queries
// * - Follow platform's approval workflow pattern (matches withdrawals, campaigns)
// *
// * PURPOSE:
// * - Track deal approval workflow from submission to publication
// * - Enable multi-stage approval process with SLA tracking
// * - Support approval analytics and compliance reporting
// * - Integrate with company version snapshots at approval
// *
// * SCHEMA RATIONALE:
// * - deal_id: Links to deals table for deal details
// * - submitted_by: Company user requesting approval
// * - reviewed_by: Admin who reviewed
// * - approved_by/rejected_by: Admin who made final decision
// * - status: pending/under_review/approved/rejected
// * - notes: Reason for rejection or approval comments

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')
                ->constrained('deals')
                ->onDelete('cascade');

            $table->enum('status', [
                'draft',
                'pending_review',
                'under_review',
                'approved',
                'rejected',
                'published',
                'archived'
            ])->default('draft');

            // Workflow timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // User tracking
            $table->foreignId('submitter_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewer_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approver_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('rejected_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('publisher_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Notes & decisions
            $table->text('submission_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('checklist_items')->nullable();

            // SLA & analytics
            $table->integer('sla_hours')->default(168);
            $table->timestamp('sla_deadline')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->integer('days_pending')->nullable();

            // Snapshot linkage
            $table->foreignId('company_version_id')->nullable()
                ->constrained('company_versions')
                ->nullOnDelete();

            $table->boolean('snapshot_created')->default(false);

            // Priority & metadata
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                ->default('normal');

            $table->boolean('is_expedited')->default(false);
            $table->text('expedited_reason')->nullable();

            $table->json('compliance_checks')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Purposeful composite indexes
            $table->index(['status', 'sla_deadline']);
            $table->index(['status', 'submitted_at']);
            $table->index(['deal_id', 'status']);
            $table->index(['status', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_approvals');
    }
};

