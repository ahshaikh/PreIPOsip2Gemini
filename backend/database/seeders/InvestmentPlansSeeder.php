<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanConfig;
// use App\Models\PlanProduct; // Model doesn't exist - plan eligibility handled via products.eligibility_mode
use App\Models\Product;
use App\Models\Menu;
use App\Models\MenuItem;
// use App\Models\Page; // Pages table doesn't exist
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Investment Plans Seeder - Phase 4
 *
 * Seeds investment framework:
 * - Plans (A, B, C with different tiers)
 * - Plan Features
 * - Plan Configurations (bonus rates, etc.)
 * - Plan-Product Eligibility Mappings
 * - Menus & Menu Items (navigation)
 * - Basic Pages (static content)
 *
 * CRITICAL:
 * - Requires CompaniesProductsSeeder (products)
 * - All business logic (bonus rates) stored in DB
 * - Plan-product mapping enables flexible allocation
 */
class InvestmentPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $plans = $this->seedPlans();
            $this->seedPlanFeatures($plans);
            $this->seedPlanConfigs($plans);
            // Note: PlanProduct model doesn't exist - plan eligibility handled via products.eligibility_mode field
            // $this->seedPlanProductMappings($plans);
            $this->seedMenus();
            // Note: Pages table doesn't exist - pages managed separately
            // $this->seedPages();
        });

        $this->command->info('✅ Investment Plans data seeded successfully');
    }

    /**
     * Seed investment plans
     */
    private function seedPlans(): array
    {
        $plansData = [
            [
                'name' => 'Plan A - Starter',
                'slug' => 'plan-a-starter',
                'description' => 'Entry-level SIP plan ideal for first-time investors with monthly investments starting at ₹5,000.',
                'monthly_amount' => 5000,
                'duration_months' => 12,
                'is_active' => true,
                'is_featured' => false,
                'display_order' => 1,
            ],
            [
                'name' => 'Plan B - Growth',
                'slug' => 'plan-b-growth',
                'description' => 'Mid-tier SIP plan with priority allocation and enhanced bonus rates for committed investors.',
                'monthly_amount' => 10000,
                'duration_months' => 12,
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Plan C - Premium',
                'slug' => 'plan-c-premium',
                'description' => 'Premium SIP plan with guaranteed allocation, highest bonus rates, and exclusive benefits.',
                'monthly_amount' => 25000,
                'duration_months' => 12,
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 3,
            ],
        ];

        $plans = [];
        foreach ($plansData as $planData) {
            $plan = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
            $plans[] = $plan;
        }

        $this->command->info('  ✓ Plans seeded: ' . count($plans) . ' records');

        return $plans;
    }

    /**
     * Seed plan features
     */
    private function seedPlanFeatures(array $plans): void
    {
        $features = [
            'plan-a-starter' => [
                'Monthly SIP of ₹5,000',
                '0.5% Progressive Bonus',
                'Standard allocation priority',
                '12-month commitment period',
            ],
            'plan-b-growth' => [
                'Monthly SIP of ₹10,000',
                '0.75% Progressive Bonus',
                'Priority allocation on oversubscribed shares',
                '12-month commitment with flexibility',
            ],
            'plan-c-premium' => [
                'Monthly SIP of ₹25,000',
                '1.0% Progressive Bonus',
                'Guaranteed allocation on all deals',
                'Dedicated relationship manager',
            ],
        ];

        foreach ($plans as $plan) {
            $planFeatures = $features[$plan->slug] ?? [];
            foreach ($planFeatures as $index => $featureText) {
                PlanFeature::updateOrCreate(
                    ['plan_id' => $plan->id, 'feature_text' => $featureText],
                    ['display_order' => $index + 1]
                );
            }
        }

        $this->command->info('  ✓ Plan features seeded');
    }

    /**
     * Seed plan configurations
     *
     * CRITICAL: All business logic values stored in DB
     * NOTE: plan_configs.value is JSON type, so we store as JSON
     */
    private function seedPlanConfigs(array $plans): void
    {
        $configs = [
            'plan-a-starter' => [
                ['config_key' => 'progressive_bonus_rate', 'value' => json_encode(0.5)],
                ['config_key' => 'milestone_bonus_enabled', 'value' => json_encode(true)],
                ['config_key' => 'milestone_bonus_6_months', 'value' => json_encode(500)],
                ['config_key' => 'milestone_bonus_12_months', 'value' => json_encode(1000)],
                ['config_key' => 'allocation_priority', 'value' => json_encode('standard')],
            ],
            'plan-b-growth' => [
                ['config_key' => 'progressive_bonus_rate', 'value' => json_encode(0.75)],
                ['config_key' => 'milestone_bonus_enabled', 'value' => json_encode(true)],
                ['config_key' => 'milestone_bonus_6_months', 'value' => json_encode(1000)],
                ['config_key' => 'milestone_bonus_12_months', 'value' => json_encode(2500)],
                ['config_key' => 'allocation_priority', 'value' => json_encode('priority')],
            ],
            'plan-c-premium' => [
                ['config_key' => 'progressive_bonus_rate', 'value' => json_encode(1.0)],
                ['config_key' => 'milestone_bonus_enabled', 'value' => json_encode(true)],
                ['config_key' => 'milestone_bonus_6_months', 'value' => json_encode(2500)],
                ['config_key' => 'milestone_bonus_12_months', 'value' => json_encode(6000)],
                ['config_key' => 'allocation_priority', 'value' => json_encode('guaranteed')],
            ],
        ];

        foreach ($plans as $plan) {
            $planConfigs = $configs[$plan->slug] ?? [];
            foreach ($planConfigs as $config) {
                PlanConfig::updateOrCreate(
                    ['plan_id' => $plan->id, 'config_key' => $config['config_key']],
                    ['value' => $config['value']]
                );
            }
        }

        $this->command->info('  ✓ Plan configurations seeded');
    }

    /**
     * Seed plan-product eligibility mappings
     *
     * All plans are eligible for all products (flexible allocation)
     */
    private function seedPlanProductMappings(array $plans): void
    {
        $products = Product::all();
        $count = 0;

        foreach ($plans as $plan) {
            foreach ($products as $product) {
                PlanProduct::updateOrCreate([
                    'plan_id' => $plan->id,
                    'product_id' => $product->id,
                ], [
                    'is_active' => true,
                ]);
                $count++;
            }
        }

        $this->command->info('  ✓ Plan-product mappings seeded: ' . $count . ' records');
    }

    /**
     * Seed navigation menus
     */
    private function seedMenus(): void
    {
        $menus = [
            ['name' => 'Header Menu', 'slug' => 'header'],
            ['name' => 'Footer Menu', 'slug' => 'footer'],
            ['name' => 'User Dashboard Menu', 'slug' => 'user-sidebar'],
            ['name' => 'Admin Panel Menu', 'slug' => 'admin-sidebar'],
        ];

        foreach ($menus as $menuData) {
            $menu = Menu::updateOrCreate(
                ['slug' => $menuData['slug']],
                $menuData
            );

            // Seed menu items based on slug
            match($menuData['slug']) {
                'header' => $this->seedHeaderMenuItems($menu),
                'footer' => $this->seedFooterMenuItems($menu),
                'user-sidebar' => $this->seedUserMenuItems($menu),
                'admin-sidebar' => $this->seedAdminMenuItems($menu),
                default => null,
            };
        }

        $this->command->info('  ✓ Menus and menu items seeded');
    }

    private function seedHeaderMenuItems(Menu $menu): void
    {
        $items = [
            ['label' => 'Home', 'url' => '/', 'display_order' => 1],
            ['label' => 'Companies', 'url' => '/companies', 'display_order' => 2],
            ['label' => 'Plans', 'url' => '/plans', 'display_order' => 3],
            ['label' => 'About Us', 'url' => '/about', 'display_order' => 4],
            ['label' => 'Contact', 'url' => '/contact', 'display_order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'label' => $item['label']],
                $item
            );
        }
    }

    private function seedFooterMenuItems(Menu $menu): void
    {
        $items = [
            ['label' => 'Privacy Policy', 'url' => '/privacy-policy', 'display_order' => 1],
            ['label' => 'Terms & Conditions', 'url' => '/terms', 'display_order' => 2],
            ['label' => 'Risk Disclosure', 'url' => '/risk-disclosure', 'display_order' => 3],
            ['label' => 'Refund Policy', 'url' => '/refund-policy', 'display_order' => 4],
            ['label' => 'Help Center', 'url' => '/help-center', 'display_order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'label' => $item['label']],
                $item
            );
        }
    }

    private function seedUserMenuItems(Menu $menu): void
    {
        $items = [
            ['label' => 'Dashboard', 'url' => '/dashboard', 'display_order' => 1],
            ['label' => 'My Investments', 'url' => '/portfolio', 'display_order' => 2],
            ['label' => 'Wallet', 'url' => '/wallet', 'display_order' => 3],
            ['label' => 'KYC', 'url' => '/kyc', 'display_order' => 4],
            ['label' => 'Referrals', 'url' => '/referrals', 'display_order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'label' => $item['label']],
                $item
            );
        }
    }

    private function seedAdminMenuItems(Menu $menu): void
    {
        $items = [
            ['label' => 'Dashboard', 'url' => '/admin/dashboard', 'display_order' => 1],
            ['label' => 'Users', 'url' => '/admin/users', 'display_order' => 2],
            ['label' => 'KYC Queue', 'url' => '/admin/kyc-queue', 'display_order' => 3],
            ['label' => 'Investments', 'url' => '/admin/investments', 'display_order' => 4],
            ['label' => 'Settings', 'url' => '/admin/settings', 'display_order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'label' => $item['label']],
                $item
            );
        }
    }

    /**
     * Seed basic pages
     *
     * DISABLED: Pages table doesn't exist in current schema
     */
    // private function seedPages(): void
    // {
    //     $pages = [
    //         [
    //             'title' => 'About Us',
    //             'slug' => 'about',
    //             'content' => '<h1>About PreIPOsip</h1><p>We are India\'s leading Pre-IPO investment platform...</p>',
    //             'status' => 'published',
    //         ],
    //         [
    //             'title' => 'How It Works',
    //             'slug' => 'how-it-works',
    //             'content' => '<h1>How It Works</h1><p>Invest in Pre-IPO companies through systematic investment plans...</p>',
    //             'status' => 'published',
    //         ],
    //         [
    //             'title' => 'Contact Us',
    //             'slug' => 'contact',
    //             'content' => '<h1>Contact Us</h1><p>Email: support@preiposip.com</p><p>Phone: +91-9876543210</p>',
    //             'status' => 'published',
    //         ],
    //     ];
    //
    //     foreach ($pages as $pageData) {
    //         Page::updateOrCreate(
    //             ['slug' => $pageData['slug']],
    //             $pageData
    //         );
    //     }
    //
    //     $this->command->info('  ✓ Pages seeded: ' . count($pages) . ' records');
    // }
}
