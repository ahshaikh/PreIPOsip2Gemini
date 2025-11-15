<?php
// V-FINAL-1730-472 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-KYC-019: Predefined rejection reasons.
     */
    public function up(): void
    {
        Schema::create('kyc_rejection_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., "Blurry PAN Card"
            $table->text('reason');  // e.g., "The image of your PAN card was blurry..."
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_rejection_templates');
    }
};