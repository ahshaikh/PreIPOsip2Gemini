<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ARCHITECTURAL FIX: Remove denormalized kyc_status column from users table.
 *
 * RATIONALE:
 * The system maintained KYC status in two locations:
 * - user_kyc.status (canonical, authoritative source)
 * - users.kyc_status (denormalized copy for "fast lookups")
 *
 * This dual-state design caused bugs when any code path bypassed KycStatusService,
 * resulting in inconsistent state between tables.
 *
 * SOLUTION:
 * - User model now has getKycStatusAttribute() accessor that reads from user_kyc relationship
 * - All existing $user->kyc_status calls continue to work transparently
 * - Single source of truth eliminates sync issues permanently
 *
 * ROLLBACK:
 * The down() method restores the column and re-syncs from user_kyc table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('users_kyc_status_index');
        });

        Schema::table('users', function (Blueprint $table) {
            // Drop the denormalized column
            $table->dropColumn('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore the denormalized column
            $table->string('kyc_status')->default('pending')->after('status');
            $table->index('kyc_status', 'users_kyc_status_index');
        });

        // Re-sync from canonical source (user_kyc table)
        DB::statement('
            UPDATE users u
            INNER JOIN user_kyc k ON u.id = k.user_id
            SET u.kyc_status = k.status
        ');
    }
};
