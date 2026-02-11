<?php
// V-DEPLOY-1730-007 (Created) | V-FINAL-1730-418 (Perms Added) | V-FINAL-1730-600 (Test Seeder) | V-SEEDER-ENHANCED | V-PHASE2-CAMPAIGNS | 11-02-26

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Core System Seeders (Roles, Settings)
        $this->call(RolesAndPermissionsSeeder::class);  // Platform Specific Roles & Permissions
        $this->call(CompanyRoleSeeder::class);          // Company-specific roles
        $this->call(PermissionsSeeder::class);          // Includes all permissions
        $this->call(SettingsSeeder::class);             // Includes all settings
		$this->call(FoundationSeeder::class);           // Basic settings of Permission and roles, sectors, features activate, KYC Rejection Templates, Legal Agreement
		$this->call(CompanyUserSeeder::class);          // Company Users

        // 2. Users & Identity (Moved to ensure users exist before content)
		$this->call(IdentityAccessSeeder::class);       // Users (Admin, Test Users, Company Reps), User Profiles, User KYC records, KYC Documents, Wallets (with genesis balances), Admin Ledger (genesis entries for initial liability), User Settings
        $this->call(UserSeeder::class);                 // Creates Super Admin + 1 test user
        $this->call(EnhancedUserSeeder::class);         // Creates 4 more test users + 2 company reps (NEW)
		$this->call(TestDataSetSeeder::class);          // Test Data Set
		// $this->call(UserInvestmentsSeeder::class);      // User Investments

        // 3. Taxonomy, Master Data & Deals (Handled by the comprehensive system seeder)
        $this->call(SectorSeeder::class);               // Industry sectors (Prerequisite)
        $this->call(DisclosureModuleSeeder::class);     // Disclosure modules (Prerequisite)
		$this->call(DisclosureModuleCategorySeeder::class);      // Categorize disclosure modules based on their code/name
        $this->call(CompanyDisclosureSystemSeeder::class);       // Creates Companies, Disclosures, Deals, etc.
		$this->call(AssignMissingCompanyUserRolesSeeder::class); // Assign founder roles to all CompanyUsers without roles
		$this->call(CompaniesProductsSeeder::class);    // Companies Products
		$this->call(DealSeeder::class);                 // Deals represent investment opportunities visible to investors on the deals page.

        // 4. Core Content Seeders (Plans, Products, CMS)
        $this->call(InvestmentPlansSeeder::class);      // All Plans
        $this->call(ProductSeeder::class);              // All Products
        $this->call(HomePageSeeder::class);             // Landing page
		$this->call(ContentManagementSeeder::class);    // IPO Catgories, Insights Category, IPO Sub Categories, Insights sub categories, Sectors
		$this->call(FeatureFlagSeeder::class);          // Platform Features Flagging
		$this->call(KycRejectionTemplateSeeder::class); // Kyc Rejection Template
		$this->call(LegalAgreementSeeder::class);       // Legal Agreements

		// 5. Communications, Marketing & Campaigns
		$this->call(BlogCategorySeeder::class);         // Blogs Categories
		$this->call(PromotionalCampaignSeeder::class);  // Sample Campaigns
		$this->call(CannedResponseSeeder::class);       // Response module for help center
		$this->call(EmailTemplateSeeder::class);        // Email Template Seeder 
		$this->call(FaqSeeder::class);                  // Frequently Asked Questions
		$this->call(KbSeeder::class);                   // Knowledge Base Seeder 
		$this->call(LuckyDrawSeeder ::class);           // Lucky Draw
		$this->call(PromotionalMaterialsSeeder::class); // Promotional Materials for Subscribers
		$this->call(SmsTemplateSeeder::class);          // SMS Template
    }
}
