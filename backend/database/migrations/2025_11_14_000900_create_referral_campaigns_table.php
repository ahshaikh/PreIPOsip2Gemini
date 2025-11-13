<?php
// V-FINAL-1730-270

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Diwali Double Dhamaka"
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('multiplier', 5, 2)->default(1.0); // e.g., 2.0x
            $table->decimal('bonus_amount', 10, 2)->default(0); // Extra fixed bonus per referral
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_campaigns');
    }
};