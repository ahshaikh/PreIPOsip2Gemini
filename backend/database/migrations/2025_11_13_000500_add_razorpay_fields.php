<?php
// V-REMEDIATE-1730-187

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('razorpay_plan_id')->nullable()->after('slug');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_auto_debit')->default(false)->after('status');
            $table->string('razorpay_subscription_id')->nullable()->after('subscription_code');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('razorpay_plan_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_auto_debit', 'razorpay_subscription_id']);
        });
    }
};