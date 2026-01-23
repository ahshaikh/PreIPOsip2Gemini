<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create disputes table for tracking company/platform disputes
 *
 * PURPOSE:
 * Track disputes between investors, companies, and platform.
 * Used by BuyEnablementGuardService to block investments for companies with active disputes.
 *
 * USED BY:
 * - BuyEnablementGuardService::checkPlatformRestrictionGuards()
 * - Admin dispute management system (future)
 * - Investor dispute resolution workflow (future)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();

            // Parties involved
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('raised_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Dispute details
            $table->enum('status', ['open', 'under_investigation', 'resolved', 'closed', 'escalated'])->default('open');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('category', [
                'financial_disclosure',
                'investment_processing',
                'kyc_verification',
                'fund_transfer',
                'platform_service',
                'company_conduct',
                'investor_conduct',
                'other'
            ])->default('other');

            $table->string('title');
            $table->text('description');
            $table->json('evidence')->nullable()->comment('Documents, screenshots, etc.');

            // Resolution
            $table->text('resolution')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('assigned_to_admin_id')->nullable()->constrained('users')->onDelete('set null');

            // Timestamps
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('investigation_started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Impact tracking
            $table->boolean('blocks_investment')->default(true)
                ->comment('Whether this dispute should block new investments');
            $table->boolean('requires_platform_freeze')->default(false)
                ->comment('Whether this dispute requires freezing company operations');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('severity');
            $table->index('assigned_to_admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
