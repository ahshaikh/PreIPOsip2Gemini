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

	    // SMS Settings
	    ['key' => 'sms_provider', 'value' => 'msg91', 'type' => 'string', 'group' => 'notification'], // msg91, twilio, log
	    ['key' => 'msg91_auth_key', 'value' => '', 'type' => 'string', 'group' => 'notification'],
	    ['key' => 'msg91_sender_id', 'value' => 'PREIPO', 'type' => 'string', 'group' => 'notification'],
	    ['key' => 'msg91_dlt_te_id', 'value' => '', 'type' => 'string', 'group' => 'notification'], // For India DLT
	    ['key' => 'twilio_sid', 'value' => '', 'type' => 'string', 'group' => 'notification'],
	    ['key' => 'twilio_token', 'value' => '', 'type' => 'string', 'group' => 'notification'],
	    ['key' => 'twilio_from', 'value' => '', 'type' => 'string', 'group' => 'notification'],

            // Financial Settings
            ['key' => 'min_withdrawal_amount', 'value' => '1000', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'referral_bonus_amount', 'value' => '500', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'payment_grace_period_days', 'value' => '2', 'type' => 'number', 'group' => 'financial'],
            
            // Site Settings
            ['key' => 'site_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'site'],
            ['key' => 'records_per_page', 'value' => '25', 'type' => 'number', 'group' => 'site'],

	    // --- Cookie Consent
	    ['key' => 'cookie_consent_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'legal'],
	    ['key' => 'cookie_consent_message', 'value' => 'We use cookies to improve your experience. By using our site, you agree to our terms.', 'type' => 'string', 'group' => 'legal'],
	    
	    // --- NEW: Company Bank Details for Offline Payments ---
	    ['key' => 'bank_account_name', 'value' => 'PreIPO SIP Pvt Ltd', 'type' => 'string', 'group' => 'payment'],
	    ['key' => 'bank_account_number', 'value' => '123456789012', 'type' => 'string', 'group' => 'payment'],
	    ['key' => 'bank_ifsc', 'value' => 'HDFC0001234', 'type' => 'string', 'group' => 'payment'],
	    ['key' => 'bank_upi_id', 'value' => 'preiposip@hdfcbank', 'type' => 'string', 'group' => 'payment'],
	    ['key' => 'bank_qr_code', 'value' => '', 'type' => 'image', 'group' => 'payment'], // URL to QR image
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}