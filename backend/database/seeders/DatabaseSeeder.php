<?php
// V-DEPLOY-1730-007 (Created) | V-FINAL-1730-418 (Perms Added)

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles & Permissions must come first
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(PermissionSeeder::class); // <-- ADD THIS

        // 2. Settings seeder
        $this->call(SettingsSeeder::class);

        // 3. User seeder
        $this->call(UserSeeder::class);

        // 4. Product seeder
        $this->call(ProductSeeder::class);

        // 5. Plan seeder
        $this->call(PlanSeeder::class);
        
        // 6. Homepage Seeder
        $this->call(HomePageSeeder::class);
    }
}