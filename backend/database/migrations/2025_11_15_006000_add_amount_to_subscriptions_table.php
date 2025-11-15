<?php
// V-FINAL-1730-477 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-PLAN-005: Allow custom subscription amounts.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Stores the *actual* monthly amount for this SIP.
            // Defaults to 0, will be set from plan on creation.
            $table->decimal('amount', 10, 2)->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};