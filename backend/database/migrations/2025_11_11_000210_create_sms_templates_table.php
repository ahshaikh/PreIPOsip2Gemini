<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g., "otp"
            $table->string('name'); // e.g., "OTP Verification"
            $table->longText('content'); // <-- REQUIRED FIX
            $table->json('variables')->nullable(); // ["otp"]
            $table->boolean('is_active')->default(true); // <-- REQUIRED FIX
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
