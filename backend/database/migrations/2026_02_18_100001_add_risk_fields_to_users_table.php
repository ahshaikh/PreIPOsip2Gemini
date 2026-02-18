<?php

// V-DISPUTE-RISK-2026-001 | Phase 1 - User Risk Profile Fields

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Risk scoring fields for chargeback/dispute tracking
            $table->unsignedSmallInteger('risk_score')->default(0)->after('status');
            $table->boolean('is_blocked')->default(false)->after('risk_score');
            $table->text('blocked_reason')->nullable()->after('is_blocked');
            $table->timestamp('last_risk_update_at')->nullable()->after('blocked_reason');

            // Index for fast blocked user lookups (investment guard queries)
            $table->index('is_blocked', 'users_is_blocked_idx');
            // Composite index for risk-based queries
            $table->index(['is_blocked', 'risk_score'], 'users_risk_profile_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_risk_profile_idx');
            $table->dropIndex('users_is_blocked_idx');
            $table->dropColumn(['risk_score', 'is_blocked', 'blocked_reason', 'last_risk_update_at']);
        });
    }
};
