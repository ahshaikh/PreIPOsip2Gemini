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
            // SMTP Configuration
            ['key' => 'email_provider', 'value' => 'smtp', 'type' => 'string', 'group' => 'email'], // smtp, sendgrid, mailgun, ses
            ['key' => 'email_driver', 'value' => 'smtp', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_host', 'value' => 'smtp.mailtrap.io', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_port', 'value' => '2525', 'type' => 'number', 'group' => 'email'],
            ['key' => 'email_encryption', 'value' => 'tls', 'type' => 'string', 'group' => 'email'], // tls, ssl, none
            ['key' => 'email_username', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_password', 'value' => '', 'type' => 'string', 'group' => 'email'],

            // Email Configuration
            ['key' => 'email_from_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_from_address', 'value' => 'noreply@preipo-sip.com', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_reply_to', 'value' => 'support@preipo-sip.com', 'type' => 'string', 'group' => 'email'],

            // Email Queue & Throttling
            ['key' => 'email_queue_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'email'],
            ['key' => 'email_queue_name', 'value' => 'emails', 'type' => 'string', 'group' => 'email'],
            ['key' => 'email_throttle_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'email'],
            ['key' => 'email_throttle_limit', 'value' => '100', 'type' => 'number', 'group' => 'email'], // per hour
            ['key' => 'email_retry_attempts', 'value' => '3', 'type' => 'number', 'group' => 'email'],
            ['key' => 'email_retry_delay', 'value' => '60', 'type' => 'number', 'group' => 'email'], // seconds

            // Email Tracking
            ['key' => 'email_tracking_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'email'],
            ['key' => 'email_track_opens', 'value' => 'true', 'type' => 'boolean', 'group' => 'email'],
            ['key' => 'email_track_clicks', 'value' => 'true', 'type' => 'boolean', 'group' => 'email'],

            // Email Blacklist
            ['key' => 'email_blacklist', 'value' => '', 'type' => 'text', 'group' => 'email'],
            ['key' => 'email_domain_blacklist', 'value' => 'tempmail.com,throwaway.email,guerrillamail.com', 'type' => 'text', 'group' => 'email'],

            // SendGrid (if using)
            ['key' => 'sendgrid_api_key', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'sendgrid_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'email'],

            // ============================================================
            // SMS SETTINGS
            // ============================================================
            ['key' => 'sms_provider', 'value' => 'log', 'type' => 'string', 'group' => 'sms'], // log, msg91, twilio, nexmo
            ['key' => 'sms_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'sms'],

            // MSG91 Configuration
            ['key' => 'msg91_auth_key', 'value' => '', 'type' => 'string', 'group' => 'sms'],
            ['key' => 'msg91_sender_id', 'value' => 'PREIPO', 'type' => 'string', 'group' => 'sms'],
            ['key' => 'msg91_dlt_te_id', 'value' => '', 'type' => 'string', 'group' => 'sms'],
            ['key' => 'msg91_route', 'value' => '4', 'type' => 'string', 'group' => 'sms'], // 4 = promotional, 1 = transactional

            // Twilio Configuration
            ['key' => 'twilio_sid', 'value' => '', 'type' => 'string', 'group' => 'sms'],
            ['key' => 'twilio_auth_token', 'value' => '', 'type' => 'string', 'group' => 'sms'],
            ['key' => 'twilio_from_number', 'value' => '', 'type' => 'string', 'group' => 'sms'],

            // SMS Settings
            ['key' => 'sms_queue_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'sms'],
            ['key' => 'sms_queue_name', 'value' => 'sms', 'type' => 'string', 'group' => 'sms'],
            ['key' => 'sms_max_length', 'value' => '160', 'type' => 'number', 'group' => 'sms'],
            ['key' => 'sms_throttle_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'sms'],
            ['key' => 'sms_throttle_limit', 'value' => '100', 'type' => 'number', 'group' => 'sms'], // per hour
            ['key' => 'sms_retry_attempts', 'value' => '3', 'type' => 'number', 'group' => 'sms'],
            ['key' => 'sms_retry_delay', 'value' => '30', 'type' => 'number', 'group' => 'sms'], // seconds
            ['key' => 'sms_country_code', 'value' => '+91', 'type' => 'string', 'group' => 'sms'],

            // ============================================================
            // PUSH NOTIFICATION SETTINGS
            // ============================================================
            ['key' => 'push_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'push'],
            ['key' => 'push_provider', 'value' => 'fcm', 'type' => 'string', 'group' => 'push'], // fcm, onesignal, sns

            // Firebase Cloud Messaging (FCM)
            ['key' => 'fcm_server_key', 'value' => '', 'type' => 'text', 'group' => 'push'],
            ['key' => 'fcm_sender_id', 'value' => '', 'type' => 'string', 'group' => 'push'],
            ['key' => 'fcm_project_id', 'value' => '', 'type' => 'string', 'group' => 'push'],
            ['key' => 'fcm_credentials_path', 'value' => '', 'type' => 'string', 'group' => 'push'],

            // OneSignal
            ['key' => 'onesignal_app_id', 'value' => '', 'type' => 'string', 'group' => 'push'],
            ['key' => 'onesignal_api_key', 'value' => '', 'type' => 'string', 'group' => 'push'],
            ['key' => 'onesignal_rest_api_key', 'value' => '', 'type' => 'string', 'group' => 'push'],

            // Push Settings
            ['key' => 'push_queue_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'push'],
            ['key' => 'push_queue_name', 'value' => 'push', 'type' => 'string', 'group' => 'push'],
            ['key' => 'push_icon_url', 'value' => '', 'type' => 'string', 'group' => 'push'],
            ['key' => 'push_badge_url', 'value' => '', 'type' => 'string', 'group' => 'push'],
            ['key' => 'push_sound', 'value' => 'default', 'type' => 'string', 'group' => 'push'],
            ['key' => 'push_ttl', 'value' => '86400', 'type' => 'number', 'group' => 'push'], // seconds (1 day)
            ['key' => 'push_priority', 'value' => 'high', 'type' => 'string', 'group' => 'push'], // high, normal
            ['key' => 'push_collapse_key', 'value' => '', 'type' => 'string', 'group' => 'push'],

            // ============================================================
            // NOTIFICATION SYSTEM SETTINGS
            // ============================================================
            // Channels
            ['key' => 'notification_channels', 'value' => 'email,sms,push,database', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_default_channel', 'value' => 'email', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_channels_enabled', 'value' => 'email,database', 'type' => 'string', 'group' => 'notification'],

            // Priority & Routing
            ['key' => 'notification_priority_order', 'value' => 'push,sms,email,database', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_fallback_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_fallback_delay', 'value' => '300', 'type' => 'number', 'group' => 'notification'], // seconds

            // Critical Notifications
            ['key' => 'notification_critical_override', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_critical_channels', 'value' => 'email,sms,push', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_critical_bypass_preferences', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],

            // Batching
            ['key' => 'notification_batching_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_batch_size', 'value' => '100', 'type' => 'number', 'group' => 'notification'],
            ['key' => 'notification_batch_delay', 'value' => '60', 'type' => 'number', 'group' => 'notification'], // seconds
            ['key' => 'notification_batch_per_user_limit', 'value' => '10', 'type' => 'number', 'group' => 'notification'],

            // Queue Configuration
            ['key' => 'notification_queue_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_queue_default', 'value' => 'notifications', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_queue_high_priority', 'value' => 'high_priority', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_queue_low_priority', 'value' => 'low_priority', 'type' => 'string', 'group' => 'notification'],

            // Logging & Tracking
            ['key' => 'notification_logging_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_log_retention_days', 'value' => '90', 'type' => 'number', 'group' => 'notification'],
            ['key' => 'notification_track_delivery', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_track_opens', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_track_clicks', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],

            // Rate Limiting
            ['key' => 'notification_rate_limit_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_rate_limit_per_user_hour', 'value' => '10', 'type' => 'number', 'group' => 'notification'],
            ['key' => 'notification_rate_limit_per_user_day', 'value' => '50', 'type' => 'number', 'group' => 'notification'],

            // Templates
            ['key' => 'notification_template_caching', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_template_cache_ttl', 'value' => '3600', 'type' => 'number', 'group' => 'notification'], // seconds

            // Testing
            ['key' => 'notification_test_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'notification'],
            ['key' => 'notification_test_email', 'value' => 'test@preipo-sip.com', 'type' => 'string', 'group' => 'notification'],
            ['key' => 'notification_test_mobile', 'value' => '', 'type' => 'string', 'group' => 'notification'],

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
            // LEGAL & COMPLIANCE SETTINGS
            // ============================================================

            // Cookie Consent Banner Configuration
            ['key' => 'cookie_consent_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'cookie_consent_version', 'value' => '1.0', 'type' => 'string', 'group' => 'compliance'],
            ['key' => 'cookie_consent_title', 'value' => 'We use cookies', 'type' => 'string', 'group' => 'compliance'],
            ['key' => 'cookie_consent_message', 'value' => 'We use cookies to improve your experience on our site and to show you personalized content. By clicking "Accept All", you consent to our use of cookies.', 'type' => 'text', 'group' => 'compliance'],
            ['key' => 'cookie_consent_position', 'value' => 'bottom', 'type' => 'string', 'group' => 'compliance'], // bottom, top, bottom-left, bottom-right
            ['key' => 'cookie_consent_theme', 'value' => 'light', 'type' => 'string', 'group' => 'compliance'], // light, dark
            ['key' => 'cookie_consent_show_reject', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'cookie_consent_show_preferences', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'cookie_consent_auto_hide_delay', 'value' => '0', 'type' => 'number', 'group' => 'compliance'], // seconds, 0 = never
            ['key' => 'cookie_consent_revisit_consent', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'cookie_consent_expiry_days', 'value' => '365', 'type' => 'number', 'group' => 'compliance'],

            // Cookie Categories
            ['key' => 'cookies_essential_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'cookies_analytics_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'cookies_marketing_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'cookies_preferences_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],

            // GDPR Compliance
            ['key' => 'gdpr_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'gdpr_region', 'value' => 'EU', 'type' => 'string', 'group' => 'compliance'], // EU, UK, Worldwide
            ['key' => 'gdpr_data_export_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'gdpr_data_deletion_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'gdpr_data_portability_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'gdpr_data_rectification_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'gdpr_right_to_object_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'gdpr_dpo_name', 'value' => '', 'type' => 'string', 'group' => 'compliance'], // Data Protection Officer
            ['key' => 'gdpr_dpo_email', 'value' => 'dpo@preipo-sip.com', 'type' => 'string', 'group' => 'compliance'],
            ['key' => 'gdpr_dpo_phone', 'value' => '', 'type' => 'string', 'group' => 'compliance'],

            // Data Retention Policy
            ['key' => 'data_retention_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'data_retention_active_users_days', 'value' => '-1', 'type' => 'number', 'group' => 'compliance'], // -1 = indefinite
            ['key' => 'data_retention_inactive_users_days', 'value' => '730', 'type' => 'number', 'group' => 'compliance'], // 2 years
            ['key' => 'data_retention_deleted_users_days', 'value' => '90', 'type' => 'number', 'group' => 'compliance'],
            ['key' => 'data_retention_transactions_days', 'value' => '2555', 'type' => 'number', 'group' => 'compliance'], // 7 years
            ['key' => 'data_retention_logs_days', 'value' => '90', 'type' => 'number', 'group' => 'compliance'],
            ['key' => 'data_retention_audit_trail_days', 'value' => '2555', 'type' => 'number', 'group' => 'compliance'], // 7 years
            ['key' => 'data_retention_support_tickets_days', 'value' => '365', 'type' => 'number', 'group' => 'compliance'],
            ['key' => 'data_retention_email_notifications_days', 'value' => '30', 'type' => 'number', 'group' => 'compliance'],
            ['key' => 'data_retention_auto_cleanup_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'compliance'],

            // Consent Management
            ['key' => 'consent_required_for_registration', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'consent_required_documents', 'value' => 'terms_of_service,privacy_policy', 'type' => 'string', 'group' => 'compliance'],
            ['key' => 'consent_version_tracking', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'consent_reaccept_on_update', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'consent_withdrawal_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'consent_granular_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'consent_marketing_emails', 'value' => 'opt-in', 'type' => 'string', 'group' => 'compliance'], // opt-in, opt-out
            ['key' => 'consent_marketing_sms', 'value' => 'opt-in', 'type' => 'string', 'group' => 'compliance'],
            ['key' => 'consent_data_sharing', 'value' => 'opt-in', 'type' => 'string', 'group' => 'compliance'],
            ['key' => 'consent_profiling', 'value' => 'opt-in', 'type' => 'string', 'group' => 'compliance'],

            // Legal Document Types
            ['key' => 'legal_terms_of_service_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'legal_privacy_policy_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'legal_refund_policy_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'legal_risk_disclosure_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'legal_disclaimer_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'legal_acceptable_use_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],

            // Compliance Notifications
            ['key' => 'compliance_notify_on_export', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'compliance_notify_on_deletion', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'compliance_notify_admin_on_export', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'compliance_notify_admin_on_deletion', 'value' => 'true', 'type' => 'boolean', 'group' => 'compliance'],
            ['key' => 'compliance_export_format', 'value' => 'json', 'type' => 'string', 'group' => 'compliance'], // json, csv, xml
            ['key' => 'compliance_export_includes', 'value' => 'profile,transactions,subscriptions,kyc,support', 'type' => 'string', 'group' => 'compliance'],

            // ============================================================
            // SUPPORT SYSTEM SETTINGS
            // ============================================================
            // Ticket System
            ['key' => 'support_auto_assign_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'support'],
            ['key' => 'support_auto_assign_strategy', 'value' => 'round_robin', 'type' => 'string', 'group' => 'support'], // round_robin, least_busy, random
            ['key' => 'support_default_priority', 'value' => 'medium', 'type' => 'string', 'group' => 'support'],
            ['key' => 'support_default_sla_hours', 'value' => '24', 'type' => 'number', 'group' => 'support'],
            ['key' => 'support_high_priority_sla_hours', 'value' => '4', 'type' => 'number', 'group' => 'support'],
            ['key' => 'support_medium_priority_sla_hours', 'value' => '24', 'type' => 'number', 'group' => 'support'],
            ['key' => 'support_low_priority_sla_hours', 'value' => '72', 'type' => 'number', 'group' => 'support'],

            // Ticket Auto-Close
            ['key' => 'support_auto_close_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'support'],
            ['key' => 'support_auto_close_days', 'value' => '7', 'type' => 'number', 'group' => 'support'],
            ['key' => 'support_auto_close_notify_user', 'value' => 'true', 'type' => 'boolean', 'group' => 'support'],

            // Ticket Escalation
            ['key' => 'support_escalation_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'support'],
            ['key' => 'support_escalation_notify_admins', 'value' => 'true', 'type' => 'boolean', 'group' => 'support'],

            // Ticket Rating
            ['key' => 'support_rating_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'support'],
            ['key' => 'support_rating_required', 'value' => 'false', 'type' => 'boolean', 'group' => 'support'],

            // Canned Responses
            ['key' => 'support_canned_responses_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'support'],

            // Ticket Categories (comma-separated)
            ['key' => 'support_categories', 'value' => 'general,payment,kyc,withdrawal,bonus,technical,account', 'type' => 'string', 'group' => 'support'],

            // Ticket Priorities (comma-separated)
            ['key' => 'support_priorities', 'value' => 'low,medium,high,urgent', 'type' => 'string', 'group' => 'support'],

            // ============================================================
            // LIVE CHAT SETTINGS
            // ============================================================
            ['key' => 'live_chat_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'chat'],
            ['key' => 'live_chat_online_status', 'value' => 'auto', 'type' => 'string', 'group' => 'chat'], // auto, online, offline
            ['key' => 'live_chat_office_hours_start', 'value' => '09:00', 'type' => 'string', 'group' => 'chat'],
            ['key' => 'live_chat_office_hours_end', 'value' => '18:00', 'type' => 'string', 'group' => 'chat'],
            ['key' => 'live_chat_offline_message', 'value' => 'Our support team is currently offline. Please leave a message or create a ticket.', 'type' => 'text', 'group' => 'chat'],
            ['key' => 'live_chat_welcome_message', 'value' => 'Hello! How can we help you today?', 'type' => 'text', 'group' => 'chat'],
            ['key' => 'live_chat_max_concurrent_chats', 'value' => '5', 'type' => 'number', 'group' => 'chat'],
            ['key' => 'live_chat_auto_assign', 'value' => 'true', 'type' => 'boolean', 'group' => 'chat'],
            ['key' => 'live_chat_transcript_storage', 'value' => 'true', 'type' => 'boolean', 'group' => 'chat'],
            ['key' => 'live_chat_transcript_retention_days', 'value' => '90', 'type' => 'number', 'group' => 'chat'],
            ['key' => 'live_chat_typing_indicator', 'value' => 'true', 'type' => 'boolean', 'group' => 'chat'],
            ['key' => 'live_chat_file_upload', 'value' => 'true', 'type' => 'boolean', 'group' => 'chat'],
            ['key' => 'live_chat_max_file_size', 'value' => '5120', 'type' => 'number', 'group' => 'chat'], // KB

            // ============================================================
            // KNOWLEDGE BASE SETTINGS
            // ============================================================
            ['key' => 'kb_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'knowledge_base'],
            ['key' => 'kb_public_access', 'value' => 'true', 'type' => 'boolean', 'group' => 'knowledge_base'],
            ['key' => 'kb_search_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'knowledge_base'],
            ['key' => 'kb_search_analytics', 'value' => 'true', 'type' => 'boolean', 'group' => 'knowledge_base'],
            ['key' => 'kb_article_views_tracking', 'value' => 'true', 'type' => 'boolean', 'group' => 'knowledge_base'],
            ['key' => 'kb_article_rating_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'knowledge_base'],
            ['key' => 'kb_article_comments_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'knowledge_base'],
            ['key' => 'kb_related_articles_count', 'value' => '5', 'type' => 'number', 'group' => 'knowledge_base'],
            ['key' => 'kb_popular_articles_count', 'value' => '10', 'type' => 'number', 'group' => 'knowledge_base'],
            ['key' => 'kb_recent_articles_count', 'value' => '5', 'type' => 'number', 'group' => 'knowledge_base'],

            // ============================================================
            // SEO & META MANAGEMENT SETTINGS
            // ============================================================

            // Global SEO Configuration
            ['key' => 'seo_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_meta_title_suffix', 'value' => ' | PreIPO SIP', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_meta_title_separator', 'value' => '|', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_default_title', 'value' => 'PreIPO SIP - Pre-IPO Investment Platform', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_default_description', 'value' => 'Invest in pre-IPO companies with systematic investment plans. Secure, transparent, and professional investment platform.', 'type' => 'text', 'group' => 'seo'],
            ['key' => 'seo_default_keywords', 'value' => 'pre-ipo, investment, sip, startup investment, equity investment', 'type' => 'text', 'group' => 'seo'],
            ['key' => 'seo_default_author', 'value' => 'PreIPO SIP Team', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_canonical_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_auto_meta_description', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_auto_meta_keywords', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],

            // Open Graph (OG) Tags
            ['key' => 'seo_og_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_og_site_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_og_default_image', 'value' => '/images/og-default.jpg', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_og_default_type', 'value' => 'website', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_og_locale', 'value' => 'en_US', 'type' => 'string', 'group' => 'seo'],

            // Twitter Card Tags
            ['key' => 'seo_twitter_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_twitter_card_type', 'value' => 'summary_large_image', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_twitter_site', 'value' => '@preiposip', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_twitter_creator', 'value' => '@preiposip', 'type' => 'string', 'group' => 'seo'],

            // Schema.org Markup
            ['key' => 'seo_schema_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_schema_type', 'value' => 'FinancialService', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_schema_organization_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_schema_logo', 'value' => '/images/logo.png', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_schema_contact_type', 'value' => 'customer support', 'type' => 'string', 'group' => 'seo'],

            // Robots.txt Configuration
            ['key' => 'seo_robots_txt', 'value' => "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /user/\nAllow: /\nSitemap: {siteUrl}/sitemap.xml", 'type' => 'text', 'group' => 'seo'],
            ['key' => 'seo_robots_meta_default', 'value' => 'index, follow', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_robots_noindex_users', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_robots_noindex_admin', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],

            // Sitemap Configuration
            ['key' => 'seo_sitemap_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_sitemap_auto_generate', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_sitemap_frequency', 'value' => 'daily', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_sitemap_priority_home', 'value' => '1.0', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_sitemap_priority_pages', 'value' => '0.8', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_sitemap_priority_products', 'value' => '0.9', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_sitemap_priority_blog', 'value' => '0.7', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'seo_sitemap_include_images', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_sitemap_ping_google', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_sitemap_ping_bing', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],

            // Redirects Configuration
            ['key' => 'seo_redirects_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_redirects_tracking', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_redirects_log_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_redirects_wildcard_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'seo'],

            // Analytics Integration
            ['key' => 'analytics_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_google_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_google_id', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'analytics_google_ga4_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_google_measurement_id', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'analytics_gtm_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_gtm_id', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'analytics_facebook_pixel_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_facebook_pixel_id', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'analytics_hotjar_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_hotjar_id', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'analytics_mixpanel_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_mixpanel_token', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'analytics_custom_script', 'value' => '', 'type' => 'text', 'group' => 'analytics'],
            ['key' => 'analytics_track_logged_users', 'value' => 'true', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_anonymize_ip', 'value' => 'true', 'type' => 'boolean', 'group' => 'analytics'],
            ['key' => 'analytics_respect_dnt', 'value' => 'true', 'type' => 'boolean', 'group' => 'analytics'], // Do Not Track

            // SEO Analysis
            ['key' => 'seo_analysis_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_analysis_min_score', 'value' => '70', 'type' => 'number', 'group' => 'seo'],
            ['key' => 'seo_analysis_auto_suggestions', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_analysis_check_images', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_analysis_check_links', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_analysis_check_readability', 'value' => 'true', 'type' => 'boolean', 'group' => 'seo'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}