<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Enhance disputes table for Admin-First Dispute Management System
 *
 * PURPOSE:
 * - Add polymorphic attachment (disputable_type, disputable_id) for Payment, Investment, Withdrawal, etc.
 * - Replace status enum with 7-state machine: open, under_review, awaiting_investor, escalated, resolved_approved, resolved_rejected, closed
 * - Add type column for DisputeType enum (confusion, payment, allocation, fraud)
 * - Add SLA and escalation tracking fields
 *
 * USED BY:
 * - DisputeStateMachine: Enforces valid state transitions
 * - DisputeSnapshotService: Creates immutable snapshots at filing time
 * - Admin dispute management system
 * - Investor dispute resolution workflow
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            // Polymorphic attachment - which entity is this dispute about?
            $table->string('disputable_type')->nullable()->after('id')
                ->comment('Model class: Payment, Investment, Withdrawal, BonusTransaction, Allocation');
            $table->unsignedBigInteger('disputable_id')->nullable()->after('disputable_type')
                ->comment('ID of the disputable model');

            // Dispute type (maps to DisputeType enum)
            $table->string('type')->default('confusion')->after('disputable_id')
                ->comment('Type: confusion, payment, allocation, fraud');

            // SLA and escalation tracking
            $table->timestamp('sla_deadline_at')->nullable()->after('closed_at')
                ->comment('When SLA response is due');
            $table->timestamp('escalation_deadline_at')->nullable()->after('sla_deadline_at')
                ->comment('When auto-escalation will trigger');
            $table->timestamp('escalated_at')->nullable()->after('escalation_deadline_at')
                ->comment('When dispute was escalated');

            // Risk score computed from type and age
            $table->unsignedTinyInteger('risk_score')->default(1)->after('escalated_at')
                ->comment('1-4 risk score for prioritization');

            // Settlement tracking
            $table->string('settlement_action')->nullable()->after('resolution')
                ->comment('Settlement action taken: refund, credit, allocation_correction, none');
            $table->unsignedBigInteger('settlement_amount_paise')->nullable()->after('settlement_action')
                ->comment('Settlement amount in paise if monetary');
            $table->json('settlement_details')->nullable()->after('settlement_amount_paise')
                ->comment('Detailed settlement breakdown');

            // Indexes for polymorphic queries
            $table->index(['disputable_type', 'disputable_id'], 'disputes_disputable_index');
            $table->index('type');
            $table->index('risk_score');
            $table->index('sla_deadline_at');
            $table->index('escalation_deadline_at');
        });

        // Modify status enum to include new states
        // Note: MySQL enum modification requires raw SQL
        DB::statement("ALTER TABLE disputes MODIFY COLUMN status ENUM(
            'open',
            'under_investigation',
            'under_review',
            'awaiting_investor',
            'escalated',
            'resolved',
            'resolved_approved',
            'resolved_rejected',
            'closed'
        ) DEFAULT 'open'");

        // Make company_id nullable for disputes not related to a specific company
        Schema::table('disputes', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropIndex('disputes_disputable_index');
            $table->dropIndex('disputes_type_index');
            $table->dropIndex('disputes_risk_score_index');
            $table->dropIndex('disputes_sla_deadline_at_index');
            $table->dropIndex('disputes_escalation_deadline_at_index');

            $table->dropColumn([
                'disputable_type',
                'disputable_id',
                'type',
                'sla_deadline_at',
                'escalation_deadline_at',
                'escalated_at',
                'risk_score',
                'settlement_action',
                'settlement_amount_paise',
                'settlement_details',
            ]);
        });

        // Revert status enum
        DB::statement("ALTER TABLE disputes MODIFY COLUMN status ENUM(
            'open',
            'under_investigation',
            'resolved',
            'closed',
            'escalated'
        ) DEFAULT 'open'");
    }
};
