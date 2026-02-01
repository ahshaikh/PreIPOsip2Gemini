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
        $this->call(CompanyRoleSeeder::class); // Company-specific roles
        $this->call(PermissionsSeeder::class); // <-- Includes all permissions
        $this->call(SettingsSeeder::class);   // <-- Includes all settings

        // 2. Users & Identity (Moved to ensure users exist before content)
        $this->call(UserSeeder::class);         // Creates Super Admin + 1 test user
        $this->call(EnhancedUserSeeder::class); // Creates 4 more test users + 2 company reps (NEW)

        // 3. Taxonomy, Master Data & Deals (Handled by the comprehensive system seeder)
        $this->call(SectorSeeder::class);     // Industry sectors (Prerequisite)
        $this->call(DisclosureModuleSeeder::class); // Disclosure modules (Prerequisite)
        $this->call(CompanyDisclosureSystemSeeder::class); // Creates Companies, Disclosures, Deals, etc.

        // 4. Core Content Seeders (Plans, Products, CMS)
        $this->call(PlanSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(HomePageSeeder::class);
    }
}