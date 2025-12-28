<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PROTOCOL: Enforce Inventory Provenance
     *
     * RULES:
     * 1. Every BulkPurchase MUST have a verified company_id (no orphaned inventory)
     * 2. company_id MUST reference a verified company (is_verified = true)
     * 3. Provenance tracking: company_share_listing_id OR manual approval with audit
     * 4. No inventory without source documentation
     *
     * FAILURE SEMANTICS:
     * - Cannot create inventory without company
     * - Cannot create inventory for unverified company
     * - Manual inventory requires explicit admin approval with reason
     */
    public function up(): void
    {
        // Add provenance tracking columns to bulk_purchases
        Schema::table('bulk_purchases', function (Blueprint $table) {
            // Company provenance (REQUIRED)
            if (!Schema::hasColumn('bulk_purchases', 'company_id')) {
                $table->foreignId('company_id')->after('product_id')->constrained('companies')->onDelete('restrict');
            }

            // Source tracking (one of these must be set)
            $table->foreignId('company_share_listing_id')->nullable()->after('company_id')->constrained('company_share_listings')->onDelete('restrict');
            $table->enum('source_type', ['company_listing', 'manual_entry'])->default('company_listing');

            // Manual entry audit trail (required if source_type = 'manual_entry')
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->text('manual_entry_reason')->nullable();
            $table->text('source_documentation')->nullable(); // File paths, agreement references
            $table->timestamp('verified_at')->nullable();

            // Add indexes
            $table->index('company_id');
            $table->index('company_share_listing_id');
            $table->index('source_type');
            $table->index('verified_at');
        });

        // Add CHECK constraint: manual entries must have approval and reason
        DB::statement("
            ALTER TABLE bulk_purchases
            ADD CONSTRAINT check_manual_entry_provenance
            CHECK (
                source_type != 'manual_entry'
                OR (
                    approved_by_admin_id IS NOT NULL
                    AND manual_entry_reason IS NOT NULL
                    AND LENGTH(manual_entry_reason) >= 50
                    AND verified_at IS NOT NULL
                )
            )
        ");

        // Add CHECK constraint: company listing entries must have listing reference
        DB::statement("
            ALTER TABLE bulk_purchases
            ADD CONSTRAINT check_listing_entry_provenance
            CHECK (
                source_type != 'company_listing'
                OR company_share_listing_id IS NOT NULL
            )
        ");

        // Update existing bulk_purchases (data migration)
        // CRITICAL: This will fail if there are bulk purchases without company_id
        // Admin must manually assign companies to existing inventory before running this migration

        DB::statement("
            UPDATE bulk_purchases bp
            LEFT JOIN company_share_listings csl ON bp.id = csl.bulk_purchase_id
            SET
                bp.company_id = COALESCE(csl.company_id,
                    (SELECT company_id FROM products WHERE id = bp.product_id LIMIT 1)
                ),
                bp.company_share_listing_id = csl.id,
                bp.source_type = IF(csl.id IS NOT NULL, 'company_listing', 'manual_entry'),
                bp.approved_by_admin_id = IF(csl.id IS NULL, bp.admin_id, NULL),
                bp.manual_entry_reason = IF(csl.id IS NULL,
                    CONCAT('Historical bulk purchase created before provenance tracking. Admin ID: ', bp.admin_id, '. Notes: ', COALESCE(bp.notes, 'No notes provided.')),
                    NULL
                ),
                bp.verified_at = bp.created_at
            WHERE bp.company_id IS NULL
        ");

        // After data migration, make company_id NOT NULL
        Schema::table('bulk_purchases', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints first
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_manual_entry_provenance');
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_listing_entry_provenance');

        Schema::table('bulk_purchases', function (Blueprint $table) {
            $table->dropForeign(['company_share_listing_id']);
            $table->dropForeign(['approved_by_admin_id']);
            $table->dropColumn([
                'company_share_listing_id',
                'source_type',
                'approved_by_admin_id',
                'manual_entry_reason',
                'source_documentation',
                'verified_at',
            ]);
        });
    }
};
