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
            // ============================================================
            // SYSTEM SETTINGS
            // ============================================================
            ['key' => 'registration_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'login_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'investment_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'withdrawal_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'support_tickets_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'maintenance_message', 'value' => 'System is down for maintenance. Please try again later.', 'type' => 'string', 'group' => 'system'],
            ['key' => 'referral_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'lucky_draw_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'profit_sharing_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'progressive_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'milestone_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'consistency_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'celebration_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'referral_kyc_required', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'kyc_required_for_investment', 'value' => 'true', 'type' => 'boolean', 'group' => 'system'],

            // ============================================================
            // SITE SETTINGS
            // ============================================================
            ['key' => 'site_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'site'],
            ['key' => 'site_title', 'value' => 'PreIPO SIP - Pre-IPO Investment Platform', 'type' => 'string', 'group' => 'site'],
            ['key' => 'site_description', 'value' => 'Invest in pre-IPO companies with systematic investment plans', 'type' => 'text', 'group' => 'site'],
            ['key' => 'site_keywords', 'value' => 'pre-ipo, investment, sip, startup investment', 'type' => 'text', 'group' => 'site'],
            ['key' => 'contact_email', 'value' => 'support@preipo-sip.com', 'type' => 'string', 'group' => 'site'],
            ['key' => 'contact_phone', 'value' => '+91 1234567890', 'type' => 'string', 'group' => 'site'],
            ['key' => 'contact_address', 'value' => 'Mumbai, Maharashtra, India', 'type' => 'text', 'group' => 'site'],
            ['key' => 'timezone', 'value' => 'Asia/Kolkata', 'type' => 'string', 'group' => 'site'],
            ['key' => 'records_per_page', 'value' => '25', 'type' => 'number', 'group' => 'site'],
            ['key' => 'logo_url', 'value' => '/images/logo.png', 'type' => 'string', 'group' => 'site'],
            ['key' => 'favicon_url', 'value' => '/images/favicon.ico', 'type' => 'string', 'group' => 'site'],
            ['key' => 'social_facebook', 'value' => '', 'type' => 'string', 'group' => 'site'],
            ['key' => 'social_twitter', 'value' => '', 'type' => 'string', 'group' => 'site'],
            ['key' => 'social_linkedin', 'value' => '', 'type' => 'string', 'group' => 'site'],
            ['key' => 'social_instagram', 'value' => '', 'type' => 'string', 'group' => 'site'],

            // ============================================================
            // OPERATIONAL SETTINGS
            // ============================================================
            ['key' => 'api_timeout', 'value' => '30', 'type' => 'number', 'group' => 'operational'],
            ['key' => 'database_timeout', 'value' => '60', 'type' => 'number', 'group' => 'operational'],
            ['key' => 'max_upload_size', 'value' => '10240', 'type' => 'number', 'group' => 'operational'], // KB
            ['key' => 'allowed_file_types', 'value' => 'jpg,jpeg,png,pdf,doc,docx', 'type' => 'string', 'group' => 'operational'],
            ['key' => 'session_timeout', 'value' => '120', 'type' => 'number', 'group' => 'operational'], // minutes
            ['key' => 'request_timeout', 'value' => '30', 'type' => 'number', 'group' => 'operational'], // seconds

            // ============================================================
            // SECURITY SETTINGS
            // ============================================================
            // Password Policy
            ['key' => 'password_min_length', 'value' => '8', 'type' => 'number', 'group' => 'security'],
            ['key' => 'password_require_uppercase', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'password_require_lowercase', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'password_require_numbers', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'password_require_special', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'password_expiry_days', 'value' => '90', 'type' => 'number', 'group' => 'security'],
            ['key' => 'password_history_limit', 'value' => '5', 'type' => 'number', 'group' => 'security'],

            // Login Security
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'number', 'group' => 'security'],
            ['key' => 'lockout_duration', 'value' => '30', 'type' => 'number', 'group' => 'security'], // minutes
            ['key' => 'session_regenerate_on_login', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],

            // IP & Access Control
            ['key' => 'admin_ip_whitelist', 'value' => '', 'type' => 'text', 'group' => 'security'],
            ['key' => 'allowed_ips', 'value' => '', 'type' => 'text', 'group' => 'security'],
            ['key' => 'ip_whitelist_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'security'],

            // Fraud Detection
            ['key' => 'fraud_amount_threshold', 'value' => '50000', 'type' => 'number', 'group' => 'security'],
            ['key' => 'fraud_new_user_days', 'value' => '7', 'type' => 'number', 'group' => 'security'],
            ['key' => 'fraud_detection_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],

            // SSL/HTTPS
            ['key' => 'force_https', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'hsts_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'hsts_max_age', 'value' => '31536000', 'type' => 'number', 'group' => 'security'],

            // ============================================================
            // TWO-FACTOR AUTHENTICATION
            // ============================================================
            ['key' => '2fa_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => '2fa'],
            ['key' => '2fa_enforce_for_admins', 'value' => 'true', 'type' => 'boolean', 'group' => '2fa'],
            ['key' => '2fa_enforce_for_users', 'value' => 'false', 'type' => 'boolean', 'group' => '2fa'],
            ['key' => '2fa_methods', 'value' => 'totp,sms,email', 'type' => 'string', 'group' => '2fa'],
            ['key' => '2fa_backup_codes_count', 'value' => '10', 'type' => 'number', 'group' => '2fa'],
            ['key' => '2fa_grace_period_days', 'value' => '7', 'type' => 'number', 'group' => '2fa'],

            // ============================================================
            // CAPTCHA CONFIGURATION
            // ============================================================
            ['key' => 'captcha_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'captcha'],
            ['key' => 'captcha_provider', 'value' => 'recaptcha_v2', 'type' => 'string', 'group' => 'captcha'],
            ['key' => 'captcha_site_key', 'value' => '', 'type' => 'string', 'group' => 'captcha'],
            ['key' => 'captcha_secret_key', 'value' => '', 'type' => 'string', 'group' => 'captcha'],
            ['key' => 'captcha_threshold', 'value' => '0.5', 'type' => 'number', 'group' => 'captcha'],
            ['key' => 'captcha_on_login', 'value' => 'true', 'type' => 'boolean', 'group' => 'captcha'],
            ['key' => 'captcha_on_register', 'value' => 'true', 'type' => 'boolean', 'group' => 'captcha'],
            ['key' => 'captcha_on_forgot_password', 'value' => 'true', 'type' => 'boolean', 'group' => 'captcha'],

            // ============================================================
            // RATE LIMITING
            // ============================================================
            ['key' => 'rate_limit_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'rate_limiting'],
            ['key' => 'rate_limit_api', 'value' => '60', 'type' => 'number', 'group' => 'rate_limiting'], // per minute
            ['key' => 'rate_limit_login', 'value' => '5', 'type' => 'number', 'group' => 'rate_limiting'], // per minute
            ['key' => 'rate_limit_register', 'value' => '3', 'type' => 'number', 'group' => 'rate_limiting'], // per hour
            ['key' => 'rate_limit_payment', 'value' => '10', 'type' => 'number', 'group' => 'rate_limiting'], // per hour

            // ============================================================
            // EMAIL SETTINGS
            // ============================================================
            ['key' => 'email_queue_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'email'],
            ['key' => 'email_throttle_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'email'],
            ['key' => 'email_throttle_limit', 'value' => '100', 'type' => 'number', 'group' => 'email'], // per hour
            ['key' => 'email_from_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_from_address', 'value' => 'noreply@preipo-sip.com', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_blacklist', 'value' => '', 'type' => 'text', 'group' => 'email'],
            ['key' => 'email_domain_blacklist', 'value' => 'tempmail.com,throwaway.email', 'type' => 'text', 'group' => 'email'],

            // ============================================================
            // NOTIFICATION SETTINGS
            // ============================================================
            ['key' => 'notification_channels', 'value' => 'email,sms,push,database', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_priority_order', 'value' => 'push,sms,email,database', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_fallback_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'sms_provider', 'value' => 'log', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'msg91_auth_key', 'value' => '', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'msg91_sender_id', 'value' => 'PREIPO', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'msg91_dlt_te_id', 'value' => '', 'type' => 'string', 'group' => 'notification'],

            // ============================================================
            // PAYMENT SETTINGS
            // ============================================================
            ['key' => 'payment_security_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'payment_velocity_check', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'payment_max_per_day', 'value' => '5', 'type' => 'number', 'group' => 'payment'],
            ['key' => 'payment_max_amount_per_day', 'value' => '100000', 'type' => 'number', 'group' => 'payment'],
            ['key' => 'payment_geo_blocking_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'payment_allowed_countries', 'value' => 'IN', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'payment_webhook_retry_attempts', 'value' => '3', 'type' => 'number', 'group' => 'payment'],
            ['key' => 'payment_webhook_retry_delay', 'value' => '60', 'type' => 'number', 'group' => 'payment'], // seconds

            // ============================================================
            // WEBHOOK CONFIGURATION
            // ============================================================
            ['key' => 'webhook_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'webhook'],
            ['key' => 'webhook_url', 'value' => '', 'type' => 'string', 'group' => 'webhook'],
            ['key' => 'webhook_secret', 'value' => '', 'type' => 'string', 'group' => 'webhook'],
            ['key' => 'webhook_events', 'value' => 'user.created,payment.success,withdrawal.approved', 'type' => 'text', 'group' => 'webhook'],
            ['key' => 'webhook_retry_attempts', 'value' => '3', 'type' => 'number', 'group' => 'webhook'],
            ['key' => 'webhook_timeout', 'value' => '30', 'type' => 'number', 'group' => 'webhook'],

            // ============================================================
            // API CONFIGURATION
            // ============================================================
            ['key' => 'api_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'api'],
            ['key' => 'api_rate_limit', 'value' => '60', 'type' => 'number', 'group' => 'api'],
            ['key' => 'api_version', 'value' => 'v1', 'type' => 'string', 'group' => 'api'],
            ['key' => 'api_key_required', 'value' => 'false', 'type' => 'boolean', 'group' => 'api'],
            ['key' => 'api_ip_whitelist', 'value' => '', 'type' => 'text', 'group' => 'api'],

            // ============================================================
            // BACKUP SETTINGS
            // ============================================================
            ['key' => 'backup_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'backup'],
            ['key' => 'backup_schedule', 'value' => 'daily', 'type' => 'string', 'group' => 'backup'],
            ['key' => 'backup_retention_days', 'value' => '30', 'type' => 'number', 'group' => 'backup'],
            ['key' => 'backup_storage', 'value' => 'local', 'type' => 'string', 'group' => 'backup'],
            ['key' => 'backup_notification_email', 'value' => '', 'type' => 'string', 'group' => 'backup'],
            ['key' => 'backup_include_uploads', 'value' => 'true', 'type' => 'boolean', 'group' => 'backup'],

            // ============================================================
            // CACHE SETTINGS
            // ============================================================
            ['key' => 'cache_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'cache'],
            ['key' => 'cache_driver', 'value' => 'redis', 'type' => 'string', 'group' => 'cache'],
            ['key' => 'cache_ttl', 'value' => '3600', 'type' => 'number', 'group' => 'cache'], // seconds
            ['key' => 'cache_prefix', 'value' => 'preipo_', 'type' => 'string', 'group' => 'cache'],

            // ============================================================
            // LOG SETTINGS
            // ============================================================
            ['key' => 'log_level', 'value' => 'info', 'type' => 'string', 'group' => 'log'],
            ['key' => 'log_channel', 'value' => 'stack', 'type' => 'string', 'group' => 'log'],
            ['key' => 'log_retention_days', 'value' => '30', 'type' => 'number', 'group' => 'log'],
            ['key' => 'log_rotation_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'log'],
            ['key' => 'log_max_files', 'value' => '7', 'type' => 'number', 'group' => 'log'],

            // ============================================================
            // CRON JOBS
            // ============================================================
            ['key' => 'cron_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'cron'],
            ['key' => 'cron_notification_email', 'value' => '', 'type' => 'string', 'group' => 'cron'],
            ['key' => 'cron_log_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'cron'],

            // ============================================================
            // FINANCIAL SETTINGS
            // ============================================================
            ['key' => 'min_withdrawal_amount', 'value' => '1000', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'auto_approval_max_amount', 'value' => '5000', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'payment_grace_period_days', 'value' => '2', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'referral_bonus_amount', 'value' => '500', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'tds_rate', 'value' => '0.10', 'type' => 'number', 'group' => 'financial'],
            ['key' => 'tds_threshold', 'value' => '5000', 'type' => 'number', 'group' => 'financial'],

            // ============================================================
            // LEGAL SETTINGS
            // ============================================================
            ['key' => 'cookie_consent_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'legal'],
            ['key' => 'cookie_consent_message', 'value' => 'We use cookies to improve your experience.', 'type' => 'string', 'group' => 'legal'],
            ['key' => 'gdpr_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'legal'],
            ['key' => 'data_retention_days', 'value' => '365', 'type' => 'number', 'group' => 'legal'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}