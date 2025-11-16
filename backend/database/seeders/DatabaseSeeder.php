<?php
// V-DEPLOY-1730-007 (Created) | V-FINAL-1730-418 (Perms Added) | V-FINAL-1730-600 (Test Seeder)

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
        $this->call(PermissionSeeder::class); // <-- Includes all permissions
        $this->call(SettingsSeeder::class);   // <-- Includes all settings

        // 2. Core Content Seeders (Plans, Products, CMS)
        $this->call(PlanSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(HomePageSeeder::class);

        // 3. Admin User
        $this->call(UserSeeder::class); // Creates the Super Admin

        // 4. --- NEW: "Chaos Seeder" ---
        // Only run this in 'local' or 'staging' environments
        if (App::environment(['local', 'staging'])) {
            $this->call(TestDataSetSeeder::class);
        }
    }
}