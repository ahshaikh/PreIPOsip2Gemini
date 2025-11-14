<?php
// V-DEPLOY-1730-003 (Created) | V-FINAL-1730-445 (IP Whitelist Added)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
            ['key' => 'password_history_limit', 'value' => '5', 'type' => 'number', 'group' => 'security'],

            // Site Settings
            ['key' => 'site_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'site'],
            ['key' => 'records_per_page', 'value' => '25', 'type' => 'number', 'group' => 'site'],

            // --- NEW: Security Settings ---
            [
                'key' => 'admin_ip_whitelist', 
                'value' => '', // Default empty (disabled)
                'type' => 'text', 
                'group' => 'security'
            ],
            [
                'key' => 'allowed_ips', // For maintenance mode
                'value' => '', 
                'type' => 'text', 
                'group' => 'security'
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'System is down for maintenance. Please try again later.',
                'type' => 'string',
                'group' => 'system'
            ],
            [
                'key' => 'auto_approval_max_amount',
                'value' => '5000',
                'type' => 'number',
                'group' => 'financial'
            ],
            [
                'key' => 'fraud_amount_threshold',
                'value' => '50000',
                'type' => 'number',
                'group' => 'security'
            ],
            [
                'key' => 'fraud_new_user_days',
                'value' => '7',
                'type' => 'number',
                'group' => 'security'
            ],
            [
                'key' => 'cookie_consent_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'legal'
            ],
            [
                'key' => 'cookie_consent_message',
                'value' => 'We use cookies to improve your experience. By using our site, you agree to our terms.',
                'type' => 'string',
                'group' => 'legal'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}