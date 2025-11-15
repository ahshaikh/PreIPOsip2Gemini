<?php
// V-DEPLOY-1730-003 (Created) | V-FINAL-1730-445 (IP Whitelist Added) | V-FINAL-1730-492 (Types Added)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // System
            ['key' => 'registration_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'login_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'investment_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'withdrawal_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'support_tickets_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'maintenance_message', 'value' => 'System is down for maintenance. Please try again later.', 'type' => 'string', 'group' => 'system'],
            
            // Financial
            ['key' => 'min_withdrawal_amount', 'value' => '1000', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'auto_approval_max_amount', 'value' => '5000', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'payment_grace_period_days', 'value' => '2', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'referral_bonus_amount', 'value' => '500', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'tds_rate', 'value' => '0.10', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'tds_threshold', 'value' => '5000', 'type' => 'number', 'group' => 'financial'],

            // Bonus Toggles
            ['key' => 'referral_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'lucky_draw_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'profit_sharing_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'progressive_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'milestone_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'consistency_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'celebration_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'referral_kyc_required', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'kyc_required_for_investment', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],

            // Security
            ['key' => 'password_history_limit', 'value' => '5Z', 'type' => 'number', 'group' => 'security'],
            ['key' => 'fraud_amount_threshold', 'value' => '50000', 'type' => 'number', 'group' => 'security'],
            ['key' => 'fraud_new_user_days', 'value' => '7', 'type' => 'number', 'group' => 'security'],
            ['key' => 'admin_ip_whitelist', 'value' => '', 'type' => 'text', 'group' => 'security'],
            ['key' => 'allowed_ips', 'value' => '', 'type' => 'text', 'group' => 'security'],

            // Site
            ['key' => 'site_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'site'],
            ['key' => 'records_per_page', 'value' => '25', 'type' => 'number', 'group' => 'site'],
            
            // Legal
            ['key' => 'cookie_consent_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'legal'],
            ['key' => 'cookie_consent_message', 'value' => 'We use cookies to improve your experience.', 'type' => 'string', 'group' => 'legal'],
            
            // Notifications (MSG91)
            ['key' => 'sms_provider', 'value' => 'log', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'msg91_auth_key', 'value' => '', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'msg91_sender_id', 'value' => 'PREIPO', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'msg91_dlt_te_id', 'value' => '', 'type' => 'string', 'group' => 'notification'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}