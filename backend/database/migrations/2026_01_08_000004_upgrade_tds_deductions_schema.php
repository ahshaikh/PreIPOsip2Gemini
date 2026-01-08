<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Upgrade TDS Deductions Schema (FIX 13 - P3)
 *
 * Upgrades the existing tds_deductions table with additional compliance fields
 * and creates the tds_quarterly_returns table for quarterly filing tracking
 */
return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists before altering
        if (Schema::hasTable('tds_deductions')) {
            Schema::table('tds_deductions', function (Blueprint $table) {
                // Add quarter tracking if not exists
                if (!Schema::hasColumn('tds_deductions', 'quarter')) {
                    $table->tinyInteger('quarter')->after('financial_year')->nullable();
                }

                // Add paise precision fields if not exists
                if (!Schema::hasColumn('tds_deductions', 'gross_amount_paise')) {
                    $table->bigInteger('gross_amount_paise')->after('transaction_type')->default(0);
                }
                if (!Schema::hasColumn('tds_deductions', 'tds_amount_paise')) {
                    $table->bigInteger('tds_amount_paise')->after('gross_amount')->default(0);
                }
                if (!Schema::hasColumn('tds_deductions', 'net_amount_paise')) {
                    $table->bigInteger('net_amount_paise')->after('tds_rate')->nullable();
                }
                if (!Schema::hasColumn('tds_deductions', 'net_amount')) {
                    $table->decimal('net_amount', 15, 2)->after('net_amount_paise')->nullable();
                }

                // Add section code if not exists
                if (!Schema::hasColumn('tds_deductions', 'section_code')) {
                    $table->string('section_code', 20)->after('tds_rate')->default('194');
                }

                // Add PAN available flag if not exists
                if (!Schema::hasColumn('tds_deductions', 'pan_available')) {
                    $table->boolean('pan_available')->after('pan_number')->default(true);
                }

                // Rename deducted_at to deduction_date if exists
                if (Schema::hasColumn('tds_deductions', 'deducted_at') && !Schema::hasColumn('tds_deductions', 'deduction_date')) {
                    $table->renameColumn('deducted_at', 'deduction_date');
                } elseif (!Schema::hasColumn('tds_deductions', 'deduction_date')) {
                    $table->date('deduction_date')->after('pan_available')->nullable();
                }

                // Add deposit details if not exists
                if (!Schema::hasColumn('tds_deductions', 'deposit_date')) {
                    $table->date('deposit_date')->after('deduction_date')->nullable();
                }
                if (!Schema::hasColumn('tds_deductions', 'challan_number')) {
                    $table->string('challan_number', 50)->after('deposit_date')->nullable();
                }
                if (!Schema::hasColumn('tds_deductions', 'bsr_code')) {
                    $table->string('bsr_code', 10)->after('challan_number')->nullable();
                }

                // Replace form_16a fields with certificate fields
                if (!Schema::hasColumn('tds_deductions', 'certificate_number')) {
                    $table->string('certificate_number', 50)->after('bsr_code')->nullable();
                }
                if (!Schema::hasColumn('tds_deductions', 'certificate_date')) {
                    $table->date('certificate_date')->after('certificate_number')->nullable();
                }
                if (!Schema::hasColumn('tds_deductions', 'certificate_path')) {
                    $table->string('certificate_path')->after('certificate_date')->nullable();
                }

                // Add status if not exists
                if (!Schema::hasColumn('tds_deductions', 'status')) {
                    $table->enum('status', ['pending', 'deposited', 'filed', 'certified'])->after('certificate_path')->default('pending');
                }

                // Add metadata and remarks if not exists
                if (!Schema::hasColumn('tds_deductions', 'metadata')) {
                    $table->json('metadata')->after('status')->nullable();
                }
                if (!Schema::hasColumn('tds_deductions', 'remarks')) {
                    $table->text('remarks')->after('metadata')->nullable();
                }

                // Add soft deletes if not exists
                if (!Schema::hasColumn('tds_deductions', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            // Add indexes if they don't exist
            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_tds_fy_quarter ON tds_deductions(financial_year, quarter)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_tds_deduction_date ON tds_deductions(deduction_date)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_tds_status ON tds_deductions(status)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_tds_section_code ON tds_deductions(section_code)');
            } catch (\Exception $e) {
                // Indexes might already exist
            }
        }

        // Create quarterly returns table if not exists
        if (!Schema::hasTable('tds_quarterly_returns')) {
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
    }

    public function down(): void
    {
        // Drop quarterly returns table
        Schema::dropIfExists('tds_quarterly_returns');

        // Revert tds_deductions changes (only drop new columns)
        if (Schema::hasTable('tds_deductions')) {
            Schema::table('tds_deductions', function (Blueprint $table) {
                $table->dropColumn([
                    'quarter',
                    'gross_amount_paise',
                    'tds_amount_paise',
                    'net_amount_paise',
                    'net_amount',
                    'section_code',
                    'pan_available',
                    'deposit_date',
                    'challan_number',
                    'bsr_code',
                    'certificate_number',
                    'certificate_date',
                    'certificate_path',
                    'status',
                    'metadata',
                    'remarks'
                ]);
            });
        }
    }
};
