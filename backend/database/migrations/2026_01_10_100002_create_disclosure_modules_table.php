<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 - MIGRATION 2/6: Create Disclosure Modules Table
 *
 * PURPOSE:
 * Creates the template structure for modular disclosure requirements.
 * Disclosure modules define the structure and validation rules for different
 * types of company disclosures (business, financials, risks, governance, etc.)
 *
 * KEY CONCEPTS:
 * - TEMPLATE SYSTEM: Modules are reusable templates, not company-specific data
 * - JSON SCHEMA VALIDATION: Each module defines structure via JSON schema
 * - SEBI ALIGNMENT: Modules map to SEBI's Pre-IPO disclosure categories
 * - ADMIN MANAGED: Only admins can create/modify modules
 *
 * EXAMPLE MODULES:
 * - "Business Model & Operations" (required, order: 1)
 * - "Financial Performance" (required, order: 2)
 * - "Risk Factors" (required, order: 3)
 * - "Board & Management" (required, order: 4)
 * - "Legal & Compliance" (optional, order: 5)
 *
 * RELATION TO OTHER TABLES:
 * - disclosure_modules (this table) = Templates
 * - company_disclosures = Company's instance of a module
 * - disclosure_versions = Historical snapshots of company_disclosures
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disclosure_modules', function (Blueprint $table) {
            $table->id();

            // =====================================================================
            // MODULE IDENTITY
            // =====================================================================

            $table->string('code', 50)->unique()
                ->comment('Unique code: business_model, financials, risks, governance, legal');

            $table->string('name', 255)
                ->comment('Display name: "Business Model & Operations"');

            $table->text('description')->nullable()
                ->comment('Admin-facing description of what this module captures');

            $table->text('help_text')->nullable()
                ->comment('Company-facing instructions for filling out this module');

            // =====================================================================
            // MODULE CONFIGURATION
            // =====================================================================

            $table->boolean('is_required')->default(true)
                ->comment('Whether companies must complete this module to submit');

            $table->boolean('is_active')->default(true)
                ->comment('Whether this module is currently in use (for deprecation)');

            $table->unsignedInteger('display_order')->default(999)
                ->comment('Order in which modules appear in company disclosure flow');

            $table->string('icon', 50)->nullable()
                ->comment('Icon identifier for frontend display (e.g., "building", "chart-line")');

            $table->string('color', 20)->nullable()
                ->comment('Color code for frontend theming (e.g., "blue", "#3B82F6")');

            // =====================================================================
            // JSON SCHEMA VALIDATION
            // =====================================================================
            // Defines the structure and validation rules for disclosure data

            $table->json('json_schema')
                ->comment('JSON Schema v7 defining structure, validation rules, required fields');

            /**
             * EXAMPLE JSON SCHEMA for "Business Model" module:
             * {
             *   "$schema": "http://json-schema.org/draft-07/schema#",
             *   "type": "object",
             *   "required": ["business_description", "revenue_streams", "customer_segments"],
             *   "properties": {
             *     "business_description": {
             *       "type": "string",
             *       "minLength": 100,
             *       "maxLength": 5000,
             *       "description": "Detailed description of business model"
             *     },
             *     "revenue_streams": {
             *       "type": "array",
             *       "minItems": 1,
             *       "items": {
             *         "type": "object",
             *         "required": ["name", "percentage"],
             *         "properties": {
             *           "name": {"type": "string"},
             *           "percentage": {"type": "number", "minimum": 0, "maximum": 100}
             *         }
             *       }
             *     },
             *     "customer_segments": {
             *       "type": "array",
             *       "items": {"type": "string"}
             *     }
             *   }
             * }
             */

            $table->json('default_data')->nullable()
                ->comment('Default/template data structure for new disclosures (optional)');

            // =====================================================================
            // REGULATORY MAPPING
            // =====================================================================

            $table->string('sebi_category', 100)->nullable()
                ->comment('Maps to SEBI disclosure category (for regulatory reporting)');

            $table->json('regulatory_references')->nullable()
                ->comment('References to SEBI regulations: [{"regulation":"ICDR","section":"26(1)","description":"..."}]');

            // =====================================================================
            // APPROVAL WORKFLOW CONFIGURATION
            // =====================================================================

            $table->boolean('requires_admin_approval')->default(true)
                ->comment('Whether changes to this module require admin approval');

            $table->unsignedInteger('min_approval_reviews')->default(1)
                ->comment('Minimum number of admin reviews required (future: multi-approver)');

            $table->json('approval_checklist')->nullable()
                ->comment('Checklist items admin must verify: ["Verify revenue figures", "Check risk disclosures"]');

            // =====================================================================
            // AUDIT & METADATA
            // =====================================================================

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who created this module');

            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who last modified this module');

            $table->timestamps();
            $table->softDeletes();

            // =====================================================================
            // INDEXES
            // =====================================================================

            $table->index(['is_active', 'is_required', 'display_order'], 'idx_disclosure_modules_active');
            $table->index('sebi_category', 'idx_disclosure_modules_sebi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disclosure_modules');
    }
};
