// V-PHASE1-1730-005
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_kyc_id')->constrained('user_kyc')->onDelete('cascade');
            $table->string('doc_type'); // e.g., 'aadhaar_front', 'pan', 'bank_proof'
            $table->string('file_path'); // Path in S3 or local storage
            $table->string('file_name');
            $table->string('mime_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};