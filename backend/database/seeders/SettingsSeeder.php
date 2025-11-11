<?php
// V-DEPLOY-1730-003
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This is the MOST IMPORTANT seeder. It populates the default
     * [cite_start]values for the "100% Configurable" engine. [cite: 438]
     */
    public function run(): void
    {
        $settings = [
            // System Toggles
            ['key' => 'registration_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'login_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'investment_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'withdrawal_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'referral_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'lucky_draw_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'profit_sharing_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'kyc_required_for_investment', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'support_tickets_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'system'],

            // Financial Settings
            ['key' => 'min_withdrawal_amount', 'value' => '1000', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'referral_bonus_amount', 'value' => '500', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'payment_grace_period_days', 'value' => '2', 'type' => 'number', 'group' => 'financial'],
            
            // Site Settings
            ['key' => 'site_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'site'],
            ['key' => 'records_per_page', 'value' => '25', 'type' => 'number', 'group' => 'site'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}