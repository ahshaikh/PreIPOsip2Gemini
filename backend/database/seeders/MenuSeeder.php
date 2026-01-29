<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menu Seeder
 *
 * Seeds navigation menus for the platform:
 * - Header Menu (public navigation)
 * - Footer Menu (legal/help links)
 * - User Dashboard Menu (authenticated users)
 * - Admin Panel Menu (admin navigation)
 *
 * NOTE:
 * This seeder is schema-aware. If the `menus.location` column
 * no longer exists, menu seeding is safely skipped.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Schema guard: legacy menus.location column no longer exists
        if (!Schema::hasColumn('menus', 'location')) {
            $this->command->warn('⚠ Skipping MenuSeeder: menus.location column does not exist.');
            return;
        }

        DB::transaction(function () {
            $this->seedHeaderMenu();
            $this->seedFooterMenu();
            $this->seedUserDashboardMenu();
            $this->seedAdminPanelMenu();
        });

        $this->command->info('✓ Menus and menu items seeded successfully');
    }

    private function seedHeaderMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['location' => 'header'],
            ['name' => 'Header Menu', 'is_active' => true]
        );

        $items = [
            ['title' => 'Home', 'url' => '/', 'order' => 1],
            ['title' => 'Companies', 'url' => '/companies', 'order' => 2],
            ['title' => 'Products', 'url' => '/products', 'order' => 3],
            ['title' => 'Plans', 'url' => '/plans', 'order' => 4],
            ['title' => 'About Us', 'url' => '/about', 'order' => 5],
            ['title' => 'Contact', 'url' => '/contact', 'order' => 6],
        ];

        $this->seedItems($menu->id, $items);
    }

    private function seedFooterMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['location' => 'footer'],
            ['name' => 'Footer Menu', 'is_active' => true]
        );

        $items = [
            ['title' => 'Privacy Policy', 'url' => '/privacy-policy', 'order' => 1],
            ['title' => 'Terms & Conditions', 'url' => '/terms', 'order' => 2],
            ['title' => 'Risk Disclosure', 'url' => '/risk-disclosure', 'order' => 3],
            ['title' => 'Refund Policy', 'url' => '/refund-policy', 'order' => 4],
            ['title' => 'Help Center', 'url' => '/help-center', 'order' => 5],
            ['title' => 'SEBI Regulations', 'url' => '/sebi-regulations', 'order' => 6],
        ];

        $this->seedItems($menu->id, $items);
    }

    private function seedUserDashboardMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['location' => 'user_sidebar'],
            ['name' => 'User Dashboard Menu', 'is_active' => true]
        );

        $items = [
            ['title' => 'Dashboard', 'url' => '/dashboard', 'order' => 1],
            ['title' => 'My Portfolio', 'url' => '/portfolio', 'order' => 2],
            ['title' => 'Subscriptions', 'url' => '/subscription', 'order' => 3],
            ['title' => 'Wallet', 'url' => '/wallet', 'order' => 4],
            ['title' => 'Transactions', 'url' => '/transactions', 'order' => 5],
            ['title' => 'KYC', 'url' => '/kyc', 'order' => 6],
            ['title' => 'Referrals', 'url' => '/referrals', 'order' => 7],
            ['title' => 'Bonuses', 'url' => '/bonuses', 'order' => 8],
            ['title' => 'Support', 'url' => '/support', 'order' => 9],
            ['title' => 'Profile', 'url' => '/profile', 'order' => 10],
        ];

        $this->seedItems($menu->id, $items);
    }

    private function seedAdminPanelMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['location' => 'admin_sidebar'],
            ['name' => 'Admin Panel Menu', 'is_active' => true]
        );

        $items = [
            ['title' => 'Dashboard', 'url' => '/admin/dashboard', 'order' => 1],
            ['title' => 'Users', 'url' => '/admin/users', 'order' => 2],
            ['title' => 'KYC Queue', 'url' => '/admin/kyc-queue', 'order' => 3],
            ['title' => 'Investments', 'url' => '/admin/investments', 'order' => 4],
            ['title' => 'Payments', 'url' => '/admin/payments', 'order' => 5],
            ['title' => 'Withdrawals', 'url' => '/admin/withdrawal-queue', 'order' => 6],
            ['title' => 'Products', 'url' => '/admin/products', 'order' => 7],
            ['title' => 'Companies', 'url' => '/admin/companies', 'order' => 8],
            ['title' => 'Plans', 'url' => '/admin/plans', 'order' => 9],
            ['title' => 'Support', 'url' => '/admin/support', 'order' => 10],
            ['title' => 'Reports', 'url' => '/admin/reports', 'order' => 11],
            ['title' => 'Settings', 'url' => '/admin/settings', 'order' => 12],
        ];

        $this->seedItems($menu->id, $items);
    }

    private function seedItems(int $menuId, array $items): void
    {
        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menuId, 'title' => $item['title']],
                ['url' => $item['url'], 'order' => $item['order']]
            );
        }
    }
}
