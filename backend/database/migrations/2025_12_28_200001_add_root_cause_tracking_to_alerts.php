<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Root Cause Tracking to Alerts
 *
 * PURPOSE (addressing audit feedback):
 * - "Alert volume can itself become a failure mode" - alert fatigue
 * - Track root causes to prevent symptoms from masking systemic issues
 * - Group similar alerts by root cause for efficient resolution
 *
 * EXAMPLE:
 * Instead of: "50 stuck payments" (symptom)
 * Show: "Payment gateway timeout - affecting 50 payments since 2h ago" (root cause)
 *
 * ROOT CAUSE TYPES:
 * - payment_gateway_timeout: External gateway outage
 * - inventory_service_down: Internal service failure
 * - webhook_delivery_failure: Network/infrastructure issue
 * - concurrency_bug: Race condition in code
 * - data_integrity_violation: Business logic bug
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stuck_state_alerts', function (Blueprint $table) {
            // Root cause identification
            $table->string('root_cause')->nullable()->after('description');
            $table->timestamp('root_cause_identified_at')->nullable()->after('root_cause');
            $table->unsignedBigInteger('root_cause_identified_by')->nullable()->after('root_cause_identified_at');

            // Root cause grouping (for alert aggregation)
            $table->string('root_cause_group')->nullable()->after('root_cause_identified_by');

            // Index for root cause queries
            $table->index('root_cause');
            $table->index('root_cause_group');
        });

        Schema::table('reconciliation_alerts', function (Blueprint $table) {
            // Root cause identification
            $table->string('root_cause')->nullable()->after('description');
            $table->timestamp('root_cause_identified_at')->nullable()->after('root_cause');
            $table->unsignedBigInteger('root_cause_identified_by')->nullable()->after('root_cause_identified_at');

            // Root cause grouping
            $table->string('root_cause_group')->nullable()->after('root_cause_identified_by');

            // Index for root cause queries
            $table->index('root_cause');
            $table->index('root_cause_group');
        });

        // Create root cause catalog table
        Schema::create('alert_root_causes', function (Blueprint $table) {
            $table->id();

            // Root cause identification
            $table->string('root_cause_type'); // 'payment_gateway_timeout', 'service_down', etc.
            $table->text('description');

            // Impact tracking
            $table->integer('affected_alerts_count')->default(0);
            $table->decimal('total_monetary_impact', 15, 2)->default(0);
            $table->integer('affected_users_count')->default(0);

            // Timeline
            $table->timestamp('first_occurrence')->nullable();
            $table->timestamp('last_occurrence')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Resolution
            $table->boolean('is_resolved')->default(false);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();

            // Severity (calculated from impact)
            $table->string('severity'); // 'low', 'medium', 'high', 'critical'

            $table->timestamps();

            // Indexes
            $table->index('root_cause_type');
            $table->index('is_resolved');
            $table->index('severity');
            $table->index('first_occurrence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stuck_state_alerts', function (Blueprint $table) {
            $table->dropIndex(['root_cause']);
            $table->dropIndex(['root_cause_group']);
            $table->dropColumn(['root_cause', 'root_cause_identified_at', 'root_cause_identified_by', 'root_cause_group']);
        });

        Schema::table('reconciliation_alerts', function (Blueprint $table) {
            $table->dropIndex(['root_cause']);
            $table->dropIndex(['root_cause_group']);
            $table->dropColumn(['root_cause', 'root_cause_identified_at', 'root_cause_identified_by', 'root_cause_group']);
        });

        Schema::dropIfExists('alert_root_causes');
    }
};
