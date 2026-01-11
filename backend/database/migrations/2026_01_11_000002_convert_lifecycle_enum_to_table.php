<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 1 STABILIZATION - Issue 2: ENUM Explosion Risk
 *
 * PROBLEM:
 * lifecycle_state is ENUM. Adding new states requires risky ALTER TABLE.
 *
 * SURGICAL FIX:
 * Convert ENUM to relational table. Define valid transitions as data.
 * No workflow logic changes. Pure data-driven state management.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Create lifecycle_states lookup table
        Schema::create('lifecycle_states', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Programmatic state code');
            $table->string('label')->comment('Human-readable label');
            $table->text('description')->nullable();
            $table->boolean('allows_buying')->default(false)->comment('Can investors purchase shares');
            $table->boolean('is_active')->default(true)->comment('Is this state currently usable');
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index(['is_active', 'display_order']);
        });

        // 2. Create state transitions table
        Schema::create('lifecycle_state_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('from_state', 50)->comment('Source state code');
            $table->string('to_state', 50)->comment('Target state code');
            $table->string('trigger', 100)->comment('What causes this transition');
            $table->text('conditions')->nullable()->comment('Additional conditions for transition');
            $table->boolean('requires_admin_approval')->default(false);
            $table->boolean('is_reversible')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['from_state', 'to_state']);
            $table->index('trigger');
        });

        // 3. Seed lifecycle states (preserve existing ENUM values)
        DB::table('lifecycle_states')->insert([
            [
                'code' => 'draft',
                'label' => 'Draft',
                'description' => 'Company profile being set up, not visible to investors',
                'allows_buying' => false,
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'live_limited',
                'label' => 'Live (Limited Disclosure)',
                'description' => 'Tier 1 approved, visible to investors, buying disabled',
                'allows_buying' => false,
                'is_active' => true,
                'display_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'live_investable',
                'label' => 'Live (Investable)',
                'description' => 'Tier 2 approved, buying enabled',
                'allows_buying' => true,
                'is_active' => true,
                'display_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'live_fully_disclosed',
                'label' => 'Live (Fully Disclosed)',
                'description' => 'All tiers approved, complete transparency',
                'allows_buying' => true,
                'is_active' => true,
                'display_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'suspended',
                'label' => 'Suspended',
                'description' => 'Trading suspended by platform',
                'allows_buying' => false,
                'is_active' => true,
                'display_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 4. Seed valid transitions
        $transitions = [
            // Normal progression
            ['draft', 'live_limited', 'tier_1_approved'],
            ['live_limited', 'live_investable', 'tier_2_approved'],
            ['live_investable', 'live_fully_disclosed', 'tier_3_approved'],

            // Suspension from any live state
            ['live_limited', 'suspended', 'admin_suspension'],
            ['live_investable', 'suspended', 'admin_suspension'],
            ['live_fully_disclosed', 'suspended', 'admin_suspension'],

            // Reactivation
            ['suspended', 'live_limited', 'admin_reactivation'],
            ['suspended', 'live_investable', 'admin_reactivation'],
            ['suspended', 'live_fully_disclosed', 'admin_reactivation'],
        ];

        foreach ($transitions as $t) {
            DB::table('lifecycle_state_transitions')->insert([
                'from_state' => $t[0],
                'to_state' => $t[1],
                'trigger' => $t[2],
                'requires_admin_approval' => in_array($t[2], ['admin_suspension', 'admin_reactivation']),
                'is_reversible' => $t[2] === 'admin_reactivation',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Convert companies.lifecycle_state from ENUM to VARCHAR with FK
        // First, add new column
        Schema::table('companies', function (Blueprint $table) {
            $table->string('lifecycle_state_new', 50)->nullable()->after('lifecycle_state');
        });

        // Copy existing ENUM values to new column
        DB::statement("UPDATE companies SET lifecycle_state_new = lifecycle_state");

        // Drop old ENUM column and rename new one
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('lifecycle_state');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->renameColumn('lifecycle_state_new', 'lifecycle_state');
        });

        // Add foreign key (optional, can be enforced at application layer)
        // Schema::table('companies', function (Blueprint $table) {
        //     $table->foreign('lifecycle_state')->references('code')->on('lifecycle_states');
        // });
    }

    public function down(): void
    {
        // Reverse: convert back to ENUM
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('lifecycle_state');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->enum('lifecycle_state', [
                'draft',
                'live_limited',
                'live_investable',
                'live_fully_disclosed',
                'suspended',
            ])->default('draft');
        });

        Schema::dropIfExists('lifecycle_state_transitions');
        Schema::dropIfExists('lifecycle_states');
    }
};
