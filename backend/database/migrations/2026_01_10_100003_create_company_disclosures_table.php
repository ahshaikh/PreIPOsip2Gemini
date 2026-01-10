<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 - MIGRATION 3/6: Create Company Disclosures Table
 *
 * PURPOSE:
 * Creates the modular disclosure instances for each company.
 * Each record represents a company's filling out of a specific disclosure module.
 *
 * KEY CONCEPTS:
 * - MODULAR INSTANCES: Each company has one record per disclosure module
 * - DRAFT → APPROVED LIFECYCLE: Tracks progress from drafting to approval
 * - CURRENT STATE: This table holds the "current" state (not historical versions)
 * - VERSIONING: Historical changes are stored in disclosure_versions table
 *
 * EXAMPLE RECORDS:
 * | id | company_id | module_id | status    | completion_percentage |
 * |----|------------|-----------|-----------|----------------------|
 * | 1  | 42         | 1 (Bus.)  | approved  | 100                  |
 * | 2  | 42         | 2 (Fin.)  | draft     | 65                   |
 * | 3  | 42         | 3 (Risk)  | submitted | 100                  |
 *
 * RELATION TO OTHER TABLES:
 * - disclosure_modules: Template this disclosure is based on
 * - companies: Company that owns this disclosure
 * - disclosure_versions: Historical snapshots of this disclosure
 * - disclosure_clarifications: Questions/answers about this disclosure
 * - disclosure_approvals: Approval workflow records
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_disclosures', function (Blueprint $table) {
            $table->id();

            // =====================================================================
            // OWNERSHIP & MODULE REFERENCE
            // =====================================================================

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company that owns this disclosure');

            $table->foreignId('disclosure_module_id')
                ->constrained('disclosure_modules')
                ->restrictOnDelete()
                ->comment('Template module this disclosure is based on');

            // UNIQUE CONSTRAINT: One active disclosure per module per company
            // Prevents duplicate "Business Model" disclosures for same company
            $table->unique(['company_id', 'disclosure_module_id'], 'uq_company_disclosure_module');

            // =====================================================================
            // DISCLOSURE DATA
            // =====================================================================
            // The actual disclosure content filled by the company

            $table->json('disclosure_data')
                ->comment('Company-provided disclosure data conforming to module JSON schema');

            /**
             * EXAMPLE disclosure_data for "Business Model" module:
             * {
             *   "business_description": "We operate a SaaS platform connecting...",
             *   "revenue_streams": [
             *     {"name": "Subscription Fees", "percentage": 70},
             *     {"name": "Transaction Fees", "percentage": 20},
             *     {"name": "Professional Services", "percentage": 10}
             *   ],
             *   "customer_segments": ["SMB", "Enterprise", "Government"],
             *   "key_partners": ["AWS", "Stripe", "Salesforce"],
             *   "competitive_advantages": ["First-mover in India", "Patent-protected algorithm"]
             * }
             */

            $table->json('attachments')->nullable()
                ->comment('Supporting documents: [{"file_path":"docs/business-plan.pdf","uploaded_at":"2024-01-15"}]');

            // =====================================================================
            // LIFECYCLE & STATUS TRACKING
            // =====================================================================

            $table->enum('status', [
                'draft',                   // Company is filling out
                'submitted',               // Company submitted for review
                'under_review',            // Admin is reviewing
                'clarification_required',  // Admin asked questions
                'resubmitted',            // Company answered clarifications
                'approved',               // Admin approved
                'rejected'                // Admin rejected
            ])->default('draft')
                ->comment('Current lifecycle status of this disclosure');

            $table->unsignedTinyInteger('completion_percentage')->default(0)
                ->comment('Auto-calculated percentage of required fields completed (0-100)');

            $table->boolean('is_locked')->default(false)
                ->comment('Whether this disclosure is locked (after approval or company freeze)');

            // =====================================================================
            // SUBMISSION & APPROVAL TRACKING
            // =====================================================================

            $table->timestamp('submitted_at')->nullable()
                ->comment('When company first submitted this disclosure for review');

            $table->foreignId('submitted_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('CompanyUser who submitted this disclosure');

            $table->timestamp('approved_at')->nullable()
                ->comment('When admin approved this disclosure');

            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who approved this disclosure');

            $table->text('rejection_reason')->nullable()
                ->comment('Admin reason for rejecting this disclosure');

            $table->timestamp('rejected_at')->nullable()
                ->comment('When admin rejected this disclosure');

            $table->foreignId('rejected_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who rejected this disclosure');

            // =====================================================================
            // VERSION TRACKING
            // =====================================================================
            // Links to the versioning system for historical snapshots

            $table->unsignedInteger('version_number')->default(1)
                ->comment('Current version number (increments on each approved change)');

            $table->foreignId('current_version_id')->nullable()
                ->comment('FK to disclosure_versions - current approved snapshot');
            // NOTE: FK constraint added AFTER disclosure_versions table is created (circular dependency)

            $table->timestamp('last_modified_at')->nullable()
                ->comment('When disclosure data was last modified (not status changes)');

            $table->foreignId('last_modified_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who last modified the disclosure data');

            // =====================================================================
            // AUDIT TRAIL
            // =====================================================================

            $table->string('last_modified_ip', 45)->nullable()
                ->comment('IP address of last modifier');

            $table->text('last_modified_user_agent')->nullable()
                ->comment('User agent of last modifier');

            $table->text('internal_notes')->nullable()
                ->comment('Admin-only internal notes about this disclosure');

            // =====================================================================
            // TIMESTAMPS
            // =====================================================================

            $table->timestamps();
            $table->softDeletes();

            // =====================================================================
            // INDEXES
            // =====================================================================

            $table->index('status', 'idx_company_disclosures_status');
            $table->index(['company_id', 'status'], 'idx_company_disclosures_company_status');
            $table->index(['disclosure_module_id', 'status'], 'idx_company_disclosures_module_status');
            $table->index('submitted_at', 'idx_company_disclosures_submitted');
            $table->index('is_locked', 'idx_company_disclosures_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_disclosures');
    }
};
