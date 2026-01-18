<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 1 STABILIZATION - Issue 4: Platform Context (Mandatory)
 *
 * PROBLEM:
 * Platform context (metrics, flags, valuation) lacks explicit ownership protection.
 * Companies could theoretically infer or overwrite platform calculations.
 *
 * SURGICAL FIX:
 * Create authority table defining ownership and calculation rules.
 * Add versioning for platform context evolution.
 * Enforce read-only access for companies.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Platform Context Authority Table
        Schema::create('platform_context_authority', function (Blueprint $table) {
            $table->id();
            $table->string('context_type', 100)->unique()
                ->comment('Type: metric, risk_flag, valuation_context, etc.');
            $table->string('owning_domain')->default('platform')
                ->comment('Always "platform" - companies cannot own');
            $table->boolean('is_company_writable')->default(false)
                ->comment('Can companies write this context? ALWAYS FALSE');
            $table->boolean('is_platform_managed')->default(true)
                ->comment('Is this managed by platform? ALWAYS TRUE');
            $table->enum('calculation_frequency', [
                'on_approval',
                'hourly',
                'daily',
                'weekly',
                'on_demand'
            ])->default('on_approval')
                ->comment('When should this context be recalculated');
            $table->timestamp('effective_from')->useCurrent()
                ->comment('When this authority rule took effect');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('context_type');
            $table->index(
		['is_company_writable', 'is_platform_managed'],
		'pca_write_managed_idx'
	    );
         });

        // 2. Platform Context Versions Table
        Schema::create('platform_context_versions', function (Blueprint $table) {
            $table->id();
            $table->string('context_type', 100);
            $table->string('version_code', 50)->comment('e.g., v1.0.0, v2.1.3');
            $table->text('changelog')->nullable()->comment('What changed in this version');
            $table->json('calculation_logic')->nullable()
                ->comment('Serialized calculation rules for reproducibility');
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_until')->nullable()
                ->comment('When this version was superseded');
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['context_type', 'version_code']);
            $table->index(['context_type', 'is_current']);
            $table->index('effective_from');
        });

        // 3. Seed platform context authority rules
        $contextTypes = [
            [
                'context_type' => 'company_metrics',
                'description' => 'Health scores (completeness, financial, governance, risk)',
                'calculation_frequency' => 'on_approval',
            ],
            [
                'context_type' => 'risk_flags',
                'description' => 'Automated risk detection flags',
                'calculation_frequency' => 'on_approval',
            ],
            [
                'context_type' => 'valuation_context',
                'description' => 'Peer comparison and valuation bands',
                'calculation_frequency' => 'daily',
            ],
            [
                'context_type' => 'disclosure_completeness',
                'description' => 'Disclosure field completion percentage',
                'calculation_frequency' => 'on_approval',
            ],
            [
                'context_type' => 'change_tracking',
                'description' => 'What\'s new since investor last visit',
                'calculation_frequency' => 'on_demand',
            ],
        ];

        foreach ($contextTypes as $ct) {
            DB::table('platform_context_authority')->insert(array_merge([
                'owning_domain' => 'platform',
                'is_company_writable' => false,  // ALWAYS FALSE
                'is_platform_managed' => true,   // ALWAYS TRUE
                'effective_from' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ], $ct));
        }

        // 4. Seed initial platform context versions
        $versions = [
            [
                'context_type' => 'company_metrics',
                'version_code' => 'v1.0.0',
                'changelog' => 'Initial implementation: Disclosure completeness, financial health, governance quality, risk intensity bands',
                'effective_from' => now(),
            ],
            [
                'context_type' => 'risk_flags',
                'version_code' => 'v1.0.0',
                'changelog' => 'Initial implementation: Financial, governance, disclosure quality, legal flags',
                'effective_from' => now(),
            ],
        ];

        foreach ($versions as $v) {
            DB::table('platform_context_versions')->insert(array_merge([
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], $v));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_context_versions');
        Schema::dropIfExists('platform_context_authority');
    }
};
