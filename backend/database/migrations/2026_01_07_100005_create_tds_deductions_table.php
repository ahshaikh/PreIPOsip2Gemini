<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX 13 (P3): TDS Reporting Module
 *
 * Creates table for tracking Tax Deducted at Source (TDS) deductions
 * Supports Form 16A generation and quarterly TDS return filing
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tds_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Transaction details
            $table->string('transaction_type'); // withdrawal, profit_share, dividend
            $table->foreignId('transaction_id')->nullable(); // ID in respective table
            $table->string('financial_year', 10); // e.g., "2023-24"
            $table->tinyInteger('quarter'); // 1, 2, 3, 4

            // Amount details (in paise for precision)
            $table->bigInteger('gross_amount_paise'); // Total amount before TDS
            $table->decimal('gross_amount', 15, 2); // Backward compatibility
            $table->bigInteger('tds_amount_paise'); // TDS deducted
            $table->decimal('tds_amount', 15, 2); // Backward compatibility
            $table->decimal('tds_rate', 5, 2); // e.g., 10.00 for 10%
            $table->bigInteger('net_amount_paise'); // Amount after TDS
            $table->decimal('net_amount', 15, 2); // Backward compatibility

            // Tax details
            $table->string('section_code', 20); // e.g., "194", "194A", "194J"
            $table->string('pan_number', 10)->nullable();
            $table->boolean('pan_available')->default(true);

            // Deduction details
            $table->date('deduction_date');
            $table->date('deposit_date')->nullable(); // When TDS paid to govt
            $table->string('challan_number', 50)->nullable();
            $table->string('bsr_code', 10)->nullable();

            // Certificate details
            $table->string('certificate_number', 50)->nullable();
            $table->date('certificate_date')->nullable();
            $table->string('certificate_path')->nullable();

            // Status
            $table->enum('status', ['pending', 'deposited', 'filed', 'certified'])->default('pending');

            // Compliance metadata
            $table->json('metadata')->nullable(); // Additional compliance data
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for reporting
            $table->index(['user_id', 'financial_year']);
            $table->index(['financial_year', 'quarter']);
            $table->index(['deduction_date']);
            $table->index(['status']);
            $table->index(['section_code']);
        });

        // Quarterly TDS Returns tracking table
        Schema::create('tds_quarterly_returns', function (Blueprint $table) {
            $table->id();
            $table->string('financial_year', 10);
            $table->tinyInteger('quarter');

            // Filing details
            $table->enum('return_type', ['24Q', '26Q', '27Q']); // Form types
            $table->date('due_date');
            $table->date('filed_date')->nullable();
            $table->string('acknowledgement_number', 50)->nullable();

            // Summary amounts
            $table->integer('total_deductees');
            $table->bigInteger('total_tds_paise');
            $table->decimal('total_tds', 15, 2);

            // Status
            $table->enum('status', ['pending', 'filed', 'revised', 'rectified'])->default('pending');

            // File paths
            $table->string('return_file_path')->nullable();
            $table->string('ack_file_path')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['financial_year', 'quarter', 'return_type']);
            $table->index(['status']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_quarterly_returns');
        Schema::dropIfExists('tds_deductions');
    }
};
