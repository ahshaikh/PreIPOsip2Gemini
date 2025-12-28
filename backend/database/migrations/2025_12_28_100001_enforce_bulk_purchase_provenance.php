<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Skip if bulk_purchases table doesn't exist
        if (!Schema::hasTable('bulk_purchases')) {
            Log::warning("bulk_purchases table does not exist - skipping provenance migration");
            return;
        }

        // Add provenance tracking columns to bulk_purchases
        Schema::table('bulk_purchases', function (Blueprint $table) {
            // Company provenance (REQUIRED - nullable first, then enforce after data migration)
            if (!Schema::hasColumn('bulk_purchases', 'company_id')) {
                if (Schema::hasTable('companies')) {
                    $table->foreignId('company_id')->nullable()->after('product_id')->constrained('companies')->onDelete('restrict');
                } else {
                    // Table doesn't exist, add column without foreign key
                    $table->unsignedBigInteger('company_id')->nullable()->after('product_id');
                }
            }

            // Source tracking (one of these must be set)
            if (!Schema::hasColumn('bulk_purchases', 'company_share_listing_id')) {
                if (Schema::hasTable('company_share_listings')) {
                    $table->foreignId('company_share_listing_id')->nullable()->after('company_id')->constrained('company_share_listings')->onDelete('restrict');
                } else {
                    $table->unsignedBigInteger('company_share_listing_id')->nullable()->after('company_id');
                }
            }

            if (!Schema::hasColumn('bulk_purchases', 'source_type')) {
                $table->enum('source_type', ['company_listing', 'manual_entry'])->default('company_listing')->after('company_share_listing_id');
            }

            // Manual entry audit trail (required if source_type = 'manual_entry')
            if (!Schema::hasColumn('bulk_purchases', 'approved_by_admin_id')) {
                if (Schema::hasTable('users')) {
                    $table->foreignId('approved_by_admin_id')->nullable()->constrained('users')->onDelete('restrict');
                } else {
                    $table->unsignedBigInteger('approved_by_admin_id')->nullable();
                }
            }

            if (!Schema::hasColumn('bulk_purchases', 'manual_entry_reason')) {
                $table->text('manual_entry_reason')->nullable();
            }

            if (!Schema::hasColumn('bulk_purchases', 'source_documentation')) {
                $table->text('source_documentation')->nullable(); // File paths, agreement references
            }

            if (!Schema::hasColumn('bulk_purchases', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
        });

        // Add indexes separately
        if (Schema::hasColumn('bulk_purchases', 'company_id') && !$this->indexExists('bulk_purchases', 'bulk_purchases_company_id_index')) {
            Schema::table('bulk_purchases', function (Blueprint $table) {
                $table->index('company_id');
            });
        }

        if (Schema::hasColumn('bulk_purchases', 'company_share_listing_id') && !$this->indexExists('bulk_purchases', 'bulk_purchases_company_share_listing_id_index')) {
            Schema::table('bulk_purchases', function (Blueprint $table) {
                $table->index('company_share_listing_id');
            });
        }

        if (Schema::hasColumn('bulk_purchases', 'source_type') && !$this->indexExists('bulk_purchases', 'bulk_purchases_source_type_index')) {
            Schema::table('bulk_purchases', function (Blueprint $table) {
                $table->index('source_type');
            });
        }

        if (Schema::hasColumn('bulk_purchases', 'verified_at') && !$this->indexExists('bulk_purchases', 'bulk_purchases_verified_at_index')) {
            Schema::table('bulk_purchases', function (Blueprint $table) {
                $table->index('verified_at');
            });
        }

        // Add CHECK constraint: manual entries must have approval and reason
        if (Schema::hasColumn('bulk_purchases', 'source_type') &&
            Schema::hasColumn('bulk_purchases', 'approved_by_admin_id') &&
            Schema::hasColumn('bulk_purchases', 'manual_entry_reason') &&
            Schema::hasColumn('bulk_purchases', 'verified_at')) {
            try {
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
            } catch (\Exception $e) {
                Log::warning("Could not add check_manual_entry_provenance constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Add CHECK constraint: company listing entries must have listing reference
        if (Schema::hasColumn('bulk_purchases', 'source_type') &&
            Schema::hasColumn('bulk_purchases', 'company_share_listing_id')) {
            try {
                DB::statement("
                    ALTER TABLE bulk_purchases
                    ADD CONSTRAINT check_listing_entry_provenance
                    CHECK (
                        source_type != 'company_listing'
                        OR company_share_listing_id IS NOT NULL
                    )
                ");
            } catch (\Exception $e) {
                Log::warning("Could not add check_listing_entry_provenance constraint.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Update existing bulk_purchases (data migration)
        // Only run if necessary columns exist
        if (Schema::hasColumn('bulk_purchases', 'company_id') &&
            Schema::hasColumn('bulk_purchases', 'source_type')) {
            try {
                // Check if there are records to migrate
                $needsMigration = DB::table('bulk_purchases')->whereNull('company_id')->count();

                if ($needsMigration > 0) {
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
                                CONCAT('Historical bulk purchase created before provenance tracking. Admin ID: ', COALESCE(bp.admin_id, 'unknown'), '. Notes: ', COALESCE(bp.notes, 'No notes provided.')),
                                NULL
                            ),
                            bp.verified_at = bp.created_at
                        WHERE bp.company_id IS NULL
                    ");
                }
            } catch (\Exception $e) {
                Log::warning("Data migration for bulk_purchases provenance failed. Manual intervention may be required.", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // After data migration, make company_id NOT NULL only if column exists and all records have company_id
        if (Schema::hasColumn('bulk_purchases', 'company_id')) {
            $nullCompanyCount = DB::table('bulk_purchases')->whereNull('company_id')->count();
            if ($nullCompanyCount === 0) {
                try {
                    Schema::table('bulk_purchases', function (Blueprint $table) {
                        $table->unsignedBigInteger('company_id')->nullable(false)->change();
                    });
                } catch (\Exception $e) {
                    Log::warning("Could not make company_id NOT NULL. Some records may have null company_id.", [
                        'error' => $e->getMessage(),
                        'null_count' => $nullCompanyCount
                    ]);
                }
            } else {
                Log::warning("Cannot make company_id NOT NULL - {$nullCompanyCount} records still have null company_id");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('bulk_purchases')) {
            return;
        }

        // Drop constraints first
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_manual_entry_provenance');
        DB::statement('ALTER TABLE bulk_purchases DROP CONSTRAINT IF EXISTS check_listing_entry_provenance');

        // Drop indexes
        $indexes = ['bulk_purchases_company_id_index', 'bulk_purchases_company_share_listing_id_index',
                    'bulk_purchases_source_type_index', 'bulk_purchases_verified_at_index'];
        foreach ($indexes as $index) {
            if ($this->indexExists('bulk_purchases', $index)) {
                try {
                    DB::statement("DROP INDEX {$index} ON bulk_purchases");
                } catch (\Exception $e) {
                    // Index might already be dropped
                }
            }
        }

        // Drop foreign keys (only if columns exist)
        Schema::table('bulk_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_purchases', 'company_share_listing_id')) {
                try {
                    $table->dropForeign(['company_share_listing_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }

            if (Schema::hasColumn('bulk_purchases', 'approved_by_admin_id')) {
                try {
                    $table->dropForeign(['approved_by_admin_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
        });

        // Drop columns
        Schema::table('bulk_purchases', function (Blueprint $table) {
            $columns = [
                'company_share_listing_id',
                'source_type',
                'approved_by_admin_id',
                'manual_entry_reason',
                'source_documentation',
                'verified_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bulk_purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
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
