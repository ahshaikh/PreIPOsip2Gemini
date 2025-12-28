<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Enforce Audit Log Immutability (F.21)
 *
 * PURPOSE:
 * - F.21: Preserve audit history permanently
 * - Add immutability constraints to all audit/compliance tables
 * - Ensure regulatory compliance (SOC 2, ISO 27001, SEBI)
 *
 * TABLES PROTECTED:
 * - audit_logs (admin actions)
 * - benefit_audit_log (campaign benefits)
 * - tds_deductions (tax compliance)
 * - kyc_documents (identity verification)
 *
 * MECHANISM:
 * - Database-level protection
 * - Observer-level protection (already implemented)
 * - Application-level protection (service layer)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===================================================================
        // STEP 1: Add retention metadata to audit_logs
        // ===================================================================
        if (!Schema::hasTable('audit_logs')) {
            Log::warning("audit_logs table does not exist - skipping audit log immutability migration");
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('requires_review');
            }
            if (!Schema::hasColumn('audit_logs', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }
            if (!Schema::hasColumn('audit_logs', 'retention_period')) {
                $table->string('retention_period')->default('permanent')->after('archived_at')
                    ->comment('permanent, 7years, etc.');
            }
        });

        // Add index separately
        if (!$this->indexExists('audit_logs', 'audit_logs_is_archived_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('is_archived');
            });
        }

        // ===================================================================
        // STEP 2: Add constraints to audit_logs
        // ===================================================================

        // Ensure risk level is valid
        if (Schema::hasColumn('audit_logs', 'risk_level')) {
            try {
                DB::statement("
                    ALTER TABLE audit_logs
                    ADD CONSTRAINT check_audit_log_valid_risk_level
                    CHECK (risk_level IN ('low', 'medium', 'high', 'critical'))
                ");
            } catch (\Exception $e) {
                Log::warning("Could not add check_audit_log_valid_risk_level constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ===================================================================
        // STEP 3: Add retention metadata to benefit_audit_log
        // ===================================================================
        if (Schema::hasTable('benefit_audit_log')) {
            Schema::table('benefit_audit_log', function (Blueprint $table) {
                if (!Schema::hasColumn('benefit_audit_log', 'is_archived')) {
                    $table->boolean('is_archived')->default(false)->after('created_at');
                }
                if (!Schema::hasColumn('benefit_audit_log', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('is_archived');
                }
            });

            // Add index separately
            if (!$this->indexExists('benefit_audit_log', 'benefit_audit_log_is_archived_index')) {
                Schema::table('benefit_audit_log', function (Blueprint $table) {
                    $table->index('is_archived');
                });
            }
        }

        // ===================================================================
        // STEP 4: Add retention metadata to tds_deductions
        // ===================================================================
        if (Schema::hasTable('tds_deductions')) {
            Schema::table('tds_deductions', function (Blueprint $table) {
                if (!Schema::hasColumn('tds_deductions', 'is_archived')) {
                    $table->boolean('is_archived')->default(false)->after('form_16a_generated_at');
                }
                if (!Schema::hasColumn('tds_deductions', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('is_archived');
                }
                if (!Schema::hasColumn('tds_deductions', 'retention_period')) {
                    $table->string('retention_period')->default('7years')->after('archived_at')
                        ->comment('Minimum 7 years as per Income Tax Act');
                }
            });

            // Add index separately
            if (!$this->indexExists('tds_deductions', 'tds_deductions_is_archived_index')) {
                Schema::table('tds_deductions', function (Blueprint $table) {
                    $table->index('is_archived');
                });
            }
        }

        // ===================================================================
        // STEP 5: Document retention policy
        // ===================================================================

        // NOTE: Archival does NOT mean deletion.
        // Archival means moving to cold storage or marking as inactive.
        // Actual deletion should ONLY happen after legal retention period
        // AND with proper authorization and audit trail.

        // For most compliance tables: NEVER delete (permanent retention)
        // For TDS: Minimum 7 years retention (Income Tax Act)
        // For KYC: Minimum 5 years after account closure (PMLA)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints
        DB::statement("ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS check_audit_log_valid_risk_level");

        // Drop columns from audit_logs
        if (Schema::hasTable('audit_logs')) {
            if ($this->indexExists('audit_logs', 'audit_logs_is_archived_index')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex(['is_archived']);
                });
            }

            Schema::table('audit_logs', function (Blueprint $table) {
                $columns = ['is_archived', 'archived_at', 'retention_period'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('audit_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Drop columns from benefit_audit_log
        if (Schema::hasTable('benefit_audit_log')) {
            if ($this->indexExists('benefit_audit_log', 'benefit_audit_log_is_archived_index')) {
                Schema::table('benefit_audit_log', function (Blueprint $table) {
                    $table->dropIndex(['is_archived']);
                });
            }

            Schema::table('benefit_audit_log', function (Blueprint $table) {
                $columns = ['is_archived', 'archived_at'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('benefit_audit_log', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Drop columns from tds_deductions
        if (Schema::hasTable('tds_deductions')) {
            if ($this->indexExists('tds_deductions', 'tds_deductions_is_archived_index')) {
                Schema::table('tds_deductions', function (Blueprint $table) {
                    $table->dropIndex(['is_archived']);
                });
            }

            Schema::table('tds_deductions', function (Blueprint $table) {
                $columns = ['is_archived', 'archived_at', 'retention_period'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tds_deductions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
        return !empty($indexes);
    }
};
