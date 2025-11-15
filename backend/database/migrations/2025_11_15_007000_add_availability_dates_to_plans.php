<?php
// V-FINAL-1730-481 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-PLAN-010: Plan Scheduling
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->timestamp('available_from')->nullable()->after('display_order');
            $table->timestamp('available_until')->nullable()->after('available_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['available_from', 'available_until']);
        });
    }
};