<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Sector;
use App\Models\FeatureFlag;
use App\Models\KycRejectionTemplate;
use App\Models\LegalAgreement;

/**
 * Foundation Seeder - Phase 1
 *
 * Seeds foundational data with NO foreign key dependencies:
 * - Settings (system configuration)
 * - Permissions & Roles (Spatie)
 * - Sectors (industry taxonomy)
 * - Feature Flags (module toggles)
 * - KYC Rejection Templates
 * - Legal Agreements
 *
 * CRITICAL: This must run FIRST before all other seeders.
 * All data is idempotent and production-safe.
 */
class FoundationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedSettings();
            $this->seedPermissionsAndRoles();
            $this->seedSectors();
            $this->seedFeatureFlags();
            $this->seedKycRejectionTemplates();
            $this->seedLegalAgreements();
        });

        $this->command->info('✅ Foundation data seeded successfully');
    }

    /**
     * Seed system settings
     * All business logic configuration - NO hardcoded values
     */
    private function seedSettings(): void
    {
        $settings = [
            // === SYSTEM CONFIGURATION ===
            ['group' => 'system', 'key' => 'platform_name', 'value' => 'PreIPOsip', 'type' => 'string', 'description' => 'Platform display name'],
            ['group' => 'system', 'key' => 'platform_url', 'value' => 'https://preiposip.com', 'type' => 'string', 'description' => 'Primary platform URL'],
            ['group' => 'system', 'key' => 'support_email', 'value' => 'support@preiposip.com', 'type' => 'string', 'description' => 'Support contact email'],
            ['group' => 'system', 'key' => 'support_phone', 'value' => '+91-9876543210', 'type' => 'string', 'description' => 'Support contact phone'],
            ['group' => 'system', 'key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable maintenance mode'],
            ['group' => 'system', 'key' => 'timezone', 'value' => 'Asia/Kolkata', 'type' => 'string', 'description' => 'Platform timezone'],
            ['group' => 'system', 'key' => 'currency', 'value' => 'INR', 'type' => 'string', 'description' => 'Platform currency'],
            ['group' => 'system', 'key' => 'currency_symbol', 'value' => '₹', 'type' => 'string', 'description' => 'Currency symbol'],

            // === INVESTMENT CONFIGURATION ===
            ['group' => 'investment', 'key' => 'min_investment_amount', 'value' => '5000', 'type' => 'integer', 'description' => 'Minimum investment amount in INR'],
            ['group' => 'investment', 'key' => 'max_investment_amount', 'value' => '1000000', 'type' => 'integer', 'description' => 'Maximum investment amount in INR'],
            ['group' => 'investment', 'key' => 'allow_partial_exits', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow partial investment exits'],
            ['group' => 'investment', 'key' => 'exit_penalty_percentage', 'value' => '2.0', 'type' => 'float', 'description' => 'Early exit penalty percentage'],
            ['group' => 'investment', 'key' => 'allocation_priority', 'value' => 'plan_tier', 'type' => 'string', 'description' => 'Allocation priority logic (plan_tier, fcfs, proportional)'],
            ['group' => 'investment', 'key' => 'enable_auto_allocation', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable automatic share allocation'],

            // === BONUS CONFIGURATION ===
            ['group' => 'bonus', 'key' => 'enable_progressive_bonus', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable progressive monthly bonuses'],
            ['group' => 'bonus', 'key' => 'enable_milestone_bonus', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable milestone bonuses'],
            ['group' => 'bonus', 'key' => 'enable_referral_bonus', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable referral bonuses'],
            ['group' => 'bonus', 'key' => 'enable_consistency_bonus', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable consistency streak bonuses'],
            ['group' => 'bonus', 'key' => 'progressive_bonus_calculation', 'value' => 'monthly', 'type' => 'string', 'description' => 'Progressive bonus frequency (monthly, quarterly)'],
            ['group' => 'bonus', 'key' => 'bonus_credit_timing', 'value' => 'immediate', 'type' => 'string', 'description' => 'When to credit bonuses (immediate, month_end)'],

            // === KYC CONFIGURATION ===
            ['group' => 'kyc', 'key' => 'kyc_required_for_investment', 'value' => 'true', 'type' => 'boolean', 'description' => 'Require KYC verification before investment'],
            ['group' => 'kyc', 'key' => 'kyc_auto_approval_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable automatic KYC approval'],
            ['group' => 'kyc', 'key' => 'kyc_document_expiry_days', 'value' => '365', 'type' => 'integer', 'description' => 'KYC document validity in days'],
            ['group' => 'kyc', 'key' => 'kyc_required_documents', 'value' => json_encode(['aadhaar', 'pan', 'bank_statement']), 'type' => 'json', 'description' => 'Required KYC documents'],
            ['group' => 'kyc', 'key' => 'kyc_min_age', 'value' => '18', 'type' => 'integer', 'description' => 'Minimum age for KYC approval'],
            ['group' => 'kyc', 'key' => 'kyc_max_age', 'value' => '75', 'type' => 'integer', 'description' => 'Maximum age for KYC approval'],

            // === WITHDRAWAL CONFIGURATION ===
            ['group' => 'withdrawal', 'key' => 'min_withdrawal_amount', 'value' => '500', 'type' => 'integer', 'description' => 'Minimum withdrawal amount in INR'],
            ['group' => 'withdrawal', 'key' => 'max_withdrawal_per_day', 'value' => '100000', 'type' => 'integer', 'description' => 'Maximum daily withdrawal limit'],
            ['group' => 'withdrawal', 'key' => 'withdrawal_processing_fee_percentage', 'value' => '1.0', 'type' => 'float', 'description' => 'Withdrawal processing fee percentage'],
            ['group' => 'withdrawal', 'key' => 'withdrawal_auto_approval_threshold', 'value' => '10000', 'type' => 'integer', 'description' => 'Auto-approve withdrawals below this amount'],
            ['group' => 'withdrawal', 'key' => 'withdrawal_processing_time_hours', 'value' => '24', 'type' => 'integer', 'description' => 'Expected withdrawal processing time'],

            // === PAYMENT GATEWAY CONFIGURATION ===
            ['group' => 'payment', 'key' => 'razorpay_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable Razorpay gateway'],
            ['group' => 'payment', 'key' => 'payment_timeout_minutes', 'value' => '15', 'type' => 'integer', 'description' => 'Payment session timeout in minutes'],
            ['group' => 'payment', 'key' => 'enable_upi', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable UPI payments'],
            ['group' => 'payment', 'key' => 'enable_cards', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable card payments'],
            ['group' => 'payment', 'key' => 'enable_netbanking', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable netbanking payments'],

            // === REFERRAL CONFIGURATION ===
            ['group' => 'referral', 'key' => 'referral_bonus_amount', 'value' => '500', 'type' => 'integer', 'description' => 'Referral bonus amount in INR'],
            ['group' => 'referral', 'key' => 'referral_minimum_investment', 'value' => '5000', 'type' => 'integer', 'description' => 'Minimum investment to earn referral bonus'],
            ['group' => 'referral', 'key' => 'referral_max_level', 'value' => '3', 'type' => 'integer', 'description' => 'Maximum referral levels (multi-level)'],
            ['group' => 'referral', 'key' => 'enable_referral_system', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable referral system'],

            // === LUCKY DRAW CONFIGURATION ===
            ['group' => 'lucky_draw', 'key' => 'enable_lucky_draws', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable lucky draw feature'],
            ['group' => 'lucky_draw', 'key' => 'lucky_draw_frequency', 'value' => 'monthly', 'type' => 'string', 'description' => 'Lucky draw frequency (monthly, quarterly)'],
            ['group' => 'lucky_draw', 'key' => 'lucky_draw_min_investment', 'value' => '5000', 'type' => 'integer', 'description' => 'Minimum investment for lucky draw entry'],
            ['group' => 'lucky_draw', 'key' => 'lucky_draw_entries_per_investment', 'value' => '1', 'type' => 'integer', 'description' => 'Draw entries per investment'],

            // === PROFIT SHARING CONFIGURATION ===
            ['group' => 'profit_sharing', 'key' => 'enable_profit_sharing', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable profit sharing feature'],
            ['group' => 'profit_sharing', 'key' => 'profit_share_frequency', 'value' => 'quarterly', 'type' => 'string', 'description' => 'Profit sharing frequency'],
            ['group' => 'profit_sharing', 'key' => 'profit_share_min_months', 'value' => '6', 'type' => 'integer', 'description' => 'Minimum active months for profit sharing eligibility'],

            // === NOTIFICATION CONFIGURATION ===
            ['group' => 'notification', 'key' => 'enable_email_notifications', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable email notifications'],
            ['group' => 'notification', 'key' => 'enable_sms_notifications', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable SMS notifications'],
            ['group' => 'notification', 'key' => 'enable_push_notifications', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable push notifications'],
            ['group' => 'notification', 'key' => 'sms_provider', 'value' => 'msg91', 'type' => 'string', 'description' => 'SMS provider (msg91, twilio)'],
            ['group' => 'notification', 'key' => 'email_provider', 'value' => 'smtp', 'type' => 'string', 'description' => 'Email provider'],

            // === SECURITY CONFIGURATION ===
            ['group' => 'security', 'key' => 'enable_2fa', 'value' => 'false', 'type' => 'boolean', 'description' => 'Require 2FA for all users'],
            ['group' => 'security', 'key' => 'session_timeout_minutes', 'value' => '60', 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            ['group' => 'security', 'key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'description' => 'Maximum login attempts before lockout'],
            ['group' => 'security', 'key' => 'lockout_duration_minutes', 'value' => '30', 'type' => 'integer', 'description' => 'Account lockout duration'],
            ['group' => 'security', 'key' => 'password_expiry_days', 'value' => '90', 'type' => 'integer', 'description' => 'Password expiry in days (0 = never)'],
            ['group' => 'security', 'key' => 'require_password_history', 'value' => '5', 'type' => 'integer', 'description' => 'Number of previous passwords to prevent reuse'],

            // === TDS CONFIGURATION ===
            ['group' => 'tds', 'key' => 'tds_rate_percentage', 'value' => '10', 'type' => 'float', 'description' => 'TDS deduction rate percentage'],
            ['group' => 'tds', 'key' => 'tds_threshold_amount', 'value' => '10000', 'type' => 'integer', 'description' => 'Minimum amount for TDS deduction'],
            ['group' => 'tds', 'key' => 'enable_tds_deduction', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable TDS deductions'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('  ✓ Settings seeded: ' . count($settings) . ' records');
    }

    /**
     * Seed Spatie permissions and roles
     */
    private function seedPermissionsAndRoles(): void
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions grouped by module
        $permissions = [
            // User Management
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.suspend', 'users.activate',

            // KYC Management
            'kyc.view', 'kyc.approve', 'kyc.reject', 'kyc.edit',

            // Investment Management
            'investments.view', 'investments.create', 'investments.edit', 'investments.delete', 'investments.allocate',

            // Payment Management
            'payments.view', 'payments.process', 'payments.refund',

            // Withdrawal Management
            'withdrawals.view', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.process',

            // Plan Management
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',

            // Product Management
            'products.view', 'products.create', 'products.edit', 'products.delete',

            // Company Management
            'companies.view', 'companies.create', 'companies.edit', 'companies.delete',

            // Bulk Purchase Management
            'bulk_purchases.view', 'bulk_purchases.create', 'bulk_purchases.edit', 'bulk_purchases.delete',

            // Bonus Management
            'bonuses.view', 'bonuses.create', 'bonuses.edit', 'bonuses.delete', 'bonuses.calculate',

            // Campaign Management
            'campaigns.view', 'campaigns.create', 'campaigns.edit', 'campaigns.delete',

            // Lucky Draw Management
            'lucky_draws.view', 'lucky_draws.create', 'lucky_draws.execute', 'lucky_draws.edit',

            // Profit Sharing Management
            'profit_shares.view', 'profit_shares.create', 'profit_shares.distribute', 'profit_shares.edit',

            // Support Management
            'support.view', 'support.respond', 'support.assign', 'support.close',

            // Content Management
            'content.view', 'content.create', 'content.edit', 'content.delete', 'content.publish',

            // Settings Management
            'settings.view', 'settings.edit',

            // Reports & Analytics
            'reports.view', 'reports.generate', 'reports.export',

            // Audit Logs
            'audit.view',

            // System Developer Tools
            'system.developer.tools',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles with their permissions
        $roles = [
            'Super Admin' => $permissions, // All permissions
            'Admin' => array_filter($permissions, fn($p) => !str_starts_with($p, 'system.developer')),
            'Support Agent' => [
                'users.view', 'kyc.view', 'support.view', 'support.respond', 'support.assign', 'support.close',
                'investments.view', 'payments.view', 'withdrawals.view'
            ],
            'KYC Reviewer' => [
                'users.view', 'kyc.view', 'kyc.approve', 'kyc.reject', 'kyc.edit'
            ],
            'User' => [], // Regular users have no admin permissions
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('  ✓ Permissions seeded: ' . count($permissions) . ' records');
        $this->command->info('  ✓ Roles seeded: ' . count($roles) . ' records');
    }

    /**
     * Seed industry sectors
     */
    private function seedSectors(): void
    {
        $sectors = [
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Software, Hardware, IT Services'],
            ['name' => 'Healthcare', 'slug' => 'healthcare', 'description' => 'Medical, Pharmaceuticals, Biotech'],
            ['name' => 'Financial Services', 'slug' => 'financial-services', 'description' => 'Banking, FinTech, Insurance'],
            ['name' => 'E-commerce', 'slug' => 'ecommerce', 'description' => 'Online Retail, Marketplaces'],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'EdTech, Online Learning, Training'],
            ['name' => 'Real Estate', 'slug' => 'real-estate', 'description' => 'PropTech, Real Estate Services'],
            ['name' => 'Manufacturing', 'slug' => 'manufacturing', 'description' => 'Industrial, Automotive, Consumer Goods'],
            ['name' => 'Energy', 'slug' => 'energy', 'description' => 'Renewable Energy, CleanTech, Power'],
            ['name' => 'Consumer Services', 'slug' => 'consumer-services', 'description' => 'Food, Hospitality, Lifestyle'],
            ['name' => 'Logistics', 'slug' => 'logistics', 'description' => 'Supply Chain, Transportation, Delivery'],
            ['name' => 'Agriculture', 'slug' => 'agriculture', 'description' => 'AgriTech, Farming, Food Production'],
            ['name' => 'Media & Entertainment', 'slug' => 'media-entertainment', 'description' => 'Content, Gaming, Streaming'],
            ['name' => 'Telecommunications', 'slug' => 'telecommunications', 'description' => 'Telecom, Networking, Communication'],
            ['name' => 'Travel & Tourism', 'slug' => 'travel-tourism', 'description' => 'Hospitality, Travel Tech, Tourism'],
            ['name' => 'Others', 'slug' => 'others', 'description' => 'Miscellaneous sectors'],
        ];

        foreach ($sectors as $sector) {
            Sector::updateOrCreate(
                ['slug' => $sector['slug']],
                $sector
            );
        }

        $this->command->info('  ✓ Sectors seeded: ' . count($sectors) . ' records');
    }

    /**
     * Seed feature flags
     */
    private function seedFeatureFlags(): void
    {
        $features = [
            ['key' => 'enable_user_registration', 'name' => 'Enable User Registration', 'description' => 'Allow new user registrations', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_user_login', 'name' => 'Enable User Login', 'description' => 'Allow user login', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_investment', 'name' => 'Enable Investment', 'description' => 'Allow new investments', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_withdrawal', 'name' => 'Enable Withdrawal', 'description' => 'Allow withdrawal requests', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_kyc_submission', 'name' => 'Enable KYC Submission', 'description' => 'Allow KYC document submission', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_referral_system', 'name' => 'Enable Referral System', 'description' => 'Enable referral functionality', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_lucky_draws', 'name' => 'Enable Lucky Draws', 'description' => 'Enable lucky draw participation', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_profit_sharing', 'name' => 'Enable Profit Sharing', 'description' => 'Enable profit sharing distributions', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_bonuses', 'name' => 'Enable Bonuses', 'description' => 'Enable bonus calculations', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_support_tickets', 'name' => 'Enable Support Tickets', 'description' => 'Allow support ticket creation', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_live_chat', 'name' => 'Enable Live Chat', 'description' => 'Enable live chat support', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_company_portal', 'name' => 'Enable Company Portal', 'description' => 'Allow company user access', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_blog', 'name' => 'Enable Blog', 'description' => 'Display blog posts', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_promotional_campaigns', 'name' => 'Enable Promotional Campaigns', 'description' => 'Enable promotional campaigns', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_mobile_app', 'name' => 'Enable Mobile App', 'description' => 'Enable mobile app API access', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_notifications', 'name' => 'Enable Notifications', 'description' => 'Send notifications to users', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_email_verification', 'name' => 'Enable Email Verification', 'description' => 'Require email verification', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_mobile_verification', 'name' => 'Enable Mobile Verification', 'description' => 'Require mobile verification', 'is_active' => true, 'is_enabled' => true],
            ['key' => 'enable_2fa', 'name' => 'Enable 2FA', 'description' => 'Enable two-factor authentication', 'is_active' => false, 'is_enabled' => false],
            ['key' => 'maintenance_mode', 'name' => 'Maintenance Mode', 'description' => 'Enable maintenance mode', 'is_active' => false, 'is_enabled' => false],
        ];

        foreach ($features as $feature) {
            FeatureFlag::updateOrCreate(
                ['key' => $feature['key']],
                $feature
            );
        }

        $this->command->info('  ✓ Feature flags seeded: ' . count($features) . ' records');
    }

    /**
     * Seed KYC rejection templates
     */
    private function seedKycRejectionTemplates(): void
    {
        $templates = [
            ['name' => 'blurred_document', 'title' => 'Blurred Document', 'category' => 'document_quality', 'reason' => 'The submitted document is blurred or unclear. Please upload a clear, high-resolution image.', 'message' => 'Your document could not be verified due to poor image quality.'],
            ['name' => 'incomplete_document', 'title' => 'Incomplete Document', 'category' => 'document_quality', 'reason' => 'The document appears to be incomplete or cut off. Please upload the complete document.', 'message' => 'Please provide the complete document without any parts cut off.'],
            ['name' => 'expired_document', 'title' => 'Expired Document', 'category' => 'document_validity', 'reason' => 'The submitted document has expired. Please upload a valid, unexpired document.', 'message' => 'Please upload an unexpired document.'],
            ['name' => 'name_mismatch', 'title' => 'Name Mismatch', 'category' => 'identity_mismatch', 'reason' => 'The name on the document does not match your registered name. Please ensure all documents have consistent information.', 'message' => 'Name mismatch detected across documents.'],
            ['name' => 'address_mismatch', 'title' => 'Address Mismatch', 'category' => 'identity_mismatch', 'reason' => 'The address on the document does not match your registered address. Please submit documents with matching address details.', 'message' => 'Address mismatch detected across documents.'],
            ['name' => 'invalid_document_type', 'title' => 'Invalid Document Type', 'category' => 'document_type', 'reason' => 'The submitted document type is not accepted. Please upload a valid government-issued ID (Aadhaar, PAN, Passport, etc.).', 'message' => 'This document type is not accepted for KYC verification.'],
            ['name' => 'not_readable', 'title' => 'Document Not Readable', 'category' => 'document_quality', 'reason' => 'The text on the document is not readable. Please upload a clearer image with visible text.', 'message' => 'Document text is not readable.'],
            ['name' => 'minor_age', 'title' => 'Minor Age', 'category' => 'eligibility', 'reason' => 'Your age is below the minimum requirement of 18 years. Unfortunately, we cannot process your KYC at this time.', 'message' => 'Minimum age requirement not met.'],
            ['name' => 'bank_details_mismatch', 'title' => 'Bank Details Mismatch', 'category' => 'financial_mismatch', 'reason' => 'The bank account details do not match your KYC information. Please verify and resubmit.', 'message' => 'Bank account information does not match KYC details.'],
            ['name' => 'suspected_fraud', 'title' => 'Suspected Fraud', 'category' => 'security', 'reason' => 'We detected potential discrepancies in your submission. Please contact support for further assistance.', 'message' => 'Verification could not be completed. Please contact support.'],
        ];

        foreach ($templates as $template) {
            KycRejectionTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('  ✓ KYC rejection templates seeded: ' . count($templates) . ' records');
    }

    /**
     * Seed legal agreements
     */
    private function seedLegalAgreements(): void
    {
        $agreements = [
            [
                'title' => 'Terms and Conditions',
                'type' => 'terms_of_service',
                'version' => '1.0',
                'content' => '<h1>Terms and Conditions</h1><p>Last updated: ' . now()->format('F d, Y') . '</p><p>Please read these terms and conditions carefully before using our platform...</p>',
                'require_signature' => true,
                'effective_date' => now(),
                'status' => 'active',
            ],
            [
                'title' => 'Privacy Policy',
                'type' => 'privacy_policy',
                'version' => '1.0',
                'content' => '<h1>Privacy Policy</h1><p>Last updated: ' . now()->format('F d, Y') . '</p><p>This Privacy Policy describes how we collect, use, and protect your personal information...</p>',
                'require_signature' => true,
                'effective_date' => now(),
                'status' => 'active',
            ],
            [
                'title' => 'Risk Disclosure',
                'type' => 'risk_disclosure',
                'version' => '1.0',
                'content' => '<h1>Risk Disclosure Statement</h1><p>Investments in Pre-IPO companies carry significant risks...</p>',
                'require_signature' => true,
                'effective_date' => now(),
                'status' => 'active',
            ],
            [
                'title' => 'Refund Policy',
                'type' => 'refund_policy',
                'version' => '1.0',
                'content' => '<h1>Refund Policy</h1><p>This policy outlines the terms and conditions for refunds...</p>',
                'require_signature' => false,
                'effective_date' => now(),
                'status' => 'active',
            ],
            [
                'title' => 'Cookie Policy',
                'type' => 'cookie_policy',
                'version' => '1.0',
                'content' => '<h1>Cookie Policy</h1><p>We use cookies to improve your experience on our platform...</p>',
                'require_signature' => false,
                'effective_date' => now(),
                'status' => 'active',
            ],
            [
                'title' => 'Investment Disclaimer',
                'type' => 'investment_disclaimer',
                'version' => '1.0',
                'content' => '<h1>SEBI Compliance</h1><p>This platform operates in accordance with SEBI regulations...</p>',
                'require_signature' => true,
                'effective_date' => now(),
                'status' => 'active',
            ],
        ];

        foreach ($agreements as $agreement) {
            LegalAgreement::updateOrCreate(
                ['type' => $agreement['type']],
                $agreement
            );
        }

        $this->command->info('  ✓ Legal agreements seeded: ' . count($agreements) . ' records');
    }
}
