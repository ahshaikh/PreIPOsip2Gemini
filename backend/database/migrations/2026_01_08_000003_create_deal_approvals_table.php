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
    ])->default('draft'); // indexed via composites

    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('review_started_at')->nullable();
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamp('published_at')->nullable();

    $table->foreignId('submitter_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('publisher_id')->nullable()->constrained('users')->nullOnDelete();

    $table->text('submission_notes')->nullable();
    $table->text('review_notes')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->json('checklist_items')->nullable();

    $table->integer('sla_hours')->default(168);
    $table->timestamp('sla_deadline')->nullable();
    $table->boolean('is_overdue')->default(false);
    $table->integer('days_pending')->nullable();

    $table->foreignId('company_version_id')->nullable()
        ->constrained('company_versions')
        ->nullOnDelete();

    $table->boolean('snapshot_created')->default(false);

    $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
        ->default('normal');

    $table->boolean('is_expedited')->default(false);
    $table->text('expedited_reason')->nullable();

    $table->json('compliance_checks')->nullable();
    $table->json('metadata')->nullable();

    $table->timestamps();
    $table->softDeletes();

    // Purposeful indexes only
    $table->index(['status', 'sla_deadline']);
    $table->index(['status', 'submitted_at']);
    $table->index(['deal_id', 'status']);
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
