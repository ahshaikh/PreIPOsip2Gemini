<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 5 - Issue 3: Risk Acknowledgements
 *
 * PURPOSE:
 * Track investor risk acknowledgements with full audit trail.
 * Required before any investment can proceed.
 *
 * LOGGED DATA:
 * - User and company reference
 * - Acknowledgement type (illiquidity, no_guarantee, platform_non_advisory)
 * - Timestamp (when acknowledged)
 * - IP address and user agent (audit trail)
 * - Session and investment context
 */
return new class extends Migration
{
    public function up(): void
    {
    Schema::create('investor_risk_acknowledgements', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->foreignId('company_id')
            ->constrained('companies')
            ->cascadeOnDelete();

        // Acknowledgement type
        $table->enum('acknowledgement_type', [
            'illiquidity',
            'no_guarantee',
            'platform_non_advisory',
            'material_changes',
        ]);

        // Acknowledgement context
        $table->timestamp('acknowledged_at')->useCurrent();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->string('session_id', 100)->nullable();

        // Optional: Link to specific investment
        $table->foreignId('investment_id')->nullable();
        $table->foreign(
            'investment_id',
            'fk_ira_investment'
        )->references('id')
        ->on('investments')
        ->nullOnDelete();

        // Optional: Acknowledgement text shown to user
        $table->text('acknowledgement_text_shown')->nullable();

        // Optional: Platform context snapshot
        $table->foreignId('platform_context_snapshot_id')->nullable();
        $table->foreign(
            'platform_context_snapshot_id',
            'fk_ira_platform_snapshot'
        )->references('id')
        ->on('platform_context_snapshots')
        ->nullOnDelete();

        // Expiry tracking
        $table->timestamp('expires_at')->nullable();
        $table->boolean('is_expired')->default(false);

        // Metadata
        $table->json('metadata')->nullable();

        $table->timestamps();

        // Indexes
        $table->index(['user_id', 'company_id', 'acknowledgement_type'], 'idx_ira_user_company_type');
        $table->index('acknowledged_at', 'idx_ira_acknowledged_at');
        $table->index('expires_at', 'idx_ira_expires_at');
    });

        // Log table for acknowledgement events
        Schema::create('investor_acknowledgement_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->enum('event_type', [
                'acknowledgement_requested',
                'acknowledgement_granted',
                'acknowledgement_expired',
                'acknowledgement_renewed',
                'investment_blocked_missing_ack',
            ]);

            $table->json('acknowledgements_status')->nullable(); // Current state of all acknowledgements
            $table->text('event_details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'company_id']);
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_acknowledgement_log');
        Schema::dropIfExists('investor_risk_acknowledgements');
    }
};
