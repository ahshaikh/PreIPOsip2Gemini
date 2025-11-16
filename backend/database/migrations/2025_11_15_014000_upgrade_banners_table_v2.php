<?php
// V-FINAL-1730-516 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-FRONT-021: Advanced Popup Manager
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Drop simple 'position' column if it exists
            if (Schema::hasColumn('banners', 'position')) {
                $table->dropColumn('position');
            }

            // --- FSD-FRONT-021 Fields ---
            
            // 1. Trigger
            $table->string('trigger_type')->default('load')->after('type'); // load, time_delay, scroll, exit_intent
            $table->integer('trigger_value')->default(0)->after('trigger_type'); // e.g., 5 (seconds) or 50 (percent)

            // 2. Frequency
            $table->string('frequency')->default('always')->after('trigger_value'); // always, once_per_session, once_daily, once
            
            // 3. Targeting (JSON for flexibility)
            // e.g., {"pages": ["/plans", "/about"], "user_type": "new"}
            $table->json('targeting_rules')->nullable()->after('frequency');

            // 4. Style (JSON for flexibility)
            // e.g., {"width": "500px", "overlay_color": "rgba(0,0,0,0.5)"}
            $table->json('style_config')->nullable()->after('targeting_rules');

            // 5. A/B Test (for V2)
            $table->unsignedInteger('variant_of')->nullable()->after('id'); // Parent Banner ID
            $table->integer('display_weight')->default(1)->after('style_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'trigger_type',
                'trigger_value',
                'frequency',
                'targeting_rules',
                'style_config',
                'variant_of',
                'display_weight'
            ]);
        });
    }
};