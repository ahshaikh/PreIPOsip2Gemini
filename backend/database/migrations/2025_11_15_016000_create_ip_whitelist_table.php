<?php
// V-FINAL-1730-539 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-SYS-108: IP Whitelisting
     */
    public function up(): void
    {
        Schema::create('ip_whitelist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique(); // e.g., "192.168.1.1" or "192.168.1.0/24"
            $table->string('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_whitelist');
    }
};