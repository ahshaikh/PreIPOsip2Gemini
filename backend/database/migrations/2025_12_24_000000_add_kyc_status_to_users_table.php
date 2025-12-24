<?php
// V-FINAL-1730-629 (Manual KYC - Add kyc_status to users for fast lookups)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This adds kyc_status to users table for:
     * 1. Fast auth gating without joins
     * 2. Quick filtering of verified/unverified users
     * 3. Performance optimization in user listings
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add kyc_status column if it doesn't exist
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status')->default('pending')->after('status');
                $table->index('kyc_status', 'users_kyc_status_index');
            }
        });

        // Sync existing KYC statuses to users table
        DB::statement('
            UPDATE users u
            INNER JOIN user_kyc k ON u.id = k.user_id
            SET u.kyc_status = k.status
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'kyc_status')) {
                $table->dropIndex('users_kyc_status_index');
                $table->dropColumn('kyc_status');
            }
        });
    }
};
