<?php
// V-CONTRACT-HARDENING-001: Immutable subscription bonus contract snapshots
// This migration adds JSON columns to store resolved bonus configuration at subscription time.
// These fields represent the contractual bonus terms and must NEVER be mutated after creation.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Core bonus configuration snapshots (immutable contractual terms)
            // These are NOT nullable - every subscription must have defined bonus rules
            $table->json('progressive_config')->nullable()->after('bonus_multiplier')
                ->comment('Immutable: Progressive bonus rules at subscription time');
            $table->json('milestone_config')->nullable()->after('progressive_config')
                ->comment('Immutable: Milestone bonus rules at subscription time');
            $table->json('consistency_config')->nullable()->after('milestone_config')
                ->comment('Immutable: Consistency/cashback rules at subscription time');

            // Optional bonus configuration snapshots
            $table->json('welcome_bonus_config')->nullable()->after('consistency_config')
                ->comment('Immutable: Welcome bonus rules (first payment only)');
            $table->json('referral_tiers')->nullable()->after('welcome_bonus_config')
                ->comment('Immutable: Referral tier multipliers at subscription time');
            $table->json('celebration_bonus_config')->nullable()->after('referral_tiers')
                ->comment('Immutable: Celebration event bonus rules');
            $table->json('lucky_draw_entries')->nullable()->after('celebration_bonus_config')
                ->comment('Immutable: Lucky draw entry rules per payment');

            // Snapshot metadata for audit trail
            $table->timestamp('config_snapshot_at')->nullable()->after('lucky_draw_entries')
                ->comment('When the bonus config was snapshotted');
            $table->string('config_snapshot_version', 32)->nullable()->after('config_snapshot_at')
                ->comment('Version hash of snapshotted config for integrity verification');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'progressive_config',
                'milestone_config',
                'consistency_config',
                'welcome_bonus_config',
                'referral_tiers',
                'celebration_bonus_config',
                'lucky_draw_entries',
                'config_snapshot_at',
                'config_snapshot_version',
            ]);
        });
    }
};
