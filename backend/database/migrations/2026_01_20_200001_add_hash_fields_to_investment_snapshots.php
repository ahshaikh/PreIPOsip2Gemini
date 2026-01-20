<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Cryptographic Hash Fields to Investment Disclosure Snapshots
 *
 * PURPOSE:
 * Add hash_algorithm and snapshot_hash fields for tamper detection and
 * immutability verification of investment snapshots.
 *
 * GOVERNANCE REQUIREMENT:
 * - Every investment snapshot must have a cryptographic hash
 * - Hash computed from database-stored disclosure_snapshot value
 * - Enables verification that snapshot hasn't been tampered with
 * - Critical for dispute resolution and regulatory compliance
 *
 * ARCHITECTURAL PRINCIPLE:
 * "Hash What's Actually Stored, Not What You Think You're Storing"
 * - Hash is computed AFTER database storage
 * - Ensures hash matches exact stored JSON structure
 * - Prevents mismatches from Laravel/MySQL JSON transformations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {
            // Add hash algorithm field
            if (!Schema::hasColumn('investment_disclosure_snapshots', 'hash_algorithm')) {
                $table->string('hash_algorithm', 20)
                    ->default('sha256')
                    ->after('is_immutable')
                    ->comment('Hash algorithm used: sha256, sha512, etc.');
            }

            // Add snapshot hash field
            if (!Schema::hasColumn('investment_disclosure_snapshots', 'snapshot_hash')) {
                $table->string('snapshot_hash', 64)
                    ->nullable()
                    ->after('hash_algorithm')
                    ->comment('SHA-256 hash of disclosure_snapshot (computed from database value)');
            }

            // Add index for hash lookups (tamper detection queries)
            if (!Schema::hasColumn('investment_disclosure_snapshots', 'snapshot_hash')) {
                $table->index('snapshot_hash', 'idx_snapshot_hash');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_disclosure_snapshots', function (Blueprint $table) {
            // Drop index first
            if (Schema::hasColumn('investment_disclosure_snapshots', 'snapshot_hash')) {
                $table->dropIndex('idx_snapshot_hash');
            }

            // Collect columns to drop
            $columnsToDrop = [];

            if (Schema::hasColumn('investment_disclosure_snapshots', 'snapshot_hash')) {
                $columnsToDrop[] = 'snapshot_hash';
            }

            if (Schema::hasColumn('investment_disclosure_snapshots', 'hash_algorithm')) {
                $columnsToDrop[] = 'hash_algorithm';
            }

            // Drop columns if they exist
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
