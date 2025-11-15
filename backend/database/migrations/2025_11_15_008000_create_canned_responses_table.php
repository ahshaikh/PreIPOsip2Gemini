<?php
// V-FINAL-1730-484 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-SUPPORT-007: Canned Responses
     */
    public function up(): void
    {
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., "KYC Rejected - Blurry"
            $table->text('body');    // e.g., "Hello {{user_name}}, your KYC..."
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canned_responses');
    }
};