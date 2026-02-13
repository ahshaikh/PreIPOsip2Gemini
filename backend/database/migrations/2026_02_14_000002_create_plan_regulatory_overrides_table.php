<?php
// V-CONTRACT-HARDENING-002: Per-plan regulatory override capability
// This table enables explicit, audited regulatory overrides that apply to all active subscriptions
// of a plan WITHOUT mutating the immutable subscription snapshots.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_regulatory_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');

            // Override scope - what aspect of bonus config is being overridden
            $table->enum('override_scope', [
                'progressive_config',   // Override progressive bonus rules
                'milestone_config',     // Override milestone bonus rules
                'consistency_config',   // Override consistency/cashback rules
                'welcome_bonus_config', // Override welcome bonus
                'referral_tiers',       // Override referral tier multipliers
                'multiplier_cap',       // Override max multiplier
                'global_rate_adjust',   // Percentage adjustment to all rates
                'full_config',          // Full replacement of all configs
            ])->index();

            // The override payload (merged into snapshot config during calculation)
            // Structure depends on override_scope
            $table->json('override_payload')
                ->comment('JSON payload to merge/replace in bonus calculation');

            // Regulatory compliance fields (required for audit)
            $table->text('reason')
                ->comment('Business/regulatory reason for this override');
            $table->string('regulatory_reference', 255)
                ->comment('Regulatory order/circular reference (e.g., SEBI/HO/2026/001)');

            // Authorization and lifecycle
            $table->foreignId('approved_by_admin_id')
                ->constrained('admins')
                ->onDelete('restrict')
                ->comment('Admin who approved this regulatory override');
            $table->timestamp('effective_from')
                ->comment('When this override becomes active');
            $table->timestamp('expires_at')->nullable()
                ->comment('When this override expires (null = permanent until revoked)');

            // Soft revocation (never hard delete regulatory records)
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_admin_id')->nullable()
                ->constrained('admins')
                ->onDelete('restrict');
            $table->text('revocation_reason')->nullable();

            $table->timestamps();

            // Indexes for efficient lookup during bonus calculation
            $table->index(['plan_id', 'effective_from', 'expires_at'], 'idx_active_overrides');
            $table->index(['override_scope', 'effective_from'], 'idx_scope_effective');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_regulatory_overrides');
    }
};
