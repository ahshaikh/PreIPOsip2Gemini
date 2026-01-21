<?php
// V-DEPLOY-1730-007 (Created) | V-FINAL-1730-418 (Perms Added) | V-FINAL-1730-600 (Test Seeder) | V-SEEDER-ENHANCED | V-PHASE2-CAMPAIGNS

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
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(PermissionsSeeder::class); // <-- Includes all permissions
        $this->call(SettingsSeeder::class);   // <-- Includes all settings

        // 2. Taxonomy & Master Data (NEW - Post-Audit)
        $this->call(SectorSeeder::class);     // Industry sectors
        $this->call(CompanySeeder::class);    // Sample Pre-IPO companies
        $this->call(DealSeeder::class);       // Active investment deals for companies

        // 3. Core Content Seeders (Plans, Products, CMS)
        $this->call(PlanSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(HomePageSeeder::class);

        // 4. Communication Templates
        $this->call(EmailTemplateSeeder::class);
        $this->call(SmsTemplateSeeder::class);

        // 5. Support & Operations Data
        $this->call(KycRejectionTemplateSeeder::class);
        $this->call(CannedResponseSeeder::class);
        $this->call(FaqSeeder::class);

        // 6. Feature Flags
        $this->call(FeatureFlagSeeder::class);

        // 7. Legal Documents
        $this->call(LegalAgreementSeeder::class);

        // 8. Users & Identity
        $this->call(UserSeeder::class);         // Creates Super Admin + 1 test user
        $this->call(EnhancedUserSeeder::class); // Creates 4 more test users + 2 company reps (NEW)

        // 9. Navigation (NEW - Post-Audit)
        $this->call(MenuSeeder::class);         // Header, Footer, User, Admin menus

        // 10. CMS And Bonus
        $this->call(CmsAndBonusSeeder::class);
        $this->call(ContentManagementSeeder::class);

        // 11. Campaigns & Engagement (NEW - Post-Audit Phase 2)
        $this->call(ReferralCampaignSeeder::class);      // Referral campaigns
        $this->call(PromotionalCampaignSeeder::class);   // Promotional campaigns
        $this->call(LuckyDrawSeeder::class);             // Lucky draw configurations

        // 12. --- "Chaos Seeder" ---
        // Only run this in 'local' or 'staging' environments
        if (App::environment(['local', 'staging'])) {
            $this->call(TestDataSetSeeder::class);
        }
    }
}