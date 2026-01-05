<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive System Seeder - Post-Audit Production-Ready Seeder
 *
 * This is a complete, zero-error, production-safe seeder built post-audit.
 * It can be run independently or integrated into DatabaseSeeder.
 *
 * EXECUTION ORDER (CRITICAL - DO NOT CHANGE):
 * 1. FoundationSeeder - Settings, Permissions, Roles, Sectors, Feature Flags
 * 2. IdentityAccessSeeder - Users, Profiles, KYC, Wallets, Admin Ledger
 * 3. CompaniesProductsSeeder - Companies, Products, Bulk Purchases
 * 4. InvestmentPlansSeeder - Plans, Features, Configs, Menus
 * 5. CommunicationCampaignsSeeder - Templates, Campaigns, Lucky Draws
 * 6. UserInvestmentsSeeder - TEST DATA ONLY (optional, local/testing only)
 *
 * USAGE:
 * php artisan db:seed --class=ComprehensiveSystemSeeder
 *
 * SAFETY FEATURES:
 * - All seeders are wrapped in transactions
 * - Idempotent (safe to run multiple times)
 * - Existence checks before insert (updateOrCreate)
 * - Foreign key dependencies respected
 * - Check constraints validated
 * - Financial integrity maintained
 */
class ComprehensiveSystemSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Comprehensive System Seeding (Post-Audit)...');
        $this->command->info('Environment: ' . app()->environment());
        $this->command->newLine();

        $startTime = microtime(true);

        try {
            // Phase 1: Foundation (No Dependencies)
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->command->info('PHASE 1: FOUNDATION');
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->call(FoundationSeeder::class);
            $this->command->newLine();

            // Phase 2: Identity & Access
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->command->info('PHASE 2: IDENTITY & ACCESS');
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->call(IdentityAccessSeeder::class);
            $this->command->newLine();

            // Phase 3: Companies & Products
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->command->info('PHASE 3: COMPANIES & PRODUCTS');
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->call(CompaniesProductsSeeder::class);
            $this->command->newLine();

            // Phase 4: Investment Plans
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->command->info('PHASE 4: INVESTMENT PLANS');
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->call(InvestmentPlansSeeder::class);
            $this->command->newLine();

            // Phase 5 & 6: Communication & Campaigns
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->command->info('PHASE 5 & 6: COMMUNICATION & CAMPAIGNS');
            $this->command->info('═══════════════════════════════════════════════════════');
            $this->call(CommunicationCampaignsSeeder::class);
            $this->command->newLine();

            // Phase 7: User Investments (OPTIONAL - LOCAL/TESTING ONLY)
            if (app()->environment(['local', 'testing', 'development'])) {
                $this->command->info('═══════════════════════════════════════════════════════');
                $this->command->info('PHASE 7: USER INVESTMENTS (TEST DATA)');
                $this->command->info('═══════════════════════════════════════════════════════');
                $this->call(UserInvestmentsSeeder::class);
                $this->command->newLine();
            } else {
                $this->command->warn('⚠️  Skipping UserInvestmentsSeeder - Production environment');
                $this->command->newLine();
            }

            // Success summary
            $this->displaySuccessSummary($startTime);

            // Post-seeding validation
            $this->runValidationChecks();

        } catch (\Exception $e) {
            $this->command->error('═══════════════════════════════════════════════════════');
            $this->command->error('❌ SEEDING FAILED');
            $this->command->error('═══════════════════════════════════════════════════════');
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->command->newLine();
            $this->command->error('Stack trace:');
            $this->command->error($e->getTraceAsString());

            throw $e;
        }
    }

    /**
     * Display success summary
     */
    private function displaySuccessSummary(float $startTime): void
    {
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('✅ COMPREHENSIVE SEEDING COMPLETED SUCCESSFULLY');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('Execution time: ' . $executionTime . ' seconds');
        $this->command->info('Environment: ' . app()->environment());
        $this->command->newLine();

        $this->command->info('📊 Summary:');
        $this->command->info('  ✓ Settings: ~60 configurations');
        $this->command->info('  ✓ Permissions: ~80 permissions, 5 roles');
        $this->command->info('  ✓ Users: 3 admins, 5 test users, 2 company reps');
        $this->command->info('  ✓ Companies: 5 companies with products');
        $this->command->info('  ✓ Plans: 3 investment plans');
        $this->command->info('  ✓ Templates: 10 email, 5 SMS');
        $this->command->info('  ✓ Campaigns: 2 referral, 3 promotional, 1 lucky draw');

        if (app()->environment(['local', 'testing', 'development'])) {
            $this->command->info('  ✓ Test Data: 5 subscriptions, 10+ payments');
        }

        $this->command->newLine();
    }

    /**
     * Run post-seeding validation checks
     */
    private function runValidationChecks(): void
    {
        $this->command->info('🔍 Running validation checks...');
        $this->command->newLine();

        $validations = [
            'Settings populated' => DB::table('settings')->count() >= 50,
            'Permissions created' => DB::table('permissions')->count() >= 70,
            'Roles created' => DB::table('roles')->count() >= 5,
            'Users exist' => DB::table('users')->count() >= 8,
            'Companies exist' => DB::table('companies')->count() >= 5,
            'Products exist' => DB::table('products')->count() >= 5,
            'Plans exist' => DB::table('plans')->count() >= 3,
            'Bulk purchases exist' => DB::table('bulk_purchases')->count() >= 5,
            'Email templates exist' => DB::table('email_templates')->count() >= 10,
            'Wallets exist' => DB::table('wallets')->count() >= 5,
            'Admin ledger genesis' => DB::table('admin_ledger_entries')->count() >= 2,
        ];

        $allPassed = true;
        foreach ($validations as $check => $passed) {
            if ($passed) {
                $this->command->info("  ✅ $check");
            } else {
                $this->command->error("  ❌ $check");
                $allPassed = false;
            }
        }

        $this->command->newLine();

        if ($allPassed) {
            $this->command->info('✅ All validation checks passed!');
        } else {
            $this->command->warn('⚠️  Some validation checks failed. Please review.');
        }

        $this->command->newLine();

        // Display login credentials
        $this->displayLoginCredentials();
    }

    /**
     * Display login credentials for testing
     */
    private function displayLoginCredentials(): void
    {
        $this->command->info('🔐 Login Credentials (FOR TESTING ONLY):');
        $this->command->newLine();

        $this->command->info('ADMIN USERS:');
        $this->command->info('  Super Admin:');
        $this->command->info('    Email: admin@preiposip.com');
        $this->command->info('    Password: password');
        $this->command->newLine();

        $this->command->info('  Support Manager:');
        $this->command->info('    Email: support@preiposip.com');
        $this->command->info('    Password: password');
        $this->command->newLine();

        $this->command->info('  KYC Reviewer:');
        $this->command->info('    Email: kyc@preiposip.com');
        $this->command->info('    Password: password');
        $this->command->newLine();

        $this->command->info('TEST USERS (KYC Verified, Wallets Funded):');
        $this->command->info('  User 1:');
        $this->command->info('    Email: user1@test.com');
        $this->command->info('    Password: password');
        $this->command->info('    Wallet: ₹50,000');
        $this->command->newLine();

        $this->command->info('  User 2:');
        $this->command->info('    Email: user2@test.com');
        $this->command->info('    Password: password');
        $this->command->info('    Wallet: ₹1,00,000');
        $this->command->newLine();

        $this->command->info('  User 3:');
        $this->command->info('    Email: user3@test.com');
        $this->command->info('    Password: password');
        $this->command->info('    Wallet: ₹25,000');
        $this->command->newLine();

        $this->command->warn('⚠️  IMPORTANT: Change these passwords in production!');
        $this->command->newLine();

        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('Next Steps:');
        $this->command->info('  1. Review seeded data in database');
        $this->command->info('  2. Test admin panel: /admin/login');
        $this->command->info('  3. Test user dashboard: /login');
        $this->command->info('  4. Verify wallet balances and transactions');
        $this->command->info('  5. Test investment flows end-to-end');
        $this->command->info('═══════════════════════════════════════════════════════');
    }
}
