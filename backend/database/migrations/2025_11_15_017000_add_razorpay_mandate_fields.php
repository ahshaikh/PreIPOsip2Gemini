<?php
// V-FINAL-1730-322 (Updated with JSON field) | V-FINAL-1730-349 (Original) | V-FINAL-1730-547 (FIXED)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // FSD-PAY-003: Auto-debit
        
        // --- THIS BLOCK WAS THE ERROR AND HAS BEEN REMOVED ---
        // Schema::create('user_profiles', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
        //     ... (all user_profile fields) ...
        // });
        // -----------------------------------------------------

        // This is the only logic that should be in this file
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('razorpay_mandate_id')->nullable()->after('plan_id');
            $table->string('razorpay_mandate_status')->nullable()->after('razorpay_mandate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the incorrect drop table logic
        // Schema::dropIfExists('user_profiles');
        
        // Only reverse the changes made in the up() method
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['razorpay_mandate_id', 'razorpay_mandate_status']);
        });
    }
};