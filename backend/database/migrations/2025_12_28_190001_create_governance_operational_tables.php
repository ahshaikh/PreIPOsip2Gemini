<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Governance & Operational Monitoring (H.25-H.26)
 *
 * PURPOSE:
 * - H.25: Audit all admin actions with constraints
 * - H.26: Operational visibility (dashboards, alerts, monitoring)
 *
 * TABLES:
 * 1. admin_action_audit - Track all manual admin actions
 * 2. system_health_metrics - Real-time system health monitoring
 * 3. reconciliation_alerts - Mismatch and gap detection
 * 4. operational_dashboards - Configurable dashboard widgets
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===================================================================
        // TABLE 1: admin_action_audit - Admin Action Tracking (H.25)
        // ===================================================================

        Schema::create('admin_action_audit', function (Blueprint $table) {
            $table->id();

            // Admin who performed action
            $table->unsignedBigInteger('admin_id');
            $table->string('admin_name'); // Cached for reporting
            $table->string('admin_role'); // Cached role at time of action

            // Action details
            $table->string('action_type'); // 'wallet_adjustment', 'payment_override', 'investment_cancel'
            $table->text('justification'); // Required for all actions
            $table->json('metadata')->nullable(); // Action-specific data

            // Affected entities
            $table->string('entity_type')->nullable(); // 'wallet', 'payment', 'investment'
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Affected user

            // Execution tracking
            $table->string('status'); // 'pending', 'completed', 'failed'
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();

            // Before/After state (for audit trail)
            $table->json('state_before')->nullable();
            $table->json('state_after')->nullable();

            // Approval workflow
            $table->boolean('requires_approval')->default(false);
            $table->boolean('approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('admin_id');
            $table->index('action_type');
            $table->index('status');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        // ===================================================================
        // TABLE 2: system_health_metrics - Real-time Monitoring (H.26)
        // ===================================================================

        Schema::create('system_health_metrics', function (Blueprint $table) {
            $table->id();

            // Metric identification
            $table->string('metric_name'); // 'wallet_balance_mismatch', 'stuck_payments', 'pending_allocations'
            $table->string('category'); // 'financial', 'operational', 'performance'
            $table->string('severity'); // 'info', 'warning', 'error', 'critical'

            // Metric value
            $table->decimal('current_value', 15, 2)->nullable();
            $table->decimal('threshold_warning', 15, 2)->nullable();
            $table->decimal('threshold_critical', 15, 2)->nullable();
            $table->string('unit')->nullable(); // 'count', 'rupees', 'percentage', 'seconds'

            // Status
            $table->boolean('is_healthy')->default(true);
            $table->text('health_message')->nullable();
            $table->timestamp('last_checked_at');
            $table->timestamp('unhealthy_since')->nullable();

            // Additional context
            $table->json('details')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('metric_name');
            $table->index('category');
            $table->index('severity');
            $table->index('is_healthy');
            $table->index('last_checked_at');
        });

        // ===================================================================
        // TABLE 3: reconciliation_alerts - Mismatch Detection (H.26)
        // ===================================================================

        Schema::create('reconciliation_alerts', function (Blueprint $table) {
            $table->id();

            // Alert identification
            $table->string('alert_type'); // 'balance_mismatch', 'orphaned_transaction', 'allocation_gap'
            $table->string('severity'); // 'low', 'medium', 'high', 'critical'

            // Affected entities
            $table->string('entity_type'); // 'wallet', 'payment', 'allocation'
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('user_id')->nullable();

            // Mismatch details
            $table->decimal('expected_value', 15, 2)->nullable();
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->decimal('discrepancy', 15, 2)->nullable();
            $table->text('description');

            // Resolution tracking
            $table->boolean('resolved')->default(false);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            // Auto-fix attempt
            $table->boolean('auto_fix_attempted')->default(false);
            $table->boolean('auto_fix_successful')->default(false);
            $table->timestamp('auto_fix_attempted_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('alert_type');
            $table->index('severity');
            $table->index(['entity_type', 'entity_id']);
            $table->index('resolved');
            $table->index('created_at');
        });

        // ===================================================================
        // TABLE 4: operational_dashboards - Dashboard Config (H.26)
        // ===================================================================

        Schema::create('operational_dashboards', function (Blueprint $table) {
            $table->id();

            // Dashboard identification
            $table->string('dashboard_name'); // 'financial_health', 'operations', 'compliance'
            $table->text('description')->nullable();

            // Widget configuration
            $table->json('widgets'); // Array of widget configs

            // Access control
            $table->json('allowed_roles'); // ['admin', 'finance_manager']
            $table->boolean('is_active')->default(true);

            // Display order
            $table->integer('display_order')->default(0);

            $table->timestamps();

            $table->unique('dashboard_name');
        });

        // ===================================================================
        // Constraints
        // ===================================================================

        if (Schema::hasTable('admin_action_audit') && Schema::hasColumn('admin_action_audit', 'status')) {
            try {
                DB::statement("
                    ALTER TABLE admin_action_audit
                    ADD CONSTRAINT check_admin_action_status
                    CHECK (status IN ('pending', 'completed', 'failed'))
                ");
            } catch (\Exception $e) {
                Log::warning("Could not add check_admin_action_status constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (Schema::hasTable('system_health_metrics') && Schema::hasColumn('system_health_metrics', 'severity')) {
            try {
                DB::statement("
                    ALTER TABLE system_health_metrics
                    ADD CONSTRAINT check_health_severity
                    CHECK (severity IN ('info', 'warning', 'error', 'critical'))
                ");
            } catch (\Exception $e) {
                Log::warning("Could not add check_health_severity constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (Schema::hasTable('reconciliation_alerts') && Schema::hasColumn('reconciliation_alerts', 'severity')) {
            try {
                DB::statement("
                    ALTER TABLE reconciliation_alerts
                    ADD CONSTRAINT check_reconciliation_severity
                    CHECK (severity IN ('low', 'medium', 'high', 'critical'))
                ");
            } catch (\Exception $e) {
                Log::warning("Could not add check_reconciliation_severity constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints
        DB::statement("ALTER TABLE admin_action_audit DROP CONSTRAINT IF EXISTS check_admin_action_status");
        DB::statement("ALTER TABLE system_health_metrics DROP CONSTRAINT IF EXISTS check_health_severity");
        DB::statement("ALTER TABLE reconciliation_alerts DROP CONSTRAINT IF EXISTS check_reconciliation_severity");

        // Drop tables
        Schema::dropIfExists('operational_dashboards');
        Schema::dropIfExists('reconciliation_alerts');
        Schema::dropIfExists('system_health_metrics');
        Schema::dropIfExists('admin_action_audit');
    }
};
