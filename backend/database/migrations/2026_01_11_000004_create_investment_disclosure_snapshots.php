<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 STABILIZATION - Issue 5: Investor Snapshot Closure
 *
 * PROBLEM:
 * When investor buys, no immutable record of what disclosures they saw.
 * If disclosure updated later, can't prove what was visible at purchase.
 *
 * SURGICAL FIX:
 * Capture complete snapshot at purchase: disclosures, metrics, flags.
 * Bind investment to exact versions investor saw.
 * Immutable and queryable for dispute resolution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_disclosure_snapshots', function (Blueprint $table) {
            $table->id();

            // Investment binding
            $table->unsignedBigInteger('investment_id')->nullable()
                ->comment('Investment this snapshot is bound to (nullable for pre-purchase snapshots)');
            $table->unsignedBigInteger('user_id')->comment('Investor who saw this snapshot');
            $table->unsignedBigInteger('company_id')->comment('Company being invested in');

            // Snapshot metadata
            $table->timestamp('snapshot_timestamp')->useCurrent()
                ->comment('Exact moment snapshot was captured');
            $table->string('snapshot_trigger', 50)->default('investment_purchase')
                ->comment('What triggered snapshot: investment_purchase, company_view, etc.');

            // Complete disclosure snapshot
            $table->json('disclosure_snapshot')->comment(
                'All company disclosures visible at snapshot time: ' .
                '{disclosure_id: {module, status, data, version_id}}'
            );

            // Platform context snapshot
            $table->json('metrics_snapshot')->nullable()->comment(
                'Platform metrics at snapshot time: {completeness, financial_band, etc.}'
            );
            $table->json('risk_flags_snapshot')->nullable()->comment(
                'Active risk flags at snapshot time: [{flag_type, severity, description}]'
            );
            $table->json('valuation_context_snapshot')->nullable()->comment(
                'Valuation context at snapshot time: {peer_median, context_band, etc.}'
            );

            // Version mapping (critical for reproducibility)
            $table->json('disclosure_versions_map')->comment(
                'Exact version IDs investor saw: {disclosure_id: version_id}'
            );

            // Data state indicators
            $table->boolean('was_under_review')->default(false)
                ->comment('Were any disclosures under admin review at snapshot time');
            $table->string('company_lifecycle_state', 50)->nullable()
                ->comment('Company lifecycle state at snapshot time');
            $table->boolean('buying_enabled_at_snapshot')->default(true)
                ->comment('Was buying enabled when snapshot taken');

            // Investor decision context
            $table->text('investor_notes')->nullable()
                ->comment('Investor can add notes about what influenced their decision');
            $table->json('viewed_documents')->nullable()
                ->comment('Which disclosure attachments investor opened');

            // Audit trail
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();

            // Immutability flag
            $table->boolean('is_immutable')->default(true)
                ->comment('Once captured, snapshot CANNOT be modified');
            $table->timestamp('locked_at')->nullable()
                ->comment('When snapshot was locked (after investment confirmed)');

            $table->timestamps();

            // Indexes
            $table->index('investment_id');
            $table->index(['user_id', 'company_id']);
            $table->index('snapshot_timestamp');
            $table->index(['snapshot_trigger', 'snapshot_timestamp']);

            // Foreign keys
            // $table->foreign('investment_id')->references('id')->on('investments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Create index for fast investor history queries
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {
            $table->index(['user_id', 'snapshot_timestamp'], 'idx_user_snapshot_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_disclosure_snapshots');
    }
};
