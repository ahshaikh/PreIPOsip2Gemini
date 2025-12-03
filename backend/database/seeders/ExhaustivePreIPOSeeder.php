<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanConfig;
use App\Models\Product;
use App\Models\ProductHighlight;
use App\Models\ProductFounder;
use App\Models\ProductFundingRound;
use App\Models\ProductKeyMetric;
use App\Models\ProductRiskDisclosure;
use App\Models\ProductPriceHistory;
use App\Models\BulkPurchase;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\UserInvestment;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\BonusTransaction;
use App\Models\Referral;
use App\Models\ReferralCampaign;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\ProfitShare;
use App\Models\UserProfitShare;
use App\Models\CelebrationEvent;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\Setting;
use App\Models\FeatureFlag;
use App\Models\IpWhitelist;
use App\Models\LegalAgreement;
use App\Models\LegalAgreementVersion;
use App\Models\UserLegalAcceptance;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Banner;
use App\Models\Redirect;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\KbCategory;
use App\Models\KbArticle;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Models\CannedResponse;
use App\Models\KycRejectionTemplate;
use App\Models\ActivityLog;
use App\Models\WebhookLog;
use App\Models\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * EXHAUSTIVE PRE-IPO SIP PLATFORM SEEDER
 *
 * This seeder creates comprehensive test data covering:
 * - All system tables
 * - 3 User Classes: Visitors (Public), Authenticated Users, Admin Users
 * - All feature scenarios
 * - Realistic, production-like data
 *
 * Run: php artisan db:seed --class=ExhaustivePreIPOSeeder
 */
class ExhaustivePreIPOSeeder extends Seeder
{
    private $adminUsers = [];
    private $regularUsers = [];
    private $products = [];
    private $plans = [];
    private $bulkPurchases = [];

public function run(): void
{
    $this->command->info('🚀 Starting Exhaustive PreIPO SIP Platform Seeder...');

    DB::beginTransaction();

    // Ordered list of seeding steps
    $steps = [
        'System Configuration'        => 'seedSystemConfiguration',
        'Roles & Permissions'         => 'seedRolesAndPermissions',
        'Legal Documents'             => 'seedLegalDocuments',
        'Products'                    => 'seedProducts',
        'Investment Plans'            => 'seedPlans',
        'Admin Users'                 => 'seedAdminUsers',
        'CMS Content'                 => 'seedCMSContent',
        'Communication Templates'     => 'seedCommunicationTemplates',
        'Regular Users'               => 'seedRegularUsers',
        'Subscriptions & Payments'    => 'seedSubscriptionsAndPayments',
        'User Investments'            => 'seedUserInvestments',
        'Wallets & Transactions'      => 'seedWalletsAndTransactions',
        'Withdrawals'                 => 'seedWithdrawals',
        'Bonuses'                     => 'seedBonuses',
        'Referrals'                   => 'seedReferrals',
        'Lucky Draws'                 => 'seedLuckyDraws',
        'Profit Sharing'              => 'seedProfitSharing',
        'Support Tickets'             => 'seedSupportTickets',
        'Activity Logs'               => 'seedActivityLogs',
        'Notifications'               => 'seedNotifications',
    ];

    foreach ($steps as $label => $method) {
        $this->command->info("➡ Running: {$label}");

        try {
            $this->{$method}();
        } catch (\Throwable $e) {
            $this->command->error("❌ Error in {$label}");
            $this->command->error("Message: " . $e->getMessage());
            $this->command->error("File: " . $e->getFile() . ':' . $e->getLine());
            $this->command->error("--------------------------------------------------");
            continue; // continue to next seeding step even if this one fails
        }
    }

    DB::commit();

    $this->command->info('✅ Exhaustive seeding completed (with possible warnings).');
    $this->printSeederSummary();
}

// ==========================================
    // 1. SYSTEM CONFIGURATION
    // ==========================================

    private function seedSystemConfiguration(): void
    {
        $this->command->info('📊 Seeding System Configuration...');

        $settings = [
            // General
            ['key' => 'site_name', 'value' => 'PreIPO SIP', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'Invest in Pre-IPO companies with systematic investment plans', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_logo', 'value' => '/images/logo.png', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_favicon', 'value' => '/images/favicon.ico', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'support@preiposip.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '+91-9876543210', 'type' => 'string', 'group' => 'general'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'general'],

            // Payment
            ['key' => 'min_payment_amount', 'value' => '500', 'type' => 'number', 'group' => 'payment'],
            ['key' => 'max_payment_amount', 'value' => '1000000', 'type' => 'number', 'group' => 'payment'],
            ['key' => 'razorpay_key_id', 'value' => 'rzp_test_xxxxxxxxxxxxxx', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'payment_gateway_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'manual_payment_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment'],

            // Withdrawal
            ['key' => 'min_withdrawal_amount', 'value' => '1000', 'type' => 'number', 'group' => 'withdrawal'],
            ['key' => 'max_withdrawal_amount', 'value' => '500000', 'type' => 'number', 'group' => 'withdrawal'],
            ['key' => 'withdrawal_fee_percentage', 'value' => '1.5', 'type' => 'number', 'group' => 'withdrawal'],
            ['key' => 'withdrawal_processing_days', 'value' => '7', 'type' => 'number', 'group' => 'withdrawal'],

            // KYC
            ['key' => 'kyc_required', 'value' => 'true', 'type' => 'boolean', 'group' => 'kyc'],
            ['key' => 'kyc_auto_verification', 'value' => 'false', 'type' => 'boolean', 'group' => 'kyc'],
            ['key' => 'max_kyc_attempts', 'value' => '3', 'type' => 'number', 'group' => 'kyc'],

            // Referral
            ['key' => 'referral_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'referral'],
            ['key' => 'referral_bonus_amount', 'value' => '500', 'type' => 'number', 'group' => 'referral'],
            ['key' => 'referral_tier_1_threshold', 'value' => '5', 'type' => 'number', 'group' => 'referral'],
            ['key' => 'referral_tier_1_multiplier', 'value' => '1.5', 'type' => 'number', 'group' => 'referral'],
            ['key' => 'referral_tier_2_threshold', 'value' => '10', 'type' => 'number', 'group' => 'referral'],
            ['key' => 'referral_tier_2_multiplier', 'value' => '2.0', 'type' => 'number', 'group' => 'referral'],
            ['key' => 'referral_tier_3_threshold', 'value' => '20', 'type' => 'number', 'group' => 'referral'],
            ['key' => 'referral_tier_3_multiplier', 'value' => '3.0', 'type' => 'number', 'group' => 'referral'],

            // Bonus
            ['key' => 'tds_percentage', 'value' => '10', 'type' => 'number', 'group' => 'bonus'],
            ['key' => 'bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus'],
            ['key' => 'progressive_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus'],
            ['key' => 'milestone_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus'],
            ['key' => 'consistency_bonus_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'bonus'],

            // Support
            ['key' => 'support_ticket_sla_hours', 'value' => '24', 'type' => 'number', 'group' => 'support'],
            ['key' => 'support_email', 'value' => 'support@preiposip.com', 'type' => 'string', 'group' => 'support'],

            // Security
            ['key' => '2fa_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'password_expiry_days', 'value' => '90', 'type' => 'number', 'group' => 'security'],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'number', 'group' => 'security'],
            ['key' => 'session_timeout_minutes', 'value' => '120', 'type' => 'number', 'group' => 'security'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Feature Flags
        $featureFlags = [
            ['key' => 'new-homepage', 'name' => 'New Homepage Design', 'description' => 'A/B test for new homepage', 'is_enabled' => true, 'rollout_percentage' => 50],
            ['key' => 'auto-debit', 'name' => 'Auto-Debit Subscriptions', 'description' => 'Enable automatic recurring payments', 'is_enabled' => true, 'rollout_percentage' => 100],
            ['key' => 'profit-sharing', 'name' => 'Profit Sharing Module', 'description' => 'Quarterly profit distribution', 'is_enabled' => true, 'rollout_percentage' => 100],
            ['key' => 'lucky-draw', 'name' => 'Lucky Draw System', 'description' => 'Monthly lucky draw for users', 'is_enabled' => true, 'rollout_percentage' => 100],
            ['key' => 'social-login', 'name' => 'Social Login', 'description' => 'Login with Google', 'is_enabled' => true, 'rollout_percentage' => 100],
            ['key' => 'digilocker-kyc', 'name' => 'DigiLocker KYC', 'description' => 'KYC via DigiLocker', 'is_enabled' => true, 'rollout_percentage' => 100],
        ];

        foreach ($featureFlags as $flag) {
            FeatureFlag::updateOrCreate(
                ['key' => $flag['key']],
                $flag
            );
        }

        // IP Whitelist (Admin access)
        $ipWhitelist = [
            ['ip_address' => '127.0.0.1', 'description' => 'Localhost', 'is_active' => true],
            ['ip_address' => '::1', 'description' => 'Localhost IPv6', 'is_active' => true],
            ['ip_address' => '192.168.1.0/24', 'description' => 'Local Network', 'is_active' => true],
        ];

        foreach ($ipWhitelist as $ip) {
            IpWhitelist::updateOrCreate(
                ['ip_address' => $ip['ip_address']],
                $ip
            );
        }

        $this->command->info('   ✓ System settings, feature flags, and IP whitelist seeded');
    }

    // ==========================================
    // 2. ROLES & PERMISSIONS
    // ==========================================

    private function seedRolesAndPermissions(): void
    {
        $this->command->info('🔐 Seeding Roles & Permissions...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions - MUST MATCH route middleware checks exactly
        $permissions = [
            // User Management (users.*)
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.export', 'users.import',
            'users.suspend', 'users.restore', 'users.adjust_wallet', 'users.manage_roles',

            // KYC Management (kyc.*)
            'kyc.view_queue', 'kyc.approve', 'kyc.reject', 'kyc.export',

            // Plan Management (plans.*)
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',

            // Product Management (products.*)
            'products.view', 'products.create', 'products.edit', 'products.delete',

            // Bulk Purchase Management
            'bulk_purchases.view', 'bulk_purchases.create', 'bulk_purchases.edit', 'bulk_purchases.delete',

            // Payment Management (payments.*)
            'payments.view', 'payments.create', 'payments.refund', 'payments.export',
            'payments.offline_entry', 'payments.approve',

            // Withdrawal Management (withdrawals.*)
            'withdrawals.view', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.process',

            // Bonus Management (bonuses.*)
            'bonuses.view', 'bonuses.create', 'bonuses.adjust', 'bonuses.delete',

            // Referral Management (referrals.*)
            'referrals.view', 'referrals.manage_campaigns',

            // Lucky Draw Management (lucky_draws.*)
            'lucky_draws.view', 'lucky_draws.create', 'lucky_draws.execute', 'lucky_draws.delete',

            // Profit Sharing Management (profit_sharing.*)
            'profit_sharing.view', 'profit_sharing.create', 'profit_sharing.calculate',
            'profit_sharing.distribute', 'profit_sharing.reverse',

            // Support Management (support.*)
            'support.view_tickets', 'support.assign_tickets', 'support.resolve_tickets', 'support.close_tickets',

            // CMS Management (settings.manage_cms)
            'settings.manage_cms',

            // Notification Management (settings.manage_notifications)
            'settings.manage_notifications',

            // Settings Management (settings.*)
            'settings.view_system', 'settings.edit_system', 'settings.manage_theme',

            // Compliance Management (compliance.*)
            'compliance.view', 'compliance.create', 'compliance.edit', 'compliance.delete',
            'compliance.publish', 'compliance.archive',

            // Report Access (reports.*)
            'reports.view', 'reports.export', 'reports.view_financial', 'reports.view_user',

            // System Management (system.*)
            'system.view_logs', 'system.view_health', 'system.manage_backups',
            'system.manage_ip_whitelist', 'system.manage_feature_flags',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Roles - using lowercase hyphenated names for consistency with middleware
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $kycOfficer = Role::firstOrCreate(['name' => 'kyc-officer', 'guard_name' => 'web']);
        $supportAgent = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);
        $contentManager = Role::firstOrCreate(['name' => 'content-manager', 'guard_name' => 'web']);
        $financeManager = Role::firstOrCreate(['name' => 'finance-manager', 'guard_name' => 'web']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superAdmin->givePermissionTo(Permission::all());

        $admin->givePermissionTo([
            'users.view', 'users.edit', 'users.suspend',
            'kyc.view_queue', 'kyc.approve', 'kyc.reject',
            'plans.view', 'plans.create', 'plans.edit',
            'products.view', 'products.create', 'products.edit',
            'payments.view', 'payments.refund',
            'withdrawals.view', 'withdrawals.approve', 'withdrawals.reject',
            'bonuses.view', 'bonuses.create',
            'referrals.view',
            'support.view_tickets', 'support.assign_tickets', 'support.resolve_tickets',
            'reports.view', 'reports.export',
        ]);

        $kycOfficer->givePermissionTo([
            'users.view', 'kyc.view_queue', 'kyc.approve', 'kyc.reject', 'kyc.export',
        ]);

        $supportAgent->givePermissionTo([
            'support.view_tickets', 'support.assign_tickets', 'support.resolve_tickets', 'support.close_tickets',
            'users.view',
        ]);

        $contentManager->givePermissionTo([
            'settings.manage_cms',
        ]);

        $financeManager->givePermissionTo([
            'payments.view', 'payments.export', 'payments.refund',
            'withdrawals.view', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.process',
            'bonuses.view', 'bonuses.create', 'bonuses.adjust',
            'profit_sharing.view', 'profit_sharing.create', 'profit_sharing.calculate', 'profit_sharing.distribute',
            'reports.view_financial', 'reports.view', 'reports.export',
        ]);

        // User role has no admin permissions

        $this->command->info('   ✓ Roles and permissions seeded');
    }

    // ==========================================
    // 3. LEGAL & COMPLIANCE
    // ==========================================

    private function seedLegalDocuments(): void
    {
        $this->command->info('⚖️  Seeding Legal & Compliance Documents...');

        $legalDocs = [
            [
                'type' => 'terms_of_service',
                'title' => 'Terms of Service',
                'description' => 'Terms and conditions for using our platform',
                'content' => $this->getTermsOfServiceContent(),
                'version' => '1.0.0',
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(6),
                'require_signature' => true,
            ],
            [
                'type' => 'privacy_policy',
                'title' => 'Privacy Policy',
                'description' => 'How we collect, use, and protect your data',
                'content' => $this->getPrivacyPolicyContent(),
                'version' => '1.2.0',
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(3),
                'require_signature' => false,
            ],
            [
                'type' => 'cookie_policy',
                'title' => 'Cookie Policy',
                'description' => 'Information about cookies used on our platform',
                'content' => $this->getCookiePolicyContent(),
                'version' => '1.0.0',
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(6),
                'require_signature' => false,
            ],
            [
                'type' => 'investment_disclaimer',
                'title' => 'Investment Risk Disclaimer',
                'description' => 'Important disclaimers about investment risks',
                'content' => $this->getInvestmentDisclaimerContent(),
                'version' => '2.0.0',
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonth(),
                'require_signature' => true,
            ],
            [
                'type' => 'refund_policy',
                'title' => 'Refund and Cancellation Policy',
                'description' => 'Policies regarding refunds and cancellations',
                'content' => $this->getRefundPolicyContent(),
                'version' => '1.0.0',
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(6),
                'require_signature' => false,
            ],
        ];

        foreach ($legalDocs as $doc) {
            $agreement = LegalAgreement::create($doc);

            // Create version record
            LegalAgreementVersion::create([
                'legal_agreement_id' => $agreement->id,
                'version' => $doc['version'],
                'content' => $doc['content'],
                'change_summary' => 'Initial version',
                'status' => 'active',
                'effective_date' => $doc['effective_date'],
            ]);
        }

        $this->command->info('   ✓ Legal documents seeded');
    }

    // ==========================================
    // 4. PRODUCTS (Pre-IPO Companies)
    // ==========================================

    private function seedProducts(): void
    {
        $this->command->info('📦 Seeding Products (Pre-IPO Companies)...');

        $productsData = [
            [
                'name' => 'TechUnicorn Pvt Ltd',
                'sector' => 'Technology',
                'face_value_per_unit' => 10.00,
                'current_market_price' => 250.00,
                'min_investment' => 5000.00,
                'expected_ipo_date' => Carbon::now()->addMonths(6),
                'description' => json_encode([
                    'short' => 'Leading AI-powered SaaS platform',
                    'full' => 'TechUnicorn is revolutionizing enterprise software with cutting-edge artificial intelligence solutions.',
                ]),
                'highlights' => [
                    'Serving 10,000+ enterprise customers globally',
                    'ARR growth of 300% YoY',
                    'Backed by top-tier VCs including Sequoia and Tiger Global',
                    'Strong unit economics with 40% net margin',
                ],
                'founders' => [
                    ['name' => 'Rajesh Kumar', 'title' => 'Co-Founder & CEO', 'linkedin_url' => 'https://linkedin.com/in/rajeshkumar'],
                    ['name' => 'Priya Sharma', 'title' => 'Co-Founder & CTO', 'linkedin_url' => 'https://linkedin.com/in/priyasharma'],
                ],
                'funding_rounds' => [
                    ['round_name' => 'Series A', 'date' => '2020-03-15', 'amount' => 10000000, 'valuation' => 50000000, 'investors' => 'Sequoia Capital, Accel Partners'],
                    ['round_name' => 'Series B', 'date' => '2021-08-20', 'amount' => 50000000, 'valuation' => 250000000, 'investors' => 'Tiger Global, SoftBank Vision Fund'],
                    ['round_name' => 'Series C', 'date' => '2023-01-10', 'amount' => 150000000, 'valuation' => 1000000000, 'investors' => 'Sequoia Capital, Tiger Global, DST Global'],
                ],
                'key_metrics' => [
                    ['metric_name' => 'Annual Recurring Revenue', 'value' => '₹500', 'unit' => 'Crores'],
                    ['metric_name' => 'Customer Count', 'value' => '10,000+', 'unit' => 'Companies'],
                    ['metric_name' => 'Employee Strength', 'value' => '2,500', 'unit' => 'Employees'],
                    ['metric_name' => 'Net Revenue Retention', 'value' => '130', 'unit' => '%'],
                ],
                'risk_disclosures' => [
                    ['risk_category' => 'Market Risk', 'severity' => 'Medium', 'risk_title' => 'Competitive Market', 'risk_description' => 'The SaaS market is highly competitive with established players.'],
                    ['risk_category' => 'Regulatory Risk', 'severity' => 'Low', 'risk_title' => 'Data Privacy Regulations', 'risk_description' => 'Compliance with evolving data privacy laws may impact operations.'],
                    ['risk_category' => 'Business Risk', 'severity' => 'Medium', 'risk_title' => 'Customer Concentration', 'risk_description' => 'Top 10 customers account for 30% of revenue.'],
                ],
            ],
            [
                'name' => 'GreenEnergy Solutions Ltd',
                'sector' => 'Renewable Energy',
                'face_value_per_unit' => 10.00,
                'current_market_price' => 180.00,
                'min_investment' => 10000.00,
                'expected_ipo_date' => Carbon::now()->addMonths(9),
                'description' => json_encode([
                    'short' => 'Leading renewable energy provider',
                    'full' => 'GreenEnergy Solutions is at the forefront of India\'s renewable energy revolution, specializing in solar and wind power generation.',
                ]),
                'highlights' => [
                    'Installed capacity of 5 GW across India',
                    'Long-term PPAs with state utilities',
                    'Diversified portfolio of solar and wind assets',
                    'Strong ESG credentials',
                ],
                'founders' => [
                    ['name' => 'Anil Verma', 'title' => 'Founder & Managing Director', 'linkedin_url' => 'https://linkedin.com/in/anilverma'],
                ],
                'funding_rounds' => [
                    ['round_name' => 'Series A', 'date' => '2019-06-01', 'amount' => 25000000, 'valuation' => 100000000, 'investors' => 'ADIA, Mitsui'],
                    ['round_name' => 'Series B', 'date' => '2021-11-15', 'amount' => 100000000, 'valuation' => 500000000, 'investors' => 'ADIA, JERA, ReNew Power'],
                ],
                'key_metrics' => [
                    ['metric_name' => 'Installed Capacity', 'value' => '5', 'unit' => 'GW'],
                    ['metric_name' => 'Annual Generation', 'value' => '8,000', 'unit' => 'GWh'],
                    ['metric_name' => 'Revenue', 'value' => '₹2,500', 'unit' => 'Crores'],
                    ['metric_name' => 'EBITDA Margin', 'value' => '85', 'unit' => '%'],
                ],
                'risk_disclosures' => [
                    ['risk_category' => 'Regulatory Risk', 'severity' => 'High', 'risk_title' => 'Policy Changes', 'risk_description' => 'Changes in renewable energy policies may impact revenue.'],
                    ['risk_category' => 'Operational Risk', 'severity' => 'Medium', 'risk_title' => 'Weather Dependency', 'risk_description' => 'Generation depends on weather conditions.'],
                    ['risk_category' => 'Financial Risk', 'severity' => 'Low', 'risk_title' => 'Currency Risk', 'risk_description' => 'Some equipment is imported and exposed to forex risk.'],
                ],
            ],
            [
                'name' => 'HealthFirst Diagnostics',
                'sector' => 'Healthcare',
                'face_value_per_unit' => 10.00,
                'current_market_price' => 320.00,
                'min_investment' => 8000.00,
                'expected_ipo_date' => Carbon::now()->addMonths(4),
                'description' => json_encode([
                    'short' => 'Leading diagnostic services provider',
                    'full' => 'HealthFirst Diagnostics operates a pan-India network of diagnostic centers offering comprehensive health checkup packages.',
                ]),
                'highlights' => [
                    '500+ diagnostic centers across 200 cities',
                    'Serving 5 million+ patients annually',
                    'Advanced technology platform for digital reports',
                    'Strong brand recognition in tier-2 and tier-3 cities',
                ],
                'founders' => [
                    ['name' => 'Dr. Suresh Menon', 'title' => 'Founder & Chief Medical Officer', 'linkedin_url' => 'https://linkedin.com/in/sureshmenon'],
                    ['name' => 'Kavita Reddy', 'title' => 'Co-Founder & COO', 'linkedin_url' => 'https://linkedin.com/in/kavitareddy'],
                ],
                'funding_rounds' => [
                    ['round_name' => 'Series A', 'date' => '2018-09-10', 'amount' => 15000000, 'valuation' => 75000000, 'investors' => 'Chiratae Ventures, Kalaari Capital'],
                    ['round_name' => 'Series B', 'date' => '2020-12-05', 'amount' => 40000000, 'valuation' => 300000000, 'investors' => 'Peak XV Partners, Kalaari Capital'],
                    ['round_name' => 'Series C', 'date' => '2022-07-22', 'amount' => 120000000, 'valuation' => 900000000, 'investors' => 'Peak XV Partners, Temasek Holdings'],
                ],
                'key_metrics' => [
                    ['metric_name' => 'Diagnostic Centers', 'value' => '500+', 'unit' => 'Centers'],
                    ['metric_name' => 'Annual Tests', 'value' => '25', 'unit' => 'Million'],
                    ['metric_name' => 'Revenue', 'value' => '₹1,200', 'unit' => 'Crores'],
                    ['metric_name' => 'EBITDA Margin', 'value' => '22', 'unit' => '%'],
                ],
                'risk_disclosures' => [
                    ['risk_category' => 'Regulatory Risk', 'severity' => 'Medium', 'risk_title' => 'Healthcare Regulations', 'risk_description' => 'Compliance with stringent healthcare regulations.'],
                    ['risk_category' => 'Business Risk', 'severity' => 'Medium', 'risk_title' => 'Competition', 'risk_description' => 'Intense competition from established diagnostic chains.'],
                    ['risk_category' => 'Operational Risk', 'severity' => 'Low', 'risk_title' => 'Quality Control', 'risk_description' => 'Maintaining quality across 500+ centers.'],
                ],
            ],
            [
                'name' => 'EduTech Academy Pvt Ltd',
                'sector' => 'Education Technology',
                'face_value_per_unit' => 10.00,
                'current_market_price' => 420.00,
                'min_investment' => 7500.00,
                'expected_ipo_date' => Carbon::now()->addMonths(8),
                'description' => json_encode([
                    'short' => 'K-12 online learning platform',
                    'full' => 'EduTech Academy is India\'s fastest-growing K-12 online learning platform with personalized AI-based learning paths.',
                ]),
                'highlights' => [
                    '2 million+ registered students',
                    'Presence in 1,000+ cities',
                    'AI-powered adaptive learning technology',
                    'Partnerships with 500+ schools',
                ],
                'founders' => [
                    ['name' => 'Amit Patel', 'title' => 'Founder & CEO', 'linkedin_url' => 'https://linkedin.com/in/amitpatel'],
                    ['name' => 'Neha Singh', 'title' => 'Co-Founder & Chief Product Officer', 'linkedin_url' => 'https://linkedin.com/in/nehasingh'],
                ],
                'funding_rounds' => [
                    ['round_name' => 'Seed', 'date' => '2019-02-20', 'amount' => 2000000, 'valuation' => 10000000, 'investors' => 'Blume Ventures, India Quotient'],
                    ['round_name' => 'Series A', 'date' => '2020-07-15', 'amount' => 20000000, 'valuation' => 100000000, 'investors' => 'Lightspeed Venture Partners, Elevation Capital'],
                    ['round_name' => 'Series B', 'date' => '2022-03-30', 'amount' => 80000000, 'valuation' => 600000000, 'investors' => 'SoftBank Vision Fund, Lightspeed Venture Partners'],
                ],
                'key_metrics' => [
                    ['metric_name' => 'Registered Students', 'value' => '2', 'unit' => 'Million'],
                    ['metric_name' => 'Paying Subscribers', 'value' => '300,000', 'unit' => 'Students'],
                    ['metric_name' => 'Revenue', 'value' => '₹450', 'unit' => 'Crores'],
                    ['metric_name' => 'Monthly Active Users', 'value' => '1.2', 'unit' => 'Million'],
                ],
                'risk_disclosures' => [
                    ['risk_category' => 'Market Risk', 'severity' => 'High', 'risk_title' => 'Intense Competition', 'risk_description' => 'Highly competitive EdTech market with several well-funded players.'],
                    ['risk_category' => 'Regulatory Risk', 'severity' => 'Medium', 'risk_title' => 'Education Regulations', 'risk_description' => 'Evolving regulations for online education platforms.'],
                    ['risk_category' => 'Business Risk', 'severity' => 'Medium', 'risk_title' => 'Customer Acquisition Cost', 'risk_description' => 'High CAC impacting profitability.'],
                ],
            ],
            [
                'name' => 'FinPay Digital Services',
                'sector' => 'Fintech',
                'face_value_per_unit' => 10.00,
                'current_market_price' => 540.00,
                'min_investment' => 5000.00,
                'expected_ipo_date' => Carbon::now()->addMonths(5),
                'description' => json_encode([
                    'short' => 'Digital payments and banking solutions',
                    'full' => 'FinPay Digital Services provides comprehensive digital payment solutions and neo-banking services to individuals and businesses.',
                ]),
                'highlights' => [
                    '50 million+ registered users',
                    'Processing ₹10,000 Cr+ monthly transactions',
                    'NBFC license and payment aggregator license',
                    'Diversified revenue streams (payments, lending, insurance)',
                ],
                'founders' => [
                    ['name' => 'Vikram Malhotra', 'title' => 'Founder & CEO', 'linkedin_url' => 'https://linkedin.com/in/vikrammalhotra'],
                    ['name' => 'Sneha Kapoor', 'title' => 'Co-Founder & CFO', 'linkedin_url' => 'https://linkedin.com/in/snehakapoor'],
                ],
                'funding_rounds' => [
                    ['round_name' => 'Series A', 'date' => '2019-04-12', 'amount' => 25000000, 'valuation' => 120000000, 'investors' => 'Ribbit Capital, DST Global'],
                    ['round_name' => 'Series B', 'date' => '2020-10-08', 'amount' => 75000000, 'valuation' => 500000000, 'investors' => 'Tencent, Ribbit Capital, PayPal Ventures'],
                    ['round_name' => 'Series C', 'date' => '2022-06-15', 'amount' => 200000000, 'valuation' => 2000000000, 'investors' => 'Tiger Global, Tencent, SoftBank Vision Fund'],
                ],
                'key_metrics' => [
                    ['metric_name' => 'Registered Users', 'value' => '50', 'unit' => 'Million'],
                    ['metric_name' => 'Monthly Transactions', 'value' => '₹10,000', 'unit' => 'Crores'],
                    ['metric_name' => 'Revenue', 'value' => '₹800', 'unit' => 'Crores'],
                    ['metric_name' => 'Loan Book', 'value' => '₹2,000', 'unit' => 'Crores'],
                ],
                'risk_disclosures' => [
                    ['risk_category' => 'Regulatory Risk', 'severity' => 'High', 'risk_title' => 'RBI Regulations', 'risk_description' => 'Stringent RBI regulations for payment aggregators and NBFCs.'],
                    ['risk_category' => 'Financial Risk', 'severity' => 'High', 'risk_title' => 'Credit Risk', 'risk_description' => 'Exposure to credit risk in lending business.'],
                    ['risk_category' => 'Technology Risk', 'severity' => 'Medium', 'risk_title' => 'Cybersecurity', 'risk_description' => 'Risk of data breaches and cyber attacks.'],
                ],
            ],
        ];

        foreach ($productsData as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'sector' => $productData['sector'],
                'face_value_per_unit' => $productData['face_value_per_unit'],
                'current_market_price' => $productData['current_market_price'],
                'min_investment' => $productData['min_investment'],
                'expected_ipo_date' => $productData['expected_ipo_date'],
                'description' => $productData['description'],
                'status' => 'active',
                'is_featured' => true,
            ]);

            // Add Highlights
            foreach ($productData['highlights'] as $index => $highlight) {
                ProductHighlight::create([
                    'product_id' => $product->id,
                    'content' => $highlight,
                    'display_order' => $index,
                ]);
            }

            // Add Founders
            foreach ($productData['founders'] as $founderData) {
                ProductFounder::create([
                    'product_id' => $product->id,
                    'name' => $founderData['name'],
                    'title' => $founderData['title'],
                    'linkedin_url' => $founderData['linkedin_url'],
                ]);
            }

            // Add Funding Rounds
            foreach ($productData['funding_rounds'] as $roundData) {
                ProductFundingRound::create([
                    'product_id' => $product->id,
                    'round_name' => $roundData['round_name'],
                    'date' => $roundData['date'],
                    'amount' => $roundData['amount'],
                    'valuation' => $roundData['valuation'],
                    'investors' => $roundData['investors'],
                ]);
            }

            // Add Key Metrics
            foreach ($productData['key_metrics'] as $metricData) {
                ProductKeyMetric::create([
                    'product_id' => $product->id,
                    'metric_name' => $metricData['metric_name'],
                    'value' => $metricData['value'],
                    'unit' => $metricData['unit'],
                ]);
            }

            // Add Risk Disclosures
            foreach ($productData['risk_disclosures'] as $index => $riskData) {
                ProductRiskDisclosure::create([
                    'product_id' => $product->id,
                    'risk_category' => $riskData['risk_category'],
                    'severity' => $riskData['severity'],
                    'risk_title' => $riskData['risk_title'],
                    'risk_description' => $riskData['risk_description'],
                    'display_order' => $index,
                ]);
            }

            // Add Price History (last 12 months)
            for ($i = 12; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $basePrice = $productData['current_market_price'];
                $variation = mt_rand(-10, 15) / 100; // -10% to +15% variation
                $price = $basePrice * (1 + $variation);

                ProductPriceHistory::create([
                    'product_id' => $product->id,
                    'price' => round($price, 2),
                    'recorded_at' => $date->format('Y-m-d'),
                ]);
            }

            $this->products[] = $product;
        }

        // Add more products (total 20) with less detail
        for ($i = 6; $i <= 20; $i++) {
            $sectors = ['Technology', 'Healthcare', 'E-commerce', 'Fintech', 'Logistics', 'Real Estate', 'Consumer Goods'];
            $sector = $sectors[array_rand($sectors)];

            $product = Product::create([
                'name' => "Company $i Pvt Ltd",
                'slug' => "company-$i-pvt-ltd",
                'sector' => $sector,
                'face_value_per_unit' => 10.00,
                'current_market_price' => mt_rand(100, 500),
                'min_investment' => mt_rand(5000, 15000),
                'expected_ipo_date' => Carbon::now()->addMonths(mt_rand(3, 18)),
                'description' => json_encode([
                    'short' => "Leading player in $sector sector",
                    'full' => "Company $i is a rapidly growing company in the $sector industry with strong fundamentals.",
                ]),
                'status' => 'active',
                'is_featured' => false,
            ]);

            // Add minimal data for these products
            ProductHighlight::create([
                'product_id' => $product->id,
                'content' => 'Strong market position in ' . $sector,
                'display_order' => 0,
            ]);

            $this->products[] = $product;
        }

        $this->command->info('   ✓ ' . count($this->products) . ' products seeded with complete details');
    }

    // ==========================================
    // 5. INVESTMENT PLANS
    // ==========================================

    private function seedPlans(): void
    {
        $this->command->info('💰 Seeding Investment Plans...');

        $plansData = [
            [
                'name' => 'Starter Plan',
                'monthly_amount' => 2500.00,
                'duration_months' => 36,
                'description' => 'Perfect for beginners starting their investment journey',
                'features' => [
                    'Monthly SIP of ₹2,500',
                    'Access to all Pre-IPO products',
                    '1.5% progressive bonus',
                    'Basic bonus features',
                    'Email support',
                ],
                'bonus_config' => [
                    'progressive_bonus_percentage' => 1.5,
                    'milestone_bonuses' => json_encode([
                        ['payment_count' => 6, 'amount' => 500],
                        ['payment_count' => 12, 'amount' => 1200],
                        ['payment_count' => 24, 'amount' => 3000],
                        ['payment_count' => 36, 'amount' => 5000],
                    ]),
                    'consistency_bonus_percentage' => 2.0,
                ],
                'is_featured' => false,
            ],
            [
                'name' => 'Growth Plan',
                'monthly_amount' => 5000.00,
                'duration_months' => 36,
                'description' => 'Ideal for serious investors looking for better returns',
                'features' => [
                    'Monthly SIP of ₹5,000',
                    'Access to all Pre-IPO products',
                    '2.0% progressive bonus',
                    'Enhanced milestone bonuses',
                    'Priority email support',
                    'Quarterly performance reports',
                ],
                'bonus_config' => [
                    'progressive_bonus_percentage' => 2.0,
                    'milestone_bonuses' => json_encode([
                        ['payment_count' => 6, 'amount' => 1200],
                        ['payment_count' => 12, 'amount' => 3000],
                        ['payment_count' => 24, 'amount' => 7500],
                        ['payment_count' => 36, 'amount' => 12000],
                    ]),
                    'consistency_bonus_percentage' => 2.5,
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Premium Plan',
                'monthly_amount' => 10000.00,
                'duration_months' => 36,
                'description' => 'For high-value investors seeking maximum benefits',
                'features' => [
                    'Monthly SIP of ₹10,000',
                    'Access to all Pre-IPO products',
                    '2.5% progressive bonus',
                    'Premium milestone bonuses',
                    'Dedicated relationship manager',
                    'Monthly performance reports',
                    'Early access to new products',
                ],
                'bonus_config' => [
                    'progressive_bonus_percentage' => 2.5,
                    'milestone_bonuses' => json_encode([
                        ['payment_count' => 6, 'amount' => 2500],
                        ['payment_count' => 12, 'amount' => 6000],
                        ['payment_count' => 24, 'amount' => 15000],
                        ['payment_count' => 36, 'amount' => 25000],
                    ]),
                    'consistency_bonus_percentage' => 3.0,
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Elite Plan',
                'monthly_amount' => 25000.00,
                'duration_months' => 36,
                'description' => 'Exclusive plan for elite investors',
                'features' => [
                    'Monthly SIP of ₹25,000',
                    'Access to all Pre-IPO products',
                    '3.0% progressive bonus',
                    'Elite milestone bonuses',
                    'Dedicated relationship manager',
                    'Weekly performance reports',
                    'Exclusive product access',
                    'Invitations to investor events',
                    'Free portfolio rebalancing',
                ],
                'bonus_config' => [
                    'progressive_bonus_percentage' => 3.0,
                    'milestone_bonuses' => json_encode([
                        ['payment_count' => 6, 'amount' => 7500],
                        ['payment_count' => 12, 'amount' => 18000],
                        ['payment_count' => 24, 'amount' => 45000],
                        ['payment_count' => 36, 'amount' => 75000],
                    ]),
                    'consistency_bonus_percentage' => 3.5,
                ],
                'is_featured' => true,
            ],
        ];

        foreach ($plansData as $planData) {
            $plan = Plan::create([
                'name' => $planData['name'],
                'slug' => Str::slug($planData['name']),
                'monthly_amount' => $planData['monthly_amount'],
                'duration_months' => $planData['duration_months'],
                'description' => $planData['description'],
                'is_active' => true,
                'is_featured' => $planData['is_featured'],
                'display_order' => 0,
            ]);

            // Add features
            foreach ($planData['features'] as $index => $feature) {
                PlanFeature::create([
                    'plan_id' => $plan->id,
                    'feature_text' => $feature,
                    'display_order' => $index,
                ]);
            }

            // Add bonus configs
            foreach ($planData['bonus_config'] as $key => $value) {
                PlanConfig::create([
                    'plan_id' => $plan->id,
                    'config_key' => $key,
                    'value' => is_string($value) ? $value : json_encode($value),
                ]);
            }

            $this->plans[] = $plan;
        }

        $this->command->info('   ✓ ' . count($this->plans) . ' investment plans seeded');
    }

    // ==========================================
    // Helper methods for legal content
    // ==========================================

    private function getTermsOfServiceContent(): string
    {
        return <<<'HTML'
<h1>Terms of Service</h1>
<p>Last Updated: January 1, 2024</p>

<h2>1. Acceptance of Terms</h2>
<p>By accessing and using PreIPO SIP platform, you accept and agree to be bound by these Terms of Service.</p>

<h2>2. Description of Service</h2>
<p>PreIPO SIP provides a platform for systematic investment in Pre-IPO companies.</p>

<h2>3. User Obligations</h2>
<p>Users must provide accurate information and comply with all applicable laws.</p>

<!-- More content here -->
HTML;
    }

    private function getPrivacyPolicyContent(): string
    {
        return <<<'HTML'
<h1>Privacy Policy</h1>
<p>Last Updated: January 1, 2024</p>

<h2>1. Information We Collect</h2>
<p>We collect personal information including name, email, phone number, and KYC documents.</p>

<h2>2. How We Use Your Information</h2>
<p>We use your information to provide our services, process payments, and comply with regulations.</p>

<!-- More content here -->
HTML;
    }

    private function getCookiePolicyContent(): string
    {
        return <<<'HTML'
<h1>Cookie Policy</h1>
<p>Last Updated: January 1, 2024</p>

<h2>1. What are Cookies</h2>
<p>Cookies are small text files stored on your device.</p>

<h2>2. How We Use Cookies</h2>
<p>We use cookies to improve user experience and analyze usage patterns.</p>

<!-- More content here -->
HTML;
    }

    private function getInvestmentDisclaimerContent(): string
    {
        return <<<'HTML'
<h1>Investment Risk Disclaimer</h1>
<p>Last Updated: January 1, 2024</p>

<h2>Important Notice</h2>
<p>Investments in Pre-IPO companies carry significant risks including loss of capital.</p>

<h2>Risk Factors</h2>
<ul>
<li>Pre-IPO investments are illiquid</li>
<li>No guarantee of IPO or positive returns</li>
<li>Company valuations may decline</li>
</ul>

<!-- More content here -->
HTML;
    }

    private function getRefundPolicyContent(): string
    {
        return <<<'HTML'
<h1>Refund and Cancellation Policy</h1>
<p>Last Updated: January 1, 2024</p>

<h2>1. Cancellation Policy</h2>
<p>You may cancel your subscription with 7 days notice.</p>

<h2>2. Refund Policy</h2>
<p>Refunds are processed within 7-10 business days.</p>

<!-- More content here -->
HTML;
    }

    // ==========================================
    // 6-20. MORE SEEDER METHODS CONTINUE...
    // ==========================================

    private function seedCMSContent(): void
    {
        $this->command->info('📝 Seeding CMS Content...');

        // Pages
        $pages = [
            ['title' => 'Home', 'slug' => 'home', 'status' => 'published'],
            ['title' => 'About Us', 'slug' => 'about-us', 'status' => 'published'],
            ['title' => 'How It Works', 'slug' => 'how-it-works', 'status' => 'published'],
            ['title' => 'Contact Us', 'slug' => 'contact-us', 'status' => 'published'],
        ];

        foreach ($pages as $pageData) {
            Page::create($pageData);
        }

        // FAQs
        $faqs = [
            ['question' => 'What is Pre-IPO investing?', 'answer' => 'Pre-IPO investing allows you to invest in companies before they go public.', 'category' => 'general'],
            ['question' => 'How do I start investing?', 'answer' => 'Sign up, complete KYC, choose a plan, and start your SIP.', 'category' => 'getting_started'],
            ['question' => 'What are the minimum investment requirements?', 'answer' => 'Minimum investment varies by plan, starting from ₹2,500/month.', 'category' => 'investment'],
            ['question' => 'Can I pause my SIP?', 'answer' => 'Yes, you can pause your SIP for up to 3 months.', 'category' => 'subscription'],
            ['question' => 'How do bonuses work?', 'answer' => 'You earn bonuses based on timely payments and milestones.', 'category' => 'bonus'],
        ];

        foreach ($faqs as $faqData) {
            Faq::create($faqData);
        }

        // Blog Posts
        $blogs = [
            [
                'title' => 'Understanding Pre-IPO Investments',
                'slug' => 'understanding-pre-ipo-investments',
                'content' => 'Pre-IPO investments offer unique opportunities...',
        //        'excerpt' => 'Learn about Pre-IPO investing',
                'author_id' => 1,
                'status' => 'published',
                'published_at' => Carbon::now(),
            ],
            [
                'title' => 'Top 5 Pre-IPO Companies to Watch in 2025',
                'slug' => 'top-5-pre-ipo-companies-2025',
                'content' => 'These companies are poised for IPO success...',
        //        'excerpt' => 'Our top picks for 2025',
                'author_id' => 1,
                'status' => 'published',
                'published_at' => Carbon::now(),
            ],
        ];

        foreach ($blogs as $blogData) {
            BlogPost::create($blogData);
        }

        $this->command->info('   ✓ CMS content seeded');
    }

    private function seedCommunicationTemplates(): void
    {
        $this->command->info('📧 Seeding Communication Templates...');

        // Email Templates
        $emailTemplates = [
            [
                'slug' => 'welcome',
                'name' => 'Welcome Email',
                'subject' => 'Welcome to PreIPO SIP!',
                'body' => '<h1>Welcome {{name}}!</h1><p>Thank you for joining PreIPO SIP.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'payment-success',
                'name' => 'Payment Success',
                'subject' => 'Payment Successful - ₹{{amount}}',
                'body' => '<p>Dear {{name}}, your payment of ₹{{amount}} was successful.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'kyc-approved',
                'name' => 'KYC Approved',
                'subject' => 'KYC Verification Successful',
                'body' => '<p>Congratulations {{name}}! Your KYC has been approved.</p>',
                'is_active' => true,
            ],
        ];

        foreach ($emailTemplates as $template) {
            EmailTemplate::updateOrCreate(['slug' => $template['slug']], $template);
        }

        // SMS Templates
        $smsTemplates = [
            [
                'slug' => 'otp',
                'name' => 'OTP Verification',
                'content' => 'Your OTP is {{otp}}. Valid for 10 minutes.',
                'is_active' => true,
            ],
            [
                'slug' => 'payment-reminder',
                'name' => 'Payment Reminder',
                'content' => 'Your SIP payment of Rs {{amount}} is due on {{date}}.',
                'is_active' => true,
            ],
        ];

        foreach ($smsTemplates as $template) {
            SmsTemplate::updateOrCreate(['slug' => $template['slug']], $template);
        }

        // KYC Rejection Templates
        $kycTemplates = [
            [
                'name' => 'Blurry Document',
                'reason' => 'Document image is blurry or unclear',
                'category' => 'document_quality',
                'is_active' => true,
            ],
            [
                'name' => 'Name Mismatch',
                'reason' => 'Name on document does not match profile',
                'category' => 'identity_mismatch',
                'is_active' => true,
            ],
        ];

        foreach ($kycTemplates as $template) {
            KycRejectionTemplate::create($template);
        }

        // Canned Responses
        $cannedResponses = [
            [
                'title' => 'Payment Issue',
                'content' => 'We understand you are facing a payment issue. Please try again or use manual payment.',
                'category' => 'payment',
                'is_active' => true,
            ],
            [
                'title' => 'KYC Pending',
                'content' => 'Your KYC verification is under review. We will update you within 24-48 hours.',
                'category' => 'kyc',
                'is_active' => true,
            ],
        ];

        foreach ($cannedResponses as $response) {
            CannedResponse::create($response);
        }

        $this->command->info('   ✓ Communication templates seeded');
    }

    private function seedAdminUsers(): void
    {
        $this->command->info('👨‍💼 Seeding Admin Users...');

        $adminsData = [
            [
                'email' => 'superadmin@preiposip.com',
                'username' => 'superadmin',
                'mobile' => '9999999999',
                'password' => Hash::make('password123'),
                'role' => 'super-admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
            ],
            [
                'email' => 'admin@preiposip.com',
                'username' => 'admin',
                'mobile' => '9999999998',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'first_name' => 'Admin',
                'last_name' => 'User',
            ],
            [
                'email' => 'kyc@preiposip.com',
                'username' => 'kyc_officer',
                'mobile' => '9999999997',
                'password' => Hash::make('password123'),
                'role' => 'kyc-officer',
                'first_name' => 'KYC',
                'last_name' => 'Officer',
            ],
            [
                'email' => 'support@preiposip.com',
                'username' => 'support_agent',
                'mobile' => '9999999996',
                'password' => Hash::make('password123'),
                'role' => 'support',
                'first_name' => 'Support',
                'last_name' => 'Agent',
            ],
            [
                'email' => 'finance@preiposip.com',
                'username' => 'finance_manager',
                'mobile' => '9999999995',
                'password' => Hash::make('password123'),
                'role' => 'finance-manager',
                'first_name' => 'Finance',
                'last_name' => 'Manager',
            ],
        ];

        foreach ($adminsData as $adminData) {
            $user = User::create([
                'username' => $adminData['username'],
                'email' => $adminData['email'],
                'mobile' => $adminData['mobile'],
                'password' => $adminData['password'],
                'referral_code' => Str::upper(Str::random(8)),
                'status' => 'active',
                'email_verified_at' => Carbon::now(),
                'mobile_verified_at' => Carbon::now(),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $adminData['first_name'],
                'last_name' => $adminData['last_name'],
            ]);

            // Create verified KYC for admin users (admins shouldn't be blocked by KYC)
            $kyc = UserKyc::create([
                'user_id' => $user->id,
                'status' => 'verified',
                'pan_number' => 'ADMIN' . str_pad($user->id, 5, '0', STR_PAD_LEFT) . 'A',
                'aadhaar_number' => str_pad(mt_rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT),
                'submitted_at' => Carbon::now(),
                'verified_at' => Carbon::now(),
            ]);

            // Create KYC documents for admin
            KycDocument::create([
                'user_kyc_id' => $kyc->id,
                'doc_type' => 'pan_card',
                'file_path' => '/storage/kyc/admin_pan_' . $user->id . '.pdf',
                'file_name' => 'admin_pan_card.pdf',
                'mime_type' => 'application/pdf',
                'status' => 'approved',
            ]);

            $user->assignRole($adminData['role']);
            $this->adminUsers[] = $user;
        }

        $this->command->info('   ✓ ' . count($this->adminUsers) . ' admin users seeded');
    }

    private function seedRegularUsers(): void
    {
        $this->command->info('👥 Seeding Regular Users...');

        // User Scenarios - Comprehensive Coverage
        $userScenarios = [
            // 1. New users (no KYC)
            ['count' => 20, 'scenario' => 'new_signup', 'kyc_status' => null],
            // 2. KYC submitted (pending)
            ['count' => 15, 'scenario' => 'kyc_pending', 'kyc_status' => 'submitted'],
            // 3. KYC rejected
            ['count' => 10, 'scenario' => 'kyc_rejected', 'kyc_status' => 'rejected'],
            // 4. KYC approved, no subscription
            ['count' => 25, 'scenario' => 'kyc_approved_no_sub', 'kyc_status' => 'verified'],
            // 5. Active subscribers (various plans)
            ['count' => 50, 'scenario' => 'active_subscriber', 'kyc_status' => 'verified'],
            // 6. Paused subscriptions
            ['count' => 10, 'scenario' => 'paused_subscriber', 'kyc_status' => 'verified'],
            // 7. Cancelled subscriptions
            ['count' => 15, 'scenario' => 'cancelled_subscriber', 'kyc_status' => 'verified'],
            // 8. High-value whales
            ['count' => 5, 'scenario' => 'whale_user', 'kyc_status' => 'verified'],
            // 9. Suspended users
            ['count' => 5, 'scenario' => 'suspended', 'kyc_status' => 'verified'],
        ];

        foreach ($userScenarios as $scenario) {
            for ($i = 0; $i < $scenario['count']; $i++) {
                $user = $this->createUserForScenario($scenario['scenario'], $scenario['kyc_status'], $i + 1);
                $this->regularUsers[] = $user;
            }
        }

        $this->command->info('   ✓ ' . count($this->regularUsers) . ' regular users seeded');
    }

    private function createUserForScenario(string $scenario, ?string $kycStatus, int $index): User
    {
        $email = $scenario . $index . '@test.com';
        $mobile = '9' . str_pad(random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        $user = User::create([
            'username' => $scenario . '_user' . $index,
            'email' => $email,
            'mobile' => $mobile,
            'password' => Hash::make('password123'),
            'referral_code' => Str::upper(Str::random(8)),
            'status' => $scenario === 'suspended' ? 'suspended' : 'active',
            'email_verified_at' => Carbon::now(),
            'mobile_verified_at' => Carbon::now(),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => 'User',
            'last_name' => $scenario . ' ' . $index,
            'dob' => Carbon::now()->subYears(mt_rand(25, 60)),
            'gender' => ['male', 'female'][mt_rand(0, 1)],
            'address_line_1' => mt_rand(1, 999) . ' Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'country' => 'India',
        ]);

        if ($kycStatus) {
            $kyc = UserKyc::create([
                'user_id' => $user->id,
                'status' => $kycStatus,
                'pan_number' => 'ABCDE' . mt_rand(1000, 9999) . 'F',
                'aadhaar_number' => str_pad(mt_rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT),
                'submitted_at' => $kycStatus === 'submitted' ? Carbon::now() : null,
                'verified_at' => $kycStatus === 'verified' ? Carbon::now() : null,
                'rejection_reason' => $kycStatus === 'rejected' ? 'Document quality issue' : null,
            ]);

            // Create KYC documents
            KycDocument::create([
                'user_kyc_id' => $kyc->id,
                'doc_type' => 'pan_card',
                'file_path' => '/storage/kyc/pan_' . $user->id . '.pdf',
                'file_name' => 'pan_card.pdf',
                'mime_type' => 'application/pdf',
                'status' => $kycStatus === 'verified' ? 'approved' : ($kycStatus === 'rejected' ? 'rejected' : 'pending'),
            ]);
        }

        $user->assignRole('user');

        return $user;
    }

    private function seedSubscriptionsAndPayments(): void
    {
        $this->command->info('💳 Seeding Subscriptions & Payments...');

        // Seed bulk purchases first (inventory)
        $this->seedBulkPurchases();

        // Only seed subscriptions for users with verified KYC
        $verifiedUsers = array_filter($this->regularUsers, function ($user) {
            return $user->kyc && $user->kyc->status === 'verified';
        });

        foreach ($verifiedUsers as $user) {
            // 70% chance of having a subscription
            if (mt_rand(1, 100) <= 70) {
                $plan = $this->plans[array_rand($this->plans)];

                $startDate = Carbon::now()->subMonths(mt_rand(1, 24));
                $endDate = $startDate->copy()->addMonths($plan->duration_months);

                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount' => $plan->monthly_amount,
                    'subscription_code' => 'SUB-' . Str::upper(Str::random(10)),
                    'status' => 'active',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'next_payment_date' => Carbon::now()->addMonths(1),
                    'consecutive_payments_count' => mt_rand(0, 12),
                ]);

                // Create payment history
                $paymentCount = mt_rand(3, 12);
                for ($i = 0; $i < $paymentCount; $i++) {
                    Payment::create([
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'amount' => $plan->monthly_amount,
                        'status' => 'paid',
                        'gateway' => 'razorpay',
                        'gateway_order_id' => 'order_' . Str::random(14),
                        'gateway_payment_id' => 'pay_' . Str::random(14),
                        'paid_at' => $startDate->copy()->addMonths($i),
                        'is_on_time' => mt_rand(0, 100) > 20, // 80% on-time
                    ]);
                }
            }
        }

        $this->command->info('   ✓ Subscriptions and payments seeded');
    }

    private function seedBulkPurchases(): void
    {
        foreach ($this->products as $product) {
            // Create 2-3 bulk purchases per product
            for ($i = 0; $i < mt_rand(2, 3); $i++) {
                $faceValue = mt_rand(1000000, 10000000);
                $discount = mt_rand(5, 15);
                $extraAllocation = mt_rand(5, 20);
                $actualCost = $faceValue * (1 - $discount / 100);
                $totalValue = $faceValue * (1 + $extraAllocation / 100);

                $bulkPurchase = BulkPurchase::create([
                    'product_id' => $product->id,
                    'admin_id' => $this->adminUsers[0]->id,
                    'face_value_purchased' => $faceValue,
                    'actual_cost_paid' => $actualCost,
                    'discount_percentage' => $discount,
                    'extra_allocation_percentage' => $extraAllocation,
                    'total_value_received' => $totalValue,
                    'value_remaining' => $totalValue,
                    'purchase_date' => Carbon::now()->subMonths(mt_rand(1, 12)),
                ]);

                $this->bulkPurchases[] = $bulkPurchase;
            }
        }
    }

    private function seedUserInvestments(): void
    {
        $this->command->info('📊 Seeding User Investments...');

        // Create investments for users with paid payments
        $paymentsWithSubs = Payment::where('status', 'paid')->with('subscription.user')->get();

        foreach ($paymentsWithSubs->take(200) as $payment) {
            if (count($this->bulkPurchases) > 0 && count($this->products) > 0) {
                $product = $this->products[array_rand($this->products)];
                $bulkPurchase = collect($this->bulkPurchases)->where('product_id', $product->id)->first();

                if ($bulkPurchase) {
                    UserInvestment::create([
                        'user_id' => $payment->user_id,
                        'product_id' => $product->id,
                        'payment_id' => $payment->id,
                        'bulk_purchase_id' => $bulkPurchase->id,
                        'units_allocated' => $payment->amount / $product->face_value_per_unit,
                        'value_allocated' => $payment->amount,
                        'source' => 'sip',
                        'status' => 'active',
                        'allocated_at' => $payment->paid_at,
                    ]);
                }
            }
        }

        $this->command->info('   ✓ User investments seeded');
    }

    private function seedWalletsAndTransactions(): void
    {
        $this->command->info('💰 Seeding Wallets & Transactions...');

        foreach ($this->regularUsers as $user) {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => mt_rand(0, 50000),
                'locked_balance' => mt_rand(0, 5000),
            ]);

            // Create some transaction history
            for ($i = 0; $i < mt_rand(5, 15); $i++) {
                $amount = mt_rand(500, 10000);
                $type = ['deposit', 'bonus', 'withdrawal'][mt_rand(0, 2)];

                Transaction::create([
                    'transaction_id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'type' => $type,
                    'status' => 'completed',
                    'amount' => $amount,
                    'balance_before' => $wallet->balance,
                    'balance_after' => $wallet->balance + $amount,
                    'description' => ucfirst($type) . ' transaction',
                    'created_at' => Carbon::now()->subDays(mt_rand(1, 90)),
                ]);
            }
        }

        $this->command->info('   ✓ Wallets and transactions seeded');
    }

    private function seedWithdrawals(): void
    {
        $this->command->info('🏦 Seeding Withdrawals...');

        $usersWithWallets = User::whereIn('id', collect($this->regularUsers)->pluck('id'))
            ->whereHas('wallet')
            ->take(30)
            ->get();

        foreach ($usersWithWallets as $user) {
            $statuses = ['pending', 'approved', 'rejected', 'completed'];

            Withdrawal::create([
                'user_id' => $user->id,
                'wallet_id' => $user->wallet->id,
                'amount' => mt_rand(5000, 50000),
                'fee' => mt_rand(100, 500),
                'net_amount' => mt_rand(4500, 49500),
                'status' => $statuses[array_rand($statuses)],
                'bank_account_number' => str_pad(mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
                'bank_ifsc' => 'SBIN0001234',
                'bank_name' => 'State Bank of India',
                'account_holder_name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                'requested_at' => Carbon::now()->subDays(mt_rand(1, 30)),
            ]);
        }

        $this->command->info('   ✓ Withdrawals seeded');
    }

    private function seedBonuses(): void
    {
        $this->command->info('🎁 Seeding Bonuses...');

        $subscriptions = Subscription::with('payments')->take(50)->get();

        foreach ($subscriptions as $subscription) {
            foreach ($subscription->payments()->where('status', 'paid')->get() as $payment) {
                // Progressive bonus
                if (mt_rand(1, 100) > 30) {
                    BonusTransaction::create([
                        'user_id' => $subscription->user_id,
                        'subscription_id' => $subscription->id,
                        'payment_id' => $payment->id,
                        'type' => 'progressive',
                        'amount' => $payment->amount * 0.02,
                        'tds_deducted' => $payment->amount * 0.02 * 0.10,
                        'description' => 'Progressive bonus for payment',
                    ]);
                }
            }

            // Milestone bonus
            if ($subscription->consecutive_payments_count >= 6) {
                BonusTransaction::create([
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                    'type' => 'milestone',
                    'amount' => 500,
                    'tds_deducted' => 50,
                    'description' => '6-month milestone bonus',
                ]);
            }
        }

        $this->command->info('   ✓ Bonuses seeded');
    }

    private function seedReferrals(): void
{
    $this->command->info('🔗 Seeding Referrals...');

    // Create referral campaign
    ReferralCampaign::create([
        'name' => 'New Year Referral Campaign 2025',
        'start_date' => Carbon::now()->subMonths(2),
        'end_date' => Carbon::now()->addMonths(4),
        'multiplier' => 1.5,
        'bonus_amount' => 500,
        'is_active' => true,
    ]);

    // Create referral chains
    $usersForReferral = collect($this->regularUsers)->take(50);

    foreach ($usersForReferral as $index => $referrer) {
        // Each user refers 0–5 people
        $referralCount = mt_rand(0, 5);

        for ($i = 0; $i < $referralCount; $i++) {

            if ($index + $i + 1 < count($this->regularUsers)) {

                $referred = $this->regularUsers[$index + $i + 1];

                // 🔥 SKIP if this user is already referred
                if (Referral::where('referred_id', $referred->id)->exists()) {
                    continue;
                }

                Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $referred->id,
                    'status' => mt_rand(0, 100) > 30 ? 'completed' : 'pending',
                    'completed_at' => mt_rand(0, 100) > 30 ? Carbon::now() : null,
                ]);
            }
        }
    }
        $this->command->info('   ✓ Referrals seeded');
    }

    private function seedLuckyDraws(): void
    {
        $this->command->info('🎰 Seeding Lucky Draws...');

        $luckyDraw = LuckyDraw::create([
            'name' => 'December 2024 Lucky Draw',
            'draw_date' => Carbon::now()->subDays(10),
            'prize_structure' => [
                ['count' => 1, 'amount' => 50000],
                ['count' => 1, 'amount' => 25000],
                ['count' => 1, 'amount' => 10000],
            ],
            'status' => 'completed',
        ]);

        // Create entries for active subscribers
        $activeSubscribers = User::whereIn('id', collect($this->regularUsers)->pluck('id'))
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active');
            })
            ->take(50)
            ->get();

        foreach ($activeSubscribers as $user) {
            LuckyDrawEntry::create([
                'user_id' => $user->id,
                'lucky_draw_id' => $luckyDraw->id,
                'payment_id' => $user->payments()->first()?->id,
                'base_entries' => mt_rand(1, 5),
                'bonus_entries' => mt_rand(0, 3),
                'is_winner' => false,
            ]);
        }

        // Set 3 winners
        $entries = $luckyDraw->entries()->inRandomOrder()->take(3)->get();
        foreach ($entries as $index => $entry) {
            $prizes = [50000, 25000, 10000];
            $entry->update([
                'is_winner' => true,
                'prize_rank' => $index + 1,
                'prize_amount' => $prizes[$index],
            ]);
        }

        $this->command->info('   ✓ Lucky draws seeded');
    }

    private function seedProfitSharing(): void
    {
        $this->command->info('💸 Seeding Profit Sharing...');

        $profitShare = ProfitShare::create([
            'period_name' => 'Q4 2024',
            'start_date' => Carbon::create(2024, 10, 1),
            'end_date' => Carbon::create(2024, 12, 31),
            'total_pool' => 5000000,
            'net_profit' => 10000000,
            'status' => 'distributed',
            'admin_id' => $this->adminUsers[0]->id,
        ]);

        // Distribute to active subscribers
        $eligibleUsers = User::whereIn('id', collect($this->regularUsers)->pluck('id'))
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active');
            })
            ->take(30)
            ->get();

        foreach ($eligibleUsers as $user) {
            UserProfitShare::create([
                'user_id' => $user->id,
                'profit_share_id' => $profitShare->id,
                'amount' => mt_rand(5000, 50000),
            ]);
        }

        $this->command->info('   ✓ Profit sharing seeded');
    }

    private function seedSupportTickets(): void
    {
        $this->command->info('🎫 Seeding Support Tickets...');

        $ticketCategories = ['payment', 'kyc', 'subscription', 'withdrawal', 'general'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];

        foreach (collect($this->regularUsers)->take(40) as $user) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'ticket_code' => 'TKT-' . strtoupper(Str::random(8)),
                'subject' => 'Need help with ' . $ticketCategories[array_rand($ticketCategories)],
                'category' => $ticketCategories[array_rand($ticketCategories)],
                'priority' => $priorities[array_rand($priorities)],
                'status' => $statuses[array_rand($statuses)],
                'assigned_to' => $this->adminUsers[array_rand($this->adminUsers)]->id,
            ]);

            // Add some messages
            for ($i = 0; $i < mt_rand(2, 5); $i++) {
                SupportMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'user_id' => $i % 2 === 0 ? $user->id : $this->adminUsers[0]->id,
                    'is_admin_reply' => $i % 2 !== 0,
                    'message' => 'This is message ' . ($i + 1) . ' regarding the ticket.',
                    'created_at' => Carbon::now()->subDays(mt_rand(1, 30)),
                ]);
            }
        }

        $this->command->info('   ✓ Support tickets seeded');
    }

    private function seedActivityLogs(): void
    {
        $this->command->info('📜 Seeding Activity Logs...');

        $actions = [
            'auth.login', 'auth.logout', 'kyc.submitted', 'kyc.approved', 'kyc.rejected',
            'payment.initiated', 'payment.completed', 'payment.failed',
            'subscription.created', 'subscription.paused', 'subscription.cancelled',
            'withdrawal.requested', 'withdrawal.approved', 'withdrawal.rejected',
        ];

        foreach ($this->regularUsers as $user) {
            for ($i = 0; $i < mt_rand(10, 30); $i++) {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => $actions[array_rand($actions)],
                    'description' => 'User performed ' . $actions[array_rand($actions)],
                    'ip_address' => '192.168.1.' . mt_rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'created_at' => Carbon::now()->subDays(mt_rand(1, 90)),
                ]);
            }
        }

        $this->command->info('   ✓ Activity logs seeded');
    }

    private function seedNotifications(): void
    {
        $this->command->info('🔔 Seeding Notifications...');

        foreach (collect($this->regularUsers)->take(50) as $user) {
            for ($i = 0; $i < mt_rand(5, 15); $i++) {
                Notification::create([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\Notifications\GeneralNotification',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'title' => 'Test Notification ' . ($i + 1),
                        'message' => 'This is a test notification',
                    ]),
                    'read_at' => mt_rand(0, 100) > 50 ? Carbon::now() : null,
                    'created_at' => Carbon::now()->subDays(mt_rand(1, 30)),
                ]);
            }
        }

        $this->command->info('   ✓ Notifications seeded');
    }

    private function printSeederSummary(): void
    {
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('📊 SEEDER SUMMARY');
        $this->command->info('========================================');
        $this->command->info('Products: ' . count($this->products));
        $this->command->info('Plans: ' . count($this->plans));
        $this->command->info('Admin Users: ' . count($this->adminUsers));
        $this->command->info('Regular Users: ' . count($this->regularUsers));
        $this->command->info('========================================');
    }
}
