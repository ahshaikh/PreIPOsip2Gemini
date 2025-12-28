<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: TDS Deductions Table (F.20)
 *
 * PURPOSE:
 * - F.20: Guarantee TDS enforcement on all taxable paths
 * - Store all TDS deductions for Form 26AS reporting
 * - Immutable audit trail for tax authorities
 *
 * COMPLIANCE:
 * - Income Tax Act requirements
 * - Form 16A generation
 * - Form 26AS reconciliation
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===================================================================
        // TABLE: tds_deductions - Complete TDS audit trail
        // ===================================================================
        Schema::create('tds_deductions', function (Blueprint $table) {
            $table->id();

            // User and transaction references
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('restrict');

            // Financial year (April to March)
            $table->string('financial_year', 10); // e.g., "2025-2026"

            // Transaction details
            $table->string('transaction_type'); // 'withdrawal', 'profit', 'bonus', etc.
            $table->decimal('gross_amount', 15, 2); // Amount before TDS
            $table->decimal('tds_amount', 15, 2); // TDS deducted
            $table->decimal('tds_rate', 5, 2); // TDS rate percentage

            // PAN details (for Form 16A)
            $table->string('pan_number')->nullable();
            $table->boolean('pan_verified')->default(false);

            // Deduction timestamp
            $table->timestamp('deducted_at');

            // Form 16A details (if generated)
            $table->string('form_16a_reference')->nullable();
            $table->timestamp('form_16a_generated_at')->nullable();

            $table->timestamps();

            // Indexes for reporting
            $table->index(['user_id', 'financial_year']);
            $table->index('financial_year');
            $table->index('transaction_type');
            $table->index('deducted_at');
            $table->index('form_16a_reference');
        });

        // ===================================================================
        // CONSTRAINTS: Ensure data integrity
        // ===================================================================

        // TDS amount must be positive
        DB::statement("
            ALTER TABLE tds_deductions
            ADD CONSTRAINT check_tds_amount_positive
            CHECK (tds_amount > 0)
        ");

        // Gross amount must be positive
        DB::statement("
            ALTER TABLE tds_deductions
            ADD CONSTRAINT check_gross_amount_positive
            CHECK (gross_amount > 0)
        ");

        // TDS cannot exceed gross amount
        DB::statement("
            ALTER TABLE tds_deductions
            ADD CONSTRAINT check_tds_not_exceed_gross
            CHECK (tds_amount <= gross_amount)
        ");

        // TDS rate must be between 0 and 30%
        DB::statement("
            ALTER TABLE tds_deductions
            ADD CONSTRAINT check_tds_rate_valid
            CHECK (tds_rate >= 0 AND tds_rate <= 30)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints
        DB::statement("ALTER TABLE tds_deductions DROP CONSTRAINT IF EXISTS check_tds_amount_positive");
        DB::statement("ALTER TABLE tds_deductions DROP CONSTRAINT IF EXISTS check_gross_amount_positive");
        DB::statement("ALTER TABLE tds_deductions DROP CONSTRAINT IF EXISTS check_tds_not_exceed_gross");
        DB::statement("ALTER TABLE tds_deductions DROP CONSTRAINT IF EXISTS check_tds_rate_valid");

        Schema::dropIfExists('tds_deductions');
    }
};
