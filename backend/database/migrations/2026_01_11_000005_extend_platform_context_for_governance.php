<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 2 HARDENING - Issue 1: Platform Context Boundary
 *
 * PROBLEM:
 * Phase 2 governance judgments (lifecycle_state, buying_enabled, suspension_reason)
 * lack explicit platform ownership protection like Phase 4 metrics do.
 *
 * SURGICAL FIX:
 * Extend platform_context_authority to cover governance judgments.
 * Add platform_governance_log for time-aware audit trail.
 * Enforce issuer cannot write governance state.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend platform context authority for governance judgments
        $governanceContexts = [
            [
                'context_type' => 'lifecycle_state',
                'description' => 'Company lifecycle state transitions (draft → live_limited → live_investable → live_fully_disclosed, suspended)',
                'calculation_frequency' => 'on_approval',
            ],
            [
                'context_type' => 'buying_enablement',
                'description' => 'Platform decision on whether buying is enabled for company',
                'calculation_frequency' => 'on_approval',
            ],
            [
                'context_type' => 'suspension_judgment',
                'description' => 'Platform suspension decisions and reasons',
                'calculation_frequency' => 'on_demand',
            ],
            [
                'context_type' => 'tier_approval',
                'description' => 'Platform approval of disclosure tiers',
                'calculation_frequency' => 'on_approval',
            ],
        ];

        foreach ($governanceContexts as $gc) {
            DB::table('platform_context_authority')->insert(array_merge([
                'owning_domain' => 'platform',
                'is_company_writable' => false,  // ALWAYS FALSE
                'is_platform_managed' => true,   // ALWAYS TRUE
                'effective_from' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ], $gc));
        }

        // 2. Create platform governance log for time-aware audit trail
        Schema::create('platform_governance_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Governance action type
            $table->string('action_type', 100)->comment(
                'Type: lifecycle_transition, buying_toggle, suspension, reactivation, tier_approval'
            );

            // Before/After state
            $table->string('from_state', 50)->nullable()->comment('Previous lifecycle state');
            $table->string('to_state', 50)->nullable()->comment('New lifecycle state');
            $table->boolean('buying_enabled_before')->nullable();
            $table->boolean('buying_enabled_after')->nullable();

            // Platform decision metadata
            $table->text('decision_reason')->nullable()->comment('Why platform made this decision');
            $table->json('decision_criteria')->nullable()->comment('Criteria evaluated (tier completion, flags, etc.)');
            $table->foreignId('decided_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Admin who made decision (null for automated)');
            $table->boolean('is_automated')->default(false)->comment('Was this automated or manual admin action');

            // Audit trail
            $table->timestamp('decided_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Immutability
            $table->boolean('is_immutable')->default(true)
                ->comment('Governance decisions are permanent record');

            $table->timestamps();

            $table->index(['company_id', 'action_type', 'decided_at']);
            $table->index('action_type');
            $table->index('decided_at');
        });

        // 3. Add governance_state_version to companies for snapshot comparison
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('governance_state_version')->default(1)->after('lifecycle_state')
                ->comment('Incremented on every governance state change for snapshot binding');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('governance_state_version');
        });

        Schema::dropIfExists('platform_governance_log');

        DB::table('platform_context_authority')
            ->whereIn('context_type', [
                'lifecycle_state',
                'buying_enablement',
                'suspension_judgment',
                'tier_approval',
            ])
            ->delete();
    }
};
