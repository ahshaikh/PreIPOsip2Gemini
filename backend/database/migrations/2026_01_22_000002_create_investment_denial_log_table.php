<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create investment_denial_log table for audit trail
 *
 * PURPOSE:
 * Log all investment denial attempts for compliance and audit purposes.
 * Records why investments were blocked by the BuyEnablementGuardService.
 *
 * USED BY:
 * - BuyEnablementGuardService::logDenialAttempt()
 * - Admin compliance reporting
 * - Investor support troubleshooting
 * - Platform analytics and monitoring
 *
 * RETENTION:
 * - Keep logs for minimum 7 years for regulatory compliance
 * - Consider archiving old logs to separate table/storage
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('investment_denial_log', function (Blueprint $table) {
            $table->id();

            // Who and what
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Why denied
            $table->json('blockers')->comment('Array of guard blockers that prevented investment');

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->decimal('attempted_amount', 15, 2)->nullable();

            // Additional metadata
            $table->string('denial_source')->default('buy_enablement_guard')
                ->comment('Which service/guard denied the investment');
            $table->json('company_state')->nullable()
                ->comment('Snapshot of company state at denial time');
            $table->json('user_state')->nullable()
                ->comment('Snapshot of user state at denial time');

            // Follow-up
            $table->boolean('user_notified')->default(false);
            $table->timestamp('user_notified_at')->nullable();
            $table->boolean('resolved')->default(false)
                ->comment('Whether the blocking issues were later resolved');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamp('created_at')->nullable();

            // Indexes for audit queries
            $table->index(['company_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
            $table->index('denial_source');
            $table->index(['resolved', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_denial_log');
    }
};
