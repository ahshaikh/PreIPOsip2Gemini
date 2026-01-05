<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanConfig;
use App\Models\PlanProduct;
use App\Models\Product;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
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
            $this->seedPlanProductMappings($plans);
            $this->seedMenus();
            $this->seedPages();
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
                'code' => 'PLAN_A',
                'name' => 'Plan A - Starter',
                'slug' => 'plan-a-starter',
                'description' => 'Entry-level SIP plan ideal for first-time investors with monthly investments starting at ₹5,000.',
                'amount' => 5000,
                'duration_months' => 12,
                'min_amount' => 5000,
                'max_amount' => 10000,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'PLAN_B',
                'name' => 'Plan B - Growth',
                'slug' => 'plan-b-growth',
                'description' => 'Mid-tier SIP plan with priority allocation and enhanced bonus rates for committed investors.',
                'amount' => 10000,
                'duration_months' => 12,
                'min_amount' => 10000,
                'max_amount' => 25000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'PLAN_C',
                'name' => 'Plan C - Premium',
                'slug' => 'plan-c-premium',
                'description' => 'Premium SIP plan with guaranteed allocation, highest bonus rates, and exclusive benefits.',
                'amount' => 25000,
                'duration_months' => 12,
                'min_amount' => 25000,
                'max_amount' => 100000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
        ];

        $plans = [];
        foreach ($plansData as $planData) {
            $plan = Plan::updateOrCreate(
                ['code' => $planData['code']],
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
            'PLAN_A' => [
                'Monthly SIP of ₹5,000',
                '0.5% Progressive Bonus',
                'Standard allocation priority',
                '12-month commitment period',
            ],
            'PLAN_B' => [
                'Monthly SIP of ₹10,000',
                '0.75% Progressive Bonus',
                'Priority allocation on oversubscribed shares',
                '12-month commitment with flexibility',
            ],
            'PLAN_C' => [
                'Monthly SIP of ₹25,000',
                '1.0% Progressive Bonus',
                'Guaranteed allocation on all deals',
                'Dedicated relationship manager',
            ],
        ];

        foreach ($plans as $plan) {
            $planFeatures = $features[$plan->code] ?? [];
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
     */
    private function seedPlanConfigs(array $plans): void
    {
        $configs = [
            'PLAN_A' => [
                ['config_key' => 'progressive_bonus_rate', 'value' => '0.5', 'type' => 'float'],
                ['config_key' => 'milestone_bonus_enabled', 'value' => 'true', 'type' => 'boolean'],
                ['config_key' => 'milestone_bonus_6_months', 'value' => '500', 'type' => 'integer'],
                ['config_key' => 'milestone_bonus_12_months', 'value' => '1000', 'type' => 'integer'],
                ['config_key' => 'allocation_priority', 'value' => 'standard', 'type' => 'string'],
            ],
            'PLAN_B' => [
                ['config_key' => 'progressive_bonus_rate', 'value' => '0.75', 'type' => 'float'],
                ['config_key' => 'milestone_bonus_enabled', 'value' => 'true', 'type' => 'boolean'],
                ['config_key' => 'milestone_bonus_6_months', 'value' => '1000', 'type' => 'integer'],
                ['config_key' => 'milestone_bonus_12_months', 'value' => '2500', 'type' => 'integer'],
                ['config_key' => 'allocation_priority', 'value' => 'priority', 'type' => 'string'],
            ],
            'PLAN_C' => [
                ['config_key' => 'progressive_bonus_rate', 'value' => '1.0', 'type' => 'float'],
                ['config_key' => 'milestone_bonus_enabled', 'value' => 'true', 'type' => 'boolean'],
                ['config_key' => 'milestone_bonus_6_months', 'value' => '2500', 'type' => 'integer'],
                ['config_key' => 'milestone_bonus_12_months', 'value' => '6000', 'type' => 'integer'],
                ['config_key' => 'allocation_priority', 'value' => 'guaranteed', 'type' => 'string'],
            ],
        ];

        foreach ($plans as $plan) {
            $planConfigs = $configs[$plan->code] ?? [];
            foreach ($planConfigs as $config) {
                PlanConfig::updateOrCreate(
                    ['plan_id' => $plan->id, 'config_key' => $config['config_key']],
                    [
                        'value' => $config['value'],
                        'type' => $config['type'],
                    ]
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
            ['name' => 'Header Menu', 'location' => 'header', 'is_active' => true],
            ['name' => 'Footer Menu', 'location' => 'footer', 'is_active' => true],
            ['name' => 'User Dashboard Menu', 'location' => 'user_sidebar', 'is_active' => true],
            ['name' => 'Admin Panel Menu', 'location' => 'admin_sidebar', 'is_active' => true],
        ];

        foreach ($menus as $menuData) {
            $menu = Menu::updateOrCreate(
                ['location' => $menuData['location']],
                $menuData
            );

            // Seed menu items based on location
            match($menuData['location']) {
                'header' => $this->seedHeaderMenuItems($menu),
                'footer' => $this->seedFooterMenuItems($menu),
                'user_sidebar' => $this->seedUserMenuItems($menu),
                'admin_sidebar' => $this->seedAdminMenuItems($menu),
                default => null,
            };
        }

        $this->command->info('  ✓ Menus and menu items seeded');
    }

    private function seedHeaderMenuItems(Menu $menu): void
    {
        $items = [
            ['title' => 'Home', 'url' => '/', 'order' => 1],
            ['title' => 'Companies', 'url' => '/companies', 'order' => 2],
            ['title' => 'Plans', 'url' => '/plans', 'order' => 3],
            ['title' => 'About Us', 'url' => '/about', 'order' => 4],
            ['title' => 'Contact', 'url' => '/contact', 'order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'title' => $item['title']],
                $item
            );
        }
    }

    private function seedFooterMenuItems(Menu $menu): void
    {
        $items = [
            ['title' => 'Privacy Policy', 'url' => '/privacy-policy', 'order' => 1],
            ['title' => 'Terms & Conditions', 'url' => '/terms', 'order' => 2],
            ['title' => 'Risk Disclosure', 'url' => '/risk-disclosure', 'order' => 3],
            ['title' => 'Refund Policy', 'url' => '/refund-policy', 'order' => 4],
            ['title' => 'Help Center', 'url' => '/help-center', 'order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'title' => $item['title']],
                $item
            );
        }
    }

    private function seedUserMenuItems(Menu $menu): void
    {
        $items = [
            ['title' => 'Dashboard', 'url' => '/dashboard', 'order' => 1],
            ['title' => 'My Investments', 'url' => '/portfolio', 'order' => 2],
            ['title' => 'Wallet', 'url' => '/wallet', 'order' => 3],
            ['title' => 'KYC', 'url' => '/kyc', 'order' => 4],
            ['title' => 'Referrals', 'url' => '/referrals', 'order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'title' => $item['title']],
                $item
            );
        }
    }

    private function seedAdminMenuItems(Menu $menu): void
    {
        $items = [
            ['title' => 'Dashboard', 'url' => '/admin/dashboard', 'order' => 1],
            ['title' => 'Users', 'url' => '/admin/users', 'order' => 2],
            ['title' => 'KYC Queue', 'url' => '/admin/kyc-queue', 'order' => 3],
            ['title' => 'Investments', 'url' => '/admin/investments', 'order' => 4],
            ['title' => 'Settings', 'url' => '/admin/settings', 'order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(
                ['menu_id' => $menu->id, 'title' => $item['title']],
                $item
            );
        }
    }

    /**
     * Seed basic pages
     */
    private function seedPages(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => '<h1>About PreIPOsip</h1><p>We are India\'s leading Pre-IPO investment platform...</p>',
                'status' => 'published',
            ],
            [
                'title' => 'How It Works',
                'slug' => 'how-it-works',
                'content' => '<h1>How It Works</h1><p>Invest in Pre-IPO companies through systematic investment plans...</p>',
                'status' => 'published',
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => '<h1>Contact Us</h1><p>Email: support@preiposip.com</p><p>Phone: +91-9876543210</p>',
                'status' => 'published',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        $this->command->info('  ✓ Pages seeded: ' . count($pages) . ' records');
    }
}
