<?php
// V-AUDIT-MODULE2-010 (Created) - Create kyc_verification_notes table
// Purpose: Track admin notes and comments during KYC verification process

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('kyc_verification_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_kyc_id')->constrained('user_kyc')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->text('note')->comment('Admin note or comment about the KYC submission');
            $table->timestamps();

            // Indexes for faster queries
            $table->index('user_kyc_id', 'idx_verification_notes_kyc_id');
            $table->index('admin_id', 'idx_verification_notes_admin_id');
            $table->index('created_at', 'idx_verification_notes_created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_verification_notes');
    }
};
