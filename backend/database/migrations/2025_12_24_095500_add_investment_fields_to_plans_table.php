<?php

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
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('min_investment', 15, 2)->nullable()->after('max_pause_duration_months');
            $table->decimal('max_investment', 15, 2)->nullable()->after('min_investment');
            $table->string('billing_cycle')->nullable()->after('max_investment');
            $table->integer('trial_period_days')->default(0)->after('billing_cycle');
            $table->json('metadata')->nullable()->after('trial_period_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'min_investment',
                'max_investment',
                'billing_cycle',
                'trial_period_days',
                'metadata',
            ]);
        });
    }
};
