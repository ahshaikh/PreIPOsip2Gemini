<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CmsAndBonusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 1. LUCKY DRAWS
        // ==========================================
        $luckyDrawId = DB::table('lucky_draws')->insertGetId([
            'name' => 'Diwali Mega Bumper',
            'draw_date' => Carbon::now()->addDays(15),
            'prize_structure' => json_encode([
                '1st' => 'iPhone 15 Pro',
                '2nd' => 'iPad Air',
                '3rd' => 'Amazon Voucher (5k)'
            ]),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lucky_draws')->insert([
            'name' => 'Monsoon Bonanza (Ended)',
            'draw_date' => Carbon::now()->subMonth(),
            'prize_structure' => json_encode(['1st' => 'Gold Coin 5g']),
            'status' => 'completed',
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);

        // ==========================================
        // 2. PROFIT SHARING POOLS
        // ==========================================
        DB::table('profit_shares')->insert([
            [
                'period_name' => 'Q3 2025 (Jul-Sep)',
                'start_date' => '2025-07-01',
                'end_date' => '2025-09-30',
                'total_pool' => 5000000.00, // 50 Lakhs
                'net_profit' => 1500000.00, // 15 Lakhs
                'status' => 'distributed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'period_name' => 'Q4 2025 (Oct-Dec)',
                'start_date' => '2025-10-01',
                'end_date' => '2025-12-31',
                'total_pool' => 7500000.00,
                'net_profit' => 0, // Not yet calculated
                'status' => 'active', // Currently accruing
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // ==========================================
        // 3. CMS PAGES (About, Terms, Privacy)
        // ==========================================
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h1>Welcome to PreIPO SIP</h1><p>We democratize access to unlisted shares...</p>',
                'status' => 'published',
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'content' => '<h1>Terms of Service</h1><p>By using this platform, you agree to...</p>',
                'status' => 'published',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => '<h1>Privacy Policy</h1><p>Your data is safe with us...</p>',
                'status' => 'published',
            ]
        ];

        foreach ($pages as $page) {
            DB::table('pages')->updateOrInsert(
                ['slug' => $page['slug']],
                array_merge($page, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // ==========================================
        // 4. MENUS (Header & Footer)
        // ==========================================
        $headerMenuId = DB::table('menus')->insertGetId([
            'name' => 'Header Main',
            'slug' => 'header-main',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $menuItems = [
            ['label' => 'Home', 'url' => '/', 'display_order' => 1],
            ['label' => 'Products', 'url' => '/products', 'display_order' => 2],
            ['label' => 'About', 'url' => '/about-us', 'display_order' => 3],
            ['label' => 'Contact', 'url' => '/contact', 'display_order' => 4],
        ];

        foreach ($menuItems as $item) {
            DB::table('menu_items')->insert(array_merge($item, [
                'menu_id' => $headerMenuId,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        // ==========================================
        // 5. BANNERS (Home Slider)
        // ==========================================
        DB::table('banners')->insert([
            [
                'title' => 'Invest in SpaceX',
                'content' => 'Exclusive allocation available now!',
                'link_url' => '/products/spacex',
                'type' => 'slider',
                'display_order' => 1,
                'is_active' => true,
                'start_at' => now(),
                'end_at' => now()->addMonths(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Refer & Earn',
                'content' => 'Get 500 INR for every friend you invite.',
                'link_url' => '/user/referrals',
                'type' => 'popup',
                'display_order' => 0,
                'is_active' => true,
                'start_at' => now(),
                'end_at' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $this->command->info('CMS, Lucky Draws, Profit Sharing, and Banners seeded successfully!');
    }
}