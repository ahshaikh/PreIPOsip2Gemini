<?php
// V-PHASE1-1730-002 | V-FINAL-1730-609 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('google_id')->nullable()->unique();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('mobile')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('password')->nullable();
            
            // 2FA Fields
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            $table->string('referral_code')->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('status')->default('pending'); // pending, active, suspended, blocked
            $table->string('avatar_url')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Dependent Tables
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('country')->default('India');
            $table->timestamps();
        });

        Schema::create('user_kyc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('pan_number')->nullable();
            $table->string('aadhaar_number')->nullable();
            $table->string('demat_account')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('status')->default('pending'); // pending, submitted, verified, rejected, processing
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_kyc_id')->constrained('user_kyc')->onDelete('cascade');
            $table->string('doc_type');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('processing_status')->nullable(); // api status
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
        });
        
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('password_hash');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // email, mobile, password_reset
            $table->string('otp_code');
            $table->integer('attempts')->default(0);
            $table->boolean('blocked')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
        Schema::dropIfExists('password_histories');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('user_kyc');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('users');
    }
};