// V-PHASE1-1730-004
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_kyc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Raw data from user
            $table->string('pan_number')->nullable();
            $table->string('aadhaar_number')->nullable();
            $table->string('demat_account')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_ifsc')->nullable();

            $table->string('status')->default('pending'); // pending, submitted, verified, rejected
            $table->text('rejection_reason')->nullable();
            
            $table->foreignId('verified_by')->nullable()->constrained('users'); // Admin ID
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_kyc');
    }
};