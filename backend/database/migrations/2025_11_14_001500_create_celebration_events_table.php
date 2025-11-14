<?php
// V-FINAL-1730-346 (Corrected)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; // <-- This was the line with the typo

return new class extends Migration
{
    /**
     * This table stores global, non-user-specific events like
     * Diwali, New Year, etc., as per FSD-BONUS-012.
     */
    public function up(): void
    {
        Schema::create('celebration_events', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Diwali Bonus"
            $table->date('event_date');
            $table->json('bonus_amount_by_plan'); // e.g., {"plan_a": 50, "plan_b": 100}
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring_annually')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('celebration_events');
    }
};