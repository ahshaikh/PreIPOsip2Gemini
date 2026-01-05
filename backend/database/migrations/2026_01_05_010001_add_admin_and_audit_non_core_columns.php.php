<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin & Audit Infrastructure – Non-Core
     * ---------------------------------------
     * Scope:
     * - activity_logs
     * - audit_logs
     * - performance_metrics
     * - system_health_checks
     *
     * Rules:
     * - ADDITIVE ONLY
     * - NO DROPS
     * - NO DATA REWRITES
     */
    public function up(): void
    {
        /**
         * ==========================================================
         * ACTIVITY LOGS
         * ==========================================================
         */
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('activity_logs', 'old_values')) {
                    $table->json('old_values')->nullable()->after('description');
                }

                if (!Schema::hasColumn('activity_logs', 'new_values')) {
                    $table->json('new_values')->nullable()->after('old_values');
                }
            });
        }

        /**
         * ==========================================================
         * AUDIT LOGS
         * ==========================================================
         */
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                // IMPORTANT:
                // Some earlier schemas used actor_id instead of admin_id.
                // We add admin_id WITHOUT assuming user_id exists.
                if (!Schema::hasColumn('audit_logs', 'admin_id')) {
                    $table->unsignedBigInteger('admin_id')
                        ->nullable()
                        ->comment('Admin responsible for audited action')
                        ->after('id');
                }
            });
        }

        /**
         * ==========================================================
         * PERFORMANCE METRICS
         * ==========================================================
         */
        if (Schema::hasTable('performance_metrics')) {
            Schema::table('performance_metrics', function (Blueprint $table) {
                if (!Schema::hasColumn('performance_metrics', 'metadata')) {
                    $table->json('metadata')->nullable()->after('value');
                }
            });
        }

        /**
         * ==========================================================
         * SYSTEM HEALTH CHECKS
         * ==========================================================
         */
        if (Schema::hasTable('system_health_checks')) {
            Schema::table('system_health_checks', function (Blueprint $table) {
                if (!Schema::hasColumn('system_health_checks', 'details')) {
                    $table->json('details')->nullable()->after('message');
                }

                if (!Schema::hasColumn('system_health_checks', 'response_time')) {
                    $table->integer('response_time')
                        ->nullable()
                        ->comment('Response time in milliseconds')
                        ->after('details');
                }
            });
        }
    }

    /**
     * Reverse migrations.
     * ---------------------------------------
     * Intentionally EMPTY.
     * Non-destructive, production-safe.
     */
    public function down(): void
    {
        // NO ROLLBACK (Additive-only migration)
    }
};
