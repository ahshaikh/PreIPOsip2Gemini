<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Benefit Audit and Campaign Usage Tracking (D.11-D.15)
 *
 * TABLES:
 * - benefit_audit_log: Complete audit trail of all benefit decisions
 * - campaign_usages: Track campaign usage for limits and liability accounting
 *
 * PURPOSE:
 * - D.14: Make all benefits auditable and replayable
 * - D.15: Account for campaign costs as admin liabilities
 * - D.13: Enforce campaign usage limits (prevent illegal stacking)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===================================================================
        // TABLE 1: benefit_audit_log - Complete audit trail of decisions
        // ===================================================================
        Schema::create('benefit_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('investment_id')->nullable()->constrained('investments')->onDelete('restrict');

            $table->enum('benefit_type', ['promotional_campaign', 'referral_bonus', 'none']);
            $table->string('decision'); // 'promotional_campaign_applied', 'referral_bonus_applied', 'no_benefit_applicable'

            $table->decimal('original_amount', 15, 2);
            $table->decimal('benefit_amount', 15, 2);
            $table->decimal('final_amount', 15, 2);

            $table->text('eligibility_reason'); // Why benefit was granted/denied
            $table->json('metadata')->nullable(); // Full decision context for replay

            $table->timestamp('created_at');

            // Indexes for querying
            $table->index('user_id');
            $table->index('investment_id');
            $table->index('benefit_type');
            $table->index('created_at');
        });

        // ===================================================================
        // TABLE 2: campaign_usages - Track usage for limits and accounting
        // ===================================================================

        // Check if table already exists
        if (!Schema::hasTable('campaign_usages')) {
            Schema::create('campaign_usages', function (Blueprint $table) {
                $table->id();

                // Campaign or Referral (one will be null)
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('restrict');
                $table->foreignId('referral_id')->nullable()->constrained('referrals')->onDelete('restrict');

                $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
                $table->foreignId('investment_id')->constrained('investments')->onDelete('restrict');

                $table->enum('benefit_type', ['promotional_campaign', 'referral_bonus']);
                $table->decimal('benefit_amount', 15, 2); // Admin cost

                $table->text('eligibility_reason');
                $table->json('metadata')->nullable();

                // Reversal tracking (for saga compensation)
                $table->boolean('is_reversed')->default(false);
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();

                $table->timestamps();

                // Indexes for usage limit checks
                $table->index(['campaign_id', 'user_id']); // Per-user campaign limit
                $table->index('campaign_id'); // Global campaign limit
                $table->index('user_id');
                $table->index('created_at');
                $table->index('is_reversed'); // For filtering active vs reversed usages

                // Constraint: Either campaign_id OR referral_id must be set (not both)
                $table->check(DB::raw('
                    (campaign_id IS NOT NULL AND referral_id IS NULL)
                    OR (campaign_id IS NULL AND referral_id IS NOT NULL)
                '));
            });
        }

        // Add constraint: benefit_amount must be positive
        DB::statement("
            ALTER TABLE benefit_audit_log
            ADD CONSTRAINT check_benefit_amount_non_negative
            CHECK (benefit_amount >= 0)
        ");

        DB::statement("
            ALTER TABLE benefit_audit_log
            ADD CONSTRAINT check_final_amount_positive
            CHECK (final_amount > 0)
        ");

        DB::statement("
            ALTER TABLE benefit_audit_log
            ADD CONSTRAINT check_benefit_not_exceed_original
            CHECK (benefit_amount <= original_amount)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints
        DB::statement("ALTER TABLE benefit_audit_log DROP CONSTRAINT IF EXISTS check_benefit_amount_non_negative");
        DB::statement("ALTER TABLE benefit_audit_log DROP CONSTRAINT IF EXISTS check_final_amount_positive");
        DB::statement("ALTER TABLE benefit_audit_log DROP CONSTRAINT IF EXISTS check_benefit_not_exceed_original");

        Schema::dropIfExists('benefit_audit_log');
        Schema::dropIfExists('campaign_usages');
    }
};
