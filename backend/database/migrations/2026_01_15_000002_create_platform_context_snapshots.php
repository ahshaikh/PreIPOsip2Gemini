<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PHASE 4 STABILIZATION - Issue 1: Canonical Platform Context Snapshot
     *
     * PURPOSE:
     * Create single, authoritative snapshot of investor-visible platform context.
     * Time-bound, immutable, and auditable.
     *
     * WHAT IS CAPTURED:
     * - Company governance state (lifecycle, suspension, buying_enabled)
     * - Tier approval status
     * - Platform restrictions (frozen, under_investigation)
     * - Risk assessments and warnings
     * - Calculated metrics (risk score, compliance score)
     * - Admin notes and platform judgments
     *
     * IMMUTABILITY:
     * - Snapshot is locked after creation
     * - Recalculations create NEW snapshots, never mutate existing
     * - Each investment references specific snapshot ID
     *
     * RELATIONSHIP:
     * - One-to-many with investments (many investments can share same snapshot)
     * - Snapshot taken when: company data changes, tier approved, admin action, scheduled refresh
     */
    public function up(): void
    {
        Schema::create('platform_context_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            // Snapshot metadata
            $table->timestamp('snapshot_at')->index()
                ->comment('When this snapshot was taken');
            $table->string('snapshot_trigger', 100)
                ->comment('What triggered snapshot: company_update, tier_approval, admin_action, scheduled_refresh');
            $table->unsignedBigInteger('triggered_by_user_id')->nullable()
                ->comment('User ID if triggered by user action');
            $table->string('actor_type', 20)->default('system')
                ->comment('Actor type: system, admin, automated_job');

            // Governance state (from Phase 2)
            $table->string('lifecycle_state', 50)
                ->comment('Company lifecycle state at snapshot time');
            $table->boolean('buying_enabled')->default(true)
                ->comment('Whether buying was enabled at snapshot time');
            $table->integer('governance_state_version')
                ->comment('Governance state version from companies table');
            $table->boolean('is_suspended')->default(false);
            $table->string('suspension_reason', 500)->nullable();

            // Tier approvals
            $table->boolean('tier_1_approved')->default(false);
            $table->timestamp('tier_1_approved_at')->nullable();
            $table->boolean('tier_2_approved')->default(false);
            $table->timestamp('tier_2_approved_at')->nullable();
            $table->boolean('tier_3_approved')->default(false);
            $table->timestamp('tier_3_approved_at')->nullable();

            // Platform restrictions (from Phase 3)
            $table->boolean('is_frozen')->default(false)
                ->comment('Disclosure freeze active');
            $table->string('freeze_reason', 500)->nullable();
            $table->boolean('is_under_investigation')->default(false);
            $table->string('investigation_reason', 500)->nullable();

            // Risk and compliance (calculated by platform)
            $table->decimal('platform_risk_score', 5, 2)->nullable()
                ->comment('Platform-calculated risk score (0-100)');
            $table->string('risk_level', 20)->nullable()
                ->comment('low, medium, high, critical');
            $table->decimal('compliance_score', 5, 2)->nullable()
                ->comment('Platform-calculated compliance score (0-100)');
            $table->json('risk_flags')->nullable()
                ->comment('Array of active risk flags');

            // Material changes
            $table->boolean('has_material_changes')->default(false)
                ->comment('Whether material changes exist since last investor snapshot');
            $table->json('material_changes_summary')->nullable()
                ->comment('Summary of material changes');
            $table->timestamp('last_material_change_at')->nullable();

            // Admin notes and judgments (from Phase 5 Admin vs Platform Attribution)
            $table->text('admin_notes')->nullable()
                ->comment('Admin notes about company at snapshot time');
            $table->json('admin_judgments')->nullable()
                ->comment('Explicit admin judgments vs automated platform analysis');

            // Full context snapshot (JSON)
            $table->json('full_context_data')
                ->comment('Complete platform context at snapshot time');

            // Immutability controls
            $table->boolean('is_locked')->default(true)
                ->comment('Snapshot is immutable once locked');
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('supersedes_snapshot_id')->nullable()
                ->comment('Previous snapshot ID that this one supersedes');

            // Validity period
            $table->timestamp('valid_from')->useCurrent()
                ->comment('Snapshot is valid from this time');
            $table->timestamp('valid_until')->nullable()->index()
                ->comment('Snapshot is valid until this time (null = current)');
            $table->boolean('is_current')->default(true)->index()
                ->comment('Whether this is the current active snapshot');

            // Audit trail
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('audit_metadata')->nullable()
                ->comment('Additional audit information');

            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')->on('companies')
                ->onDelete('cascade');

            $table->foreign('triggered_by_user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->foreign('supersedes_snapshot_id')
                ->references('id')->on('platform_context_snapshots')
                ->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'is_current']);
            $table->index(['company_id', 'snapshot_at']);
            $table->index(['valid_from', 'valid_until']);
            $table->index('snapshot_trigger');
        });

        // Add snapshot reference to investments table
        Schema::table('investments', function (Blueprint $table) {
            $table->unsignedBigInteger('platform_context_snapshot_id')->nullable()->after('id')
                ->comment('Reference to platform context snapshot at investment time');

            $table->foreign('platform_context_snapshot_id')
                ->references('id')->on('platform_context_snapshots')
                ->onDelete('set null');

            $table->index('platform_context_snapshot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropForeign(['platform_context_snapshot_id']);
            $table->dropColumn('platform_context_snapshot_id');
        });

        Schema::dropIfExists('platform_context_snapshots');
    }
};
