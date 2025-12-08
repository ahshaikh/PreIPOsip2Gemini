<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add comprehensive bonus configuration settings
        $settings = [
            // Global On/Off Controls
            ['key' => 'progressive_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],
            ['key' => 'milestone_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],
            ['key' => 'consistency_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],
            ['key' => 'welcome_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],
            ['key' => 'referral_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],
            ['key' => 'celebration_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],
            ['key' => 'lucky_draw_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],
            ['key' => 'profit_share_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus_controls'],

            // Bonus Configuration
            ['key' => 'max_bonus_multiplier', 'value' => '10', 'type' => 'number', 'group' => 'bonus_config'],
            ['key' => 'bonus_rounding_decimals', 'value' => '2', 'type' => 'number', 'group' => 'bonus_config'],
            ['key' => 'bonus_rounding_mode', 'value' => 'round', 'type' => 'string', 'group' => 'bonus_config'], // round, floor, ceil

            // Referral Configuration
            ['key' => 'referral_bonus_amount', 'value' => '1000', 'type' => 'number', 'group' => 'referral_config'],
            ['key' => 'referral_completion_criteria', 'value' => 'first_payment', 'type' => 'string', 'group' => 'referral_config'], // first_payment, nth_payment, total_amount
            ['key' => 'referral_completion_threshold', 'value' => '1', 'type' => 'number', 'group' => 'referral_config'],

            // Bonus Allocation Source
            ['key' => 'bonus_allocation_source', 'value' => 'company_reserves', 'type' => 'string', 'group' => 'bonus_config'], // company_reserves, profit_pool, marketing_budget

            // Bonus Processing
            ['key' => 'bonus_processing_mode', 'value' => 'immediate', 'type' => 'string', 'group' => 'bonus_processing'], // immediate, daily, weekly, monthly
            ['key' => 'bonus_processing_time', 'value' => '00:00', 'type' => 'string', 'group' => 'bonus_processing'],

            // Formula Editor
            ['key' => 'custom_progressive_formula', 'value' => '', 'type' => 'string', 'group' => 'bonus_formulas'],
            ['key' => 'custom_milestone_formula', 'value' => '', 'type' => 'string', 'group' => 'bonus_formulas'],
            ['key' => 'custom_consistency_formula', 'value' => '', 'type' => 'string', 'group' => 'bonus_formulas'],
            ['key' => 'enable_custom_formulas', 'value' => 'false', 'type' => 'boolean', 'group' => 'bonus_formulas'],
        ];

        foreach ($settings as $setting) {
            // Only insert if key doesn't exist
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
        $keys = [
            'progressive_bonus_enabled',
            'milestone_bonus_enabled',
            'consistency_bonus_enabled',
            'welcome_bonus_enabled',
            'referral_bonus_enabled',
            'celebration_bonus_enabled',
            'lucky_draw_enabled',
            'profit_share_enabled',
            'bonus_rounding_decimals',
            'bonus_rounding_mode',
            'referral_completion_criteria',
            'referral_completion_threshold',
            'bonus_allocation_source',
            'bonus_processing_mode',
            'bonus_processing_time',
            'custom_progressive_formula',
            'custom_milestone_formula',
            'custom_consistency_formula',
            'enable_custom_formulas',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
