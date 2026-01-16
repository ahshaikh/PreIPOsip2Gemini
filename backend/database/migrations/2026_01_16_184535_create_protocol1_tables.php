<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROTOCOL-1 DATABASE MIGRATION
 *
 * PURPOSE:
 * Creates database tables for Protocol-1 governance enforcement:
 * - protocol1_violation_log: Comprehensive violation logging
 * - protocol1_alerts: Critical alerts requiring admin attention
 *
 * TABLES:
 * 1. protocol1_violation_log:
 *    - All Protocol-1 violations with full context
 *    - Actor attribution, action details, company/user context
 *    - Enforcement mode and blocking status
 *    - Searchable by rule_id, severity, actor_type, date range
 *
 * 2. protocol1_alerts:
 *    - Critical violations and anomalies
 *    - Admin acknowledgement tracking
 *    - Alert resolution workflow
 *
 * INDEXING STRATEGY:
 * - Fast lookups by actor_type, company_id, user_id
 * - Fast filtering by severity, date range
 * - Fast rule violation analysis by rule_id
 * - Fast alert queue queries (unacknowledged alerts)
 *
 * DATA RETENTION:
 * - Violation logs: Retain for 2 years (compliance requirement)
 * - Alerts: Retain indefinitely (audit trail)
 */
return new class extends Migration
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        // 1. VIOLATION LOG TABLE
        Schema::create('protocol1_violation_log', function (Blueprint $table) {
            $table->id();

            // Protocol Version
            $table->string('protocol_version', 20)->default('1.0.0')->index();

            // Rule Details
            $table->string('rule_id', 100)->index()->comment('Rule identifier (e.g., RULE_1_1_SUSPENSION)');
            $table->string('rule_name', 255)->nullable()->comment('Human-readable rule name');
            $table->enum('severity', ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'])->index()->comment('Violation severity');
            $table->text('message')->comment('Violation description');

            // Context: Actor Attribution
            $table->string('actor_type', 50)->index()->comment('Actor type: issuer, admin_judgment, investor, system_enforcement, etc.');
            $table->string('action', 100)->index()->comment('Action attempted (e.g., submit_disclosure, create_investment)');

            // Context: Company & User
            $table->foreignId('company_id')->nullable()->index()->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->onDelete('set null');

            // Metadata: Full Context
            $table->json('violation_details')->comment('Full violation data structure');
            $table->json('context_data')->nullable()->comment('Request context: IP, user agent, URL, etc.');

            // Enforcement: Blocking Status
            $table->boolean('was_blocked')->default(false)->index()->comment('Was action blocked?');
            $table->enum('enforcement_mode', ['strict', 'lenient', 'monitor'])->default('strict')->comment('Enforcement mode at time of violation');

            // Timestamps
            $table->timestamps();

            // Composite Indexes for Common Queries
            $table->index(['actor_type', 'created_at'], 'idx_actor_date');
            $table->index(['company_id', 'severity', 'created_at'], 'idx_company_severity_date');
            $table->index(['rule_id', 'created_at'], 'idx_rule_date');
            $table->index(['severity', 'was_blocked', 'created_at'], 'idx_severity_blocked_date');
        });

        // 2. ALERTS TABLE
        Schema::create('protocol1_alerts', function (Blueprint $table) {
            $table->id();

            // Alert Details
            $table->enum('severity', ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'])->index()->comment('Alert severity');
            $table->string('title', 255)->comment('Alert title');
            $table->text('message')->comment('Alert message');
            $table->json('alert_data')->comment('Full alert context and violation details');

            // Admin Response
            $table->boolean('is_acknowledged')->default(false)->index()->comment('Has admin acknowledged alert?');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null')->comment('Admin who acknowledged');
            $table->timestamp('acknowledged_at')->nullable()->comment('When alert was acknowledged');
            $table->text('admin_notes')->nullable()->comment('Admin notes on resolution');

            // Resolution Tracking
            $table->enum('resolution_status', ['pending', 'investigating', 'resolved', 'escalated'])->default('pending')->index();
            $table->timestamp('resolved_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Composite Indexes for Alert Queue
            $table->index(['is_acknowledged', 'severity', 'created_at'], 'idx_alert_queue');
            $table->index(['resolution_status', 'created_at'], 'idx_resolution_status_date');
        });

        // Add comments to tables
        DB::statement("COMMENT ON TABLE protocol1_violation_log IS 'Protocol-1 governance violation log - comprehensive audit trail for all rule violations'");
        DB::statement("COMMENT ON TABLE protocol1_alerts IS 'Protocol-1 alerts - critical violations and anomalies requiring admin attention'");
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('protocol1_alerts');
        Schema::dropIfExists('protocol1_violation_log');
    }
};
