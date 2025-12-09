<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new fields to lucky_draws table
        Schema::table('lucky_draws', function (Blueprint $table) {
            $table->string('frequency')->default('monthly')->after('prize_structure'); // monthly, quarterly, custom
            $table->json('entry_rules')->nullable()->after('frequency'); // Per-plan entry rules
            $table->string('result_visibility')->default('public')->after('status'); // public, private, winners_only
            $table->string('certificate_template')->nullable()->after('result_visibility');
            $table->string('draw_video_url')->nullable()->after('certificate_template');
            $table->json('draw_metadata')->nullable()->after('draw_video_url'); // Store extra info
            $table->foreignId('created_by')->nullable()->constrained('users')->after('draw_metadata');
            $table->foreignId('executed_by')->nullable()->constrained('users')->after('created_by');
        });

        // Add settings for Lucky Draw configuration
        $settings = [
            // Draw Frequency
            ['key' => 'lucky_draw_frequency', 'value' => 'monthly', 'type' => 'string', 'group' => 'lucky_draw_config'],
            ['key' => 'lucky_draw_custom_interval_days', 'value' => '30', 'type' => 'number', 'group' => 'lucky_draw_config'],

            // Entry Configuration
            ['key' => 'lucky_draw_ontime_bonus', 'value' => '1', 'type' => 'number', 'group' => 'lucky_draw_config'],
            ['key' => 'lucky_draw_streak_bonus', 'value' => '5', 'type' => 'number', 'group' => 'lucky_draw_config'],
            ['key' => 'lucky_draw_streak_months', 'value' => '6', 'type' => 'number', 'group' => 'lucky_draw_config'],

            // Prize Pool
            ['key' => 'lucky_draw_prize_pool', 'value' => '152500', 'type' => 'number', 'group' => 'lucky_draw_config'],

            // Result Publishing
            ['key' => 'lucky_draw_auto_publish', 'value' => 'true', 'type' => 'boolean', 'group' => 'lucky_draw_config'],
            ['key' => 'lucky_draw_publish_full_details', 'value' => 'false', 'type' => 'boolean', 'group' => 'lucky_draw_config'],

            // Certificates
            ['key' => 'lucky_draw_enable_certificates', 'value' => 'true', 'type' => 'boolean', 'group' => 'lucky_draw_config'],
            ['key' => 'lucky_draw_certificate_footer', 'value' => 'Congratulations on your win!', 'type' => 'string', 'group' => 'lucky_draw_config'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lucky_draws', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['executed_by']);
            $table->dropColumn([
                'frequency',
                'entry_rules',
                'result_visibility',
                'certificate_template',
                'draw_video_url',
                'draw_metadata',
                'created_by',
                'executed_by',
            ]);
        });

        $keys = [
            'lucky_draw_frequency',
            'lucky_draw_custom_interval_days',
            'lucky_draw_ontime_bonus',
            'lucky_draw_streak_bonus',
            'lucky_draw_streak_months',
            'lucky_draw_prize_pool',
            'lucky_draw_auto_publish',
            'lucky_draw_publish_full_details',
            'lucky_draw_enable_certificates',
            'lucky_draw_certificate_footer',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
