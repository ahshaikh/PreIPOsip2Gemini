<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 - MIGRATION: Company Roles and Error Reporting
 *
 * PURPOSE:
 * Implements issuer-side role-based access control and error reporting system.
 *
 * COMPANY ROLES:
 * - founder: Full access, can submit disclosures
 * - finance: Can edit financial disclosures
 * - legal: Can edit legal/compliance disclosures
 * - viewer: Read-only access
 *
 * ERROR REPORTING:
 * - Self-reported corrections tracked separately
 * - Original approved data never modified
 * - Admin notified of all error reports
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================================
        // COMPANY USER ROLES
        // =====================================================================
        Schema::create('company_user_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User with role');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company user belongs to');

            $table->enum('role', ['founder', 'finance', 'legal', 'viewer'])
                ->comment('User role in company');

            $table->boolean('is_active')->default(true)
                ->comment('Role is currently active');

            $table->foreignId('assigned_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Who assigned this role');

            $table->timestamp('assigned_at')->useCurrent()
                ->comment('When role was assigned');

            $table->timestamp('revoked_at')->nullable()
                ->comment('When role was revoked');

            $table->timestamps();

            // Unique constraint: User can only have one active role per company
            $table->unique(['user_id', 'company_id', 'is_active'], 'unique_active_user_company_role');

            // Indexes
            $table->index(['company_id', 'role'], 'idx_company_user_roles_company_role');
            $table->index('user_id', 'idx_company_user_roles_user');
        });

        // =====================================================================
        // DISCLOSURE ERROR REPORTS
        // =====================================================================
        Schema::create('disclosure_error_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_disclosure_id')
                ->constrained('company_disclosures')
                ->cascadeOnDelete()
                ->comment('Original approved disclosure with error');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company reporting error');

            $table->foreignId('reported_by')
		->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who reported error');

            $table->timestamp('reported_at')
                ->comment('When error was reported');

            $table->text('error_description')
                ->comment('Description of what was wrong');

            $table->text('correction_reason')
                ->comment('Why correction is needed');

            $table->json('original_data')
                ->comment('Snapshot of approved data with error');

            $table->json('corrected_data')
                ->comment('Proposed corrected data');

            // Admin review
            $table->text('admin_notes')->nullable()
                ->comment('Admin response to error report');

            $table->foreignId('admin_reviewed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who reviewed error report');

            $table->timestamp('admin_reviewed_at')->nullable()
                ->comment('When admin reviewed error report');

            // Audit fields
            $table->string('ip_address', 45)->nullable()
                ->comment('IP address of reporter');

            $table->text('user_agent')->nullable()
                ->comment('User agent of reporter');

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'reported_at'], 'idx_error_reports_company_timeline');
            $table->index('company_disclosure_id', 'idx_error_reports_disclosure');
            $table->index('admin_reviewed_at', 'idx_error_reports_reviewed');
        });

        // =====================================================================
        // ADD FIELDS TO COMPANY_DISCLOSURES
        // =====================================================================
        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->integer('supersedes_disclosure_id')->nullable()->after('version_number')
                ->comment('Previous disclosure ID that this one replaces');

            $table->boolean('created_from_error_report')->default(false)->after('supersedes_disclosure_id')
                ->comment('Whether this disclosure was created from error report');

            $table->foreignId('error_report_id')->nullable()->after('created_from_error_report')
                ->constrained('disclosure_error_reports')
                ->nullOnDelete()
                ->comment('Error report that triggered this disclosure');

            $table->json('draft_edit_history')->nullable()->after('internal_notes')
                ->comment('Log of all edits made while in draft status');

            $table->text('submission_notes')->nullable()->after('draft_edit_history')
                ->comment('Notes provided by company when submitting for review');
        });

        // =====================================================================
        // ROLE PERMISSIONS MAPPING (Data)
        // =====================================================================
        // This will be handled at application level, but document here:
        /*
        ROLE PERMISSIONS:

        founder:
          - View all disclosures
          - Edit all disclosures (draft/rejected/clarification_required)
          - Submit disclosures for review
          - Answer clarifications
          - Report errors
          - Attach documents
          - Manage company users

        finance:
          - View all disclosures
          - Edit financial disclosures (Tier 2 modules)
          - Submit financial disclosures
          - Answer financial clarifications
          - Report errors in financial disclosures
          - Attach financial documents

        legal:
          - View all disclosures
          - Edit legal/compliance disclosures
          - Submit legal disclosures
          - Answer legal clarifications
          - Report errors in legal disclosures
          - Attach legal documents

        viewer:
          - View all disclosures (read-only)
          - View clarifications (read-only)
          - No edit permissions
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->dropForeign(['error_report_id']);
            $table->dropColumn([
                'supersedes_disclosure_id',
                'created_from_error_report',
                'error_report_id',
                'draft_edit_history',
                'submission_notes',
            ]);
        });

        Schema::dropIfExists('disclosure_error_reports');
        Schema::dropIfExists('company_user_roles');
    }
};
