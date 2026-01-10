<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 - MIGRATION 4/6: Create Disclosure Versions Table
 *
 * PURPOSE:
 * Creates the immutable historical snapshots of company disclosures.
 * Every approved change to a company_disclosure creates a new version record.
 *
 * KEY CONCEPTS:
 * - IMMUTABILITY: Once created, version records are NEVER modified or deleted
 * - REGULATORY COMPLIANCE: Provides audit trail for investor protection
 * - SNAPSHOT STORAGE: Full copy of disclosure_data at approval time
 * - CHANGE TRACKING: Records what changed and why
 *
 * IMMUTABILITY ENFORCEMENT:
 * - is_locked flag set to true on creation
 * - Model Observer prevents updates/deletes
 * - Audit log records any attempted modifications
 *
 * EXAMPLE USE CASE:
 * 1. Company creates "Business Model" disclosure (version 1)
 * 2. Admin approves → disclosure_versions record created
 * 3. Company later updates revenue streams (version 2)
 * 4. Admin approves → new disclosure_versions record created
 * 5. Investor dispute → Can show exact data at purchase time
 *
 * RELATION TO OTHER TABLES:
 * - company_disclosures: Parent disclosure (current state)
 * - companies: Company that owns the disclosure
 * - disclosure_modules: Template module
 * - company_disclosures.current_version_id → this table (circular FK)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disclosure_versions', function (Blueprint $table) {
            $table->id();

            // =====================================================================
            // PARENT REFERENCES
            // =====================================================================

            $table->foreignId('company_disclosure_id')
                ->constrained('company_disclosures')
                ->restrictOnDelete()
                ->comment('Parent disclosure record (current state)');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete()
                ->comment('Denormalized for query performance (company ownership)');

            $table->foreignId('disclosure_module_id')
                ->constrained('disclosure_modules')
                ->restrictOnDelete()
                ->comment('Denormalized for query performance (module type)');

            // =====================================================================
            // VERSION METADATA
            // =====================================================================

            $table->unsignedInteger('version_number')
                ->comment('Sequential version number (1, 2, 3...) per disclosure');

            $table->string('version_hash', 64)
                ->comment('SHA-256 hash of disclosure_data for tamper detection');

            // UNIQUE CONSTRAINT: One version number per disclosure
            $table->unique(['company_disclosure_id', 'version_number'], 'uq_disclosure_version_number');

            // =====================================================================
            // IMMUTABLE SNAPSHOT DATA
            // =====================================================================
            // Complete copy of disclosure state at approval time

            $table->json('disclosure_data')
                ->comment('IMMUTABLE: Full snapshot of disclosure data at this version');

            $table->json('attachments')->nullable()
                ->comment('IMMUTABLE: Supporting documents at this version');

            $table->json('changes_summary')->nullable()
                ->comment('What changed from previous version: {"revenue_streams":"Updated Q3 data","customer_segments":"Added Enterprise"}');

            $table->text('change_reason')->nullable()
                ->comment('Company-provided reason for this change (required for v2+)');

            // =====================================================================
            // IMMUTABILITY ENFORCEMENT
            // =====================================================================

            $table->boolean('is_locked')->default(true)
                ->comment('IMMUTABILITY FLAG: Always true, prevents any modifications');

            $table->timestamp('locked_at')->nullable()
                ->comment('When this version was locked (set on creation)');

            // =====================================================================
            // APPROVAL TRACKING
            // =====================================================================
            // Who approved this version and when

            $table->timestamp('approved_at')
                ->comment('When admin approved this version');

            $table->foreignId('approved_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Admin who approved this version (REQUIRED)');

            $table->text('approval_notes')->nullable()
                ->comment('Admin notes from approval review');

            // =====================================================================
            // REGULATORY & COMPLIANCE
            // =====================================================================

            $table->boolean('was_investor_visible')->default(false)
                ->comment('Whether this version was ever visible to investors (for liability tracking)');

            $table->timestamp('first_investor_view_at')->nullable()
                ->comment('When first investor viewed this version (for disclosure timing compliance)');

            $table->unsignedInteger('investor_view_count')->default(0)
                ->comment('How many times investors viewed this version (materiality assessment)');

            $table->json('linked_transactions')->nullable()
                ->comment('Investor purchases made under this version: [{"transaction_id":123,"date":"2024-01-15"}]');

            // =====================================================================
            // DOCUMENT LINKAGE
            // =====================================================================
            // Link to external regulatory filings or certifications

            $table->string('sebi_filing_reference', 100)->nullable()
                ->comment('Reference to SEBI filing (if this version was filed)');

            $table->timestamp('sebi_filed_at')->nullable()
                ->comment('When this version was filed with SEBI');

            $table->json('certification')->nullable()
                ->comment('Digital signature/certification: {"signed_by":"CEO","signature_hash":"...","timestamp":"..."}');

            // =====================================================================
            // AUDIT TRAIL
            // =====================================================================

            $table->string('created_by_ip', 45)->nullable()
                ->comment('IP address when version was created');

            $table->text('created_by_user_agent')->nullable()
                ->comment('User agent when version was created');

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('CompanyUser who triggered this version creation');

            // =====================================================================
            // TIMESTAMPS
            // =====================================================================

            $table->timestamps();

            // NOTE: NO softDeletes() - versions must NEVER be deleted
            // Regulatory requirement: Permanent record retention

            // =====================================================================
            // INDEXES
            // =====================================================================

            $table->index(['company_disclosure_id', 'version_number'], 'idx_disclosure_versions_lookup');
            $table->index(['company_id', 'approved_at'], 'idx_disclosure_versions_company_timeline');
            $table->index('version_hash', 'idx_disclosure_versions_hash');
            $table->index('was_investor_visible', 'idx_disclosure_versions_investor_visible');
            $table->index('sebi_filing_reference', 'idx_disclosure_versions_sebi');
        });

        // =====================================================================
        // ADD CIRCULAR FOREIGN KEY
        // =====================================================================
        // Now that disclosure_versions exists, add FK from company_disclosures

        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('disclosure_versions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop circular FK first
        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('disclosure_versions');
    }
};
