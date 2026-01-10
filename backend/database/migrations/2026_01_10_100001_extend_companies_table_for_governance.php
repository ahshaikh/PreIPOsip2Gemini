<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 - MIGRATION 1/6: Extend Companies Table for Governance Protocol
 *
 * PURPOSE:
 * Extends the existing companies table with legal identity, governance structure,
 * and SEBI regulatory fields required for Pre-IPO disclosure compliance.
 *
 * BACKWARD COMPATIBILITY:
 * - All new fields are NULLABLE to preserve existing data
 * - No modifications to existing columns
 * - No foreign key constraints that could block existing operations
 *
 * REGULATORY CONTEXT:
 * These fields support SEBI's Pre-IPO disclosure requirements including:
 * - Company legal identity (CIN, PAN, Registration Number)
 * - Board composition and governance structure
 * - SEBI registration and approval tracking
 * - Disclosure lifecycle management
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // =====================================================================
            // LEGAL IDENTITY & REGISTRATION
            // =====================================================================
            // These fields establish the company's legal existence and regulatory identity

            $table->string('cin', 21)->nullable()->unique()->after('slug')
                ->comment('Corporate Identity Number (Ministry of Corporate Affairs)');

            $table->string('pan', 10)->nullable()->unique()->after('cin')
                ->comment('Permanent Account Number (Income Tax Department)');

            $table->string('registration_number', 50)->nullable()->after('pan')
                ->comment('State/ROC registration number for non-corporate entities');

            $table->enum('legal_structure', [
                'private_limited',
                'public_limited',
                'llp',
                'partnership',
                'sole_proprietorship',
                'section_8_company',
                'opc'
            ])->nullable()->after('registration_number')
                ->comment('Legal entity structure affecting disclosure requirements');

            $table->date('incorporation_date')->nullable()->after('founded_year')
                ->comment('Official date of incorporation (vs founded_year marketing field)');

            $table->string('registered_office_address', 500)->nullable()->after('headquarters')
                ->comment('Legal registered office address (regulatory requirement)');

            // =====================================================================
            // GOVERNANCE STRUCTURE
            // =====================================================================
            // Board composition fields required for corporate governance disclosures

            $table->unsignedInteger('board_size')->nullable()->after('ceo_name')
                ->comment('Total number of board members');

            $table->unsignedInteger('independent_directors')->nullable()->after('board_size')
                ->comment('Number of independent directors (SEBI requirement)');

            $table->json('board_committees')->nullable()->after('independent_directors')
                ->comment('Board committees: [{"name":"Audit Committee","members":3}]');

            $table->string('company_secretary', 255)->nullable()->after('board_committees')
                ->comment('Company Secretary name (mandatory for certain entity types)');

            // =====================================================================
            // SEBI & REGULATORY COMPLIANCE
            // =====================================================================
            // Fields tracking SEBI registration and regulatory approval status

            $table->boolean('sebi_registered')->default(false)->after('company_secretary')
                ->comment('Whether company is registered with SEBI');

            $table->string('sebi_registration_number', 50)->nullable()->after('sebi_registered')
                ->comment('SEBI registration number if applicable');

            $table->date('sebi_approval_date')->nullable()->after('sebi_registration_number')
                ->comment('Date of SEBI approval for Pre-IPO offering');

            $table->date('sebi_approval_expiry')->nullable()->after('sebi_approval_date')
                ->comment('Expiry date of SEBI approval (typically 12 months)');

            $table->json('regulatory_approvals')->nullable()->after('sebi_approval_expiry')
                ->comment('Other regulatory approvals: [{"authority":"RBI","approval_number":"RBI/2024/123","date":"2024-01-15"}]');

            // =====================================================================
            // DISCLOSURE LIFECYCLE MANAGEMENT
            // =====================================================================
            // State machine for tracking company's progress through disclosure workflow

            $table->enum('disclosure_stage', [
                'draft',              // Initial state - company filling out disclosures
                'submitted',          // Company submitted for admin review
                'under_review',       // Admin reviewing disclosures
                'clarification_required', // Admin requested clarifications
                'resubmitted',        // Company answered clarifications
                'approved',           // Admin approved - company goes live
                'rejected',           // Admin rejected - cannot list
                'suspended'           // Admin suspended after approval (compliance issue)
            ])->default('draft')->after('status')
                ->comment('Current stage in disclosure approval workflow');

            $table->timestamp('disclosure_submitted_at')->nullable()->after('disclosure_stage')
                ->comment('When company first submitted disclosures for review');

            $table->timestamp('disclosure_approved_at')->nullable()->after('disclosure_submitted_at')
                ->comment('When admin approved disclosures (company goes live)');

            $table->foreignId('disclosure_approved_by')->nullable()->after('disclosure_approved_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin user who approved the disclosures');

            $table->text('disclosure_rejection_reason')->nullable()->after('disclosure_approved_by')
                ->comment('Admin reason for rejecting disclosures');

            // =====================================================================
            // PLATFORM CONTROLS (Already Exists - Documented for Completeness)
            // =====================================================================
            // Note: These fields already exist in companies table but are part of governance
            // - status: enum('active','inactive') - Platform visibility control
            // - is_verified: boolean - Platform verification badge
            // - is_featured: boolean - Platform editorial judgment
            // - frozen_at: timestamp - Immutability enforcement (added 2026-01-07)
            // - frozen_by_admin_id: FK users - Who froze the company

            // =====================================================================
            // AUDIT TRAIL ENHANCEMENT
            // =====================================================================
            // Additional metadata for regulatory audit requirements

            $table->string('last_modified_by_ip', 45)->nullable()->after('updated_at')
                ->comment('IP address of last modifier (IPv4 or IPv6)');

            $table->text('last_modified_user_agent')->nullable()->after('last_modified_by_ip')
                ->comment('User agent of last modifier for audit trail');

            // Indexes for performance and uniqueness
            $table->index('disclosure_stage', 'idx_companies_disclosure_stage');
            $table->index(['sebi_registered', 'disclosure_stage'], 'idx_companies_sebi_disclosure');
            $table->index('disclosure_submitted_at', 'idx_companies_disclosure_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_companies_disclosure_stage');
            $table->dropIndex('idx_companies_sebi_disclosure');
            $table->dropIndex('idx_companies_disclosure_submitted');

            // Drop foreign key
            $table->dropForeign(['disclosure_approved_by']);

            // Drop all added columns
            $table->dropColumn([
                // Legal Identity
                'cin',
                'pan',
                'registration_number',
                'legal_structure',
                'incorporation_date',
                'registered_office_address',

                // Governance
                'board_size',
                'independent_directors',
                'board_committees',
                'company_secretary',

                // SEBI
                'sebi_registered',
                'sebi_registration_number',
                'sebi_approval_date',
                'sebi_approval_expiry',
                'regulatory_approvals',

                // Disclosure Lifecycle
                'disclosure_stage',
                'disclosure_submitted_at',
                'disclosure_approved_at',
                'disclosure_approved_by',
                'disclosure_rejection_reason',

                // Audit Trail
                'last_modified_by_ip',
                'last_modified_user_agent',
            ]);
        });
    }
};
