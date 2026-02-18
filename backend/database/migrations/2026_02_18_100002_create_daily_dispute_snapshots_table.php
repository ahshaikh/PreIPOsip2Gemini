<?php

// V-DISPUTE-RISK-2026-002 | Phase 1 - Daily Dispute Aggregation Snapshots

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_dispute_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('set null');

            // Dispute counts
            $table->unsignedInteger('total_disputes')->default(0);
            $table->unsignedInteger('open_disputes')->default(0);
            $table->unsignedInteger('under_investigation_disputes')->default(0);
            $table->unsignedInteger('resolved_disputes')->default(0);
            $table->unsignedInteger('escalated_disputes')->default(0);

            // Chargeback metrics (stored in paise for precision)
            $table->unsignedBigInteger('total_chargeback_count')->default(0);
            $table->unsignedBigInteger('total_chargeback_amount_paise')->default(0);
            $table->unsignedBigInteger('confirmed_chargeback_count')->default(0);
            $table->unsignedBigInteger('confirmed_chargeback_amount_paise')->default(0);

            // Severity breakdown
            $table->unsignedInteger('low_severity_count')->default(0);
            $table->unsignedInteger('medium_severity_count')->default(0);
            $table->unsignedInteger('high_severity_count')->default(0);
            $table->unsignedInteger('critical_severity_count')->default(0);

            // Category breakdown (JSON for flexibility)
            $table->json('category_breakdown')->nullable();

            // Risk metrics
            $table->unsignedInteger('blocked_users_count')->default(0);
            $table->unsignedInteger('high_risk_users_count')->default(0);

            $table->timestamps();

            // Unique constraint: one snapshot per date per plan (null plan = platform-wide)
            $table->unique(['snapshot_date', 'plan_id'], 'daily_dispute_snapshots_unique_idx');

            // Index for date range queries
            $table->index('snapshot_date', 'daily_dispute_snapshots_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_dispute_snapshots');
    }
};
