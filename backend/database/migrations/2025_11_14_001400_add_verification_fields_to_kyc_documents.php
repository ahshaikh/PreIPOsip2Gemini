<?php
// V-FINAL-1730-328

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            // 'pending', 'approved', 'rejected'
            $table->string('status')->default('pending')->after('mime_type');
            
            // Who verified this specific document?
            $table->foreignId('verified_by')->nullable()->constrained('users')->after('status');
            
            // When?
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            
            // Why was it rejected?
            $table->text('verification_notes')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropColumn(['status', 'verified_by', 'verified_at', 'verification_notes']);
        });
    }
};