<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FRESHNESS MODEL - MIGRATION 3/3
 *
 * Creates pillar_vitality_snapshots table for historical vitality tracking.
 *
 * ENUM VOCABULARY (FROZEN - DO NOT ADD SYNONYMS):
 * - pillar: 'governance' | 'financial' | 'legal' | 'operational'
 * - vitality_state: 'healthy' | 'needs_attention' | 'at_risk'
 *
 * VITALITY STATE MEANINGS:
 * - healthy: All artifacts in pillar are 'current'
 * - needs_attention: Any artifact is 'aging' OR exactly 1 is 'stale'/'unstable'
 * - at_risk: 2+ artifacts are 'stale' OR 2+ are 'unstable'
 *
 * PURPOSE:
 * - Point-in-time snapshots for audit trail
 * - Historical vitality tracking per company
 * - Admin evidence for decision justification
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pillar_vitality_snapshots', function (Blueprint $table) {
            $table->id();

            // Company reference
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            // Pillar category (matches disclosure_modules.category)
            $table->enum('pillar', ['governance', 'financial', 'legal', 'operational']);

            // Computed vitality state
            $table->enum('vitality_state', ['healthy', 'needs_attention', 'at_risk']);

            // Freshness breakdown counts
            $table->unsignedInteger('current_count')->default(0);
            $table->unsignedInteger('aging_count')->default(0);
            $table->unsignedInteger('stale_count')->default(0);
            $table->unsignedInteger('unstable_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);

            // Drivers explaining vitality degradation
            // Format: [{ module_code, freshness_state, signal_text, days_info }]
            $table->json('vitality_drivers')
                ->nullable()
                ->comment('Artifacts causing vitality degradation with explanations');

            // When this snapshot was computed
            $table->timestamp('computed_at');

            $table->timestamps();

            // Unique constraint per company + pillar + snapshot time
            $table->unique(['company_id', 'pillar', 'computed_at'], 'idx_pillar_vitality_unique');

            // Index for company dashboard queries
            $table->index(['company_id', 'computed_at'], 'idx_company_vitality_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pillar_vitality_snapshots');
    }
};
