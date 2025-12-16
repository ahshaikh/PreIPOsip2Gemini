<?php
// V-AUDIT-MODULE2-003 (Created) - Add granular verification columns to user_kyc table
// Purpose: Track individual component verification (Aadhaar, PAN, Bank) separately
// This fixes the critical bug where DigiLocker verification bypassed PAN/Bank checks

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add granular verification tracking columns to user_kyc table.
     * This allows the system to track which specific components have been verified.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            // --- Granular Verification Flags ---
            // These track individual component verification status

            // Aadhaar/Identity Verification
            $table->boolean('is_aadhaar_verified')->default(false)
                ->after('status')
                ->comment('TRUE if Aadhaar/identity has been verified (via DigiLocker or manual)');

            $table->timestamp('aadhaar_verified_at')->nullable()
                ->after('is_aadhaar_verified')
                ->comment('Timestamp when Aadhaar was verified');

            $table->string('aadhaar_verification_source')->nullable()
                ->after('aadhaar_verified_at')
                ->comment('Source of verification: digilocker, manual, api');

            // PAN Verification
            $table->boolean('is_pan_verified')->default(false)
                ->after('aadhaar_verification_source')
                ->comment('TRUE if PAN has been verified via API or manual check');

            $table->timestamp('pan_verified_at')->nullable()
                ->after('is_pan_verified')
                ->comment('Timestamp when PAN was verified');

            $table->string('pan_verification_source')->nullable()
                ->after('pan_verified_at')
                ->comment('Source of verification: api, manual');

            // Bank Account Verification
            $table->boolean('is_bank_verified')->default(false)
                ->after('pan_verification_source')
                ->comment('TRUE if bank account has been verified');

            $table->timestamp('bank_verified_at')->nullable()
                ->after('is_bank_verified')
                ->comment('Timestamp when bank account was verified');

            $table->string('bank_verification_source')->nullable()
                ->after('bank_verified_at')
                ->comment('Source of verification: api, manual, penny_drop');

            // Demat Account Verification (Optional)
            $table->boolean('is_demat_verified')->default(false)
                ->after('bank_verification_source')
                ->comment('TRUE if demat account has been verified (optional)');

            $table->timestamp('demat_verified_at')->nullable()
                ->after('is_demat_verified')
                ->comment('Timestamp when demat was verified');

            // --- Additional Fields for Enhanced Tracking ---

            // Track resubmission requests
            $table->text('resubmission_instructions')->nullable()
                ->after('rejection_reason')
                ->comment('Instructions for user when resubmission is required');

            // Track verification checklist (JSON)
            $table->json('verification_checklist')->nullable()
                ->after('verified_by')
                ->comment('Admin verification checklist (JSON format)');

            // Add index on status for faster queries
            $table->index('status', 'idx_user_kyc_status');

            // Add index on verification flags for analytics queries
            $table->index(['is_aadhaar_verified', 'is_pan_verified', 'is_bank_verified'], 'idx_user_kyc_verification_flags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_user_kyc_status');
            $table->dropIndex('idx_user_kyc_verification_flags');

            // Drop columns in reverse order
            $table->dropColumn([
                'verification_checklist',
                'resubmission_instructions',
                'demat_verified_at',
                'is_demat_verified',
                'bank_verification_source',
                'bank_verified_at',
                'is_bank_verified',
                'pan_verification_source',
                'pan_verified_at',
                'is_pan_verified',
                'aadhaar_verification_source',
                'aadhaar_verified_at',
                'is_aadhaar_verified',
            ]);
        });
    }
};
