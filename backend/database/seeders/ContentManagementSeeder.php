<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get Pre-IPO Listing Category (idempotent)
        $preIPOCategory = DB::table('content_categories')
            ->where('slug', 'pre-ipo-listing')
            ->first();

        if (!$preIPOCategory) {
            $preIPOCategoryId = DB::table('content_categories')->insertGetId([
                'name' => 'Pre-IPO Listing',
                'slug' => 'pre-ipo-listing',
                'type' => 'menu',
                'description' => 'Pre-IPO investment opportunities and listings',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $preIPOCategoryId = $preIPOCategory->id;
        }

        // Create or get Insights Category (idempotent)
        $insightsCategory = DB::table('content_categories')
            ->where('slug', 'insights')
            ->first();

        if (!$insightsCategory) {
            $insightsCategoryId = DB::table('content_categories')->insertGetId([
                'name' => 'Insights',
                'slug' => 'insights',
                'type' => 'menu',
                'description' => 'Market insights, analysis, and educational content',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $insightsCategoryId = $insightsCategory->id;
        }

        // Pre-IPO Listing Subcategories (idempotent)
        $preIPOSubcategories = [
            ['name' => 'Live Deals', 'slug' => 'live-deals', 'sort_order' => 1],
            ['name' => 'Upcoming Deals', 'slug' => 'upcoming-deals', 'sort_order' => 2],
            ['name' => 'Companies', 'slug' => 'companies', 'sort_order' => 3],
            ['name' => 'Sectors', 'slug' => 'sectors', 'sort_order' => 4],
            ['name' => 'Compare Plans', 'slug' => 'compare-plans', 'sort_order' => 5],
        ];

        foreach ($preIPOSubcategories as $sub) {
            // Check if subcategory already exists
            $exists = DB::table('content_subcategories')
                ->where('slug', $sub['slug'])
                ->where('category_id', $preIPOCategoryId)
                ->exists();

            if (!$exists) {
                DB::table('content_subcategories')->insert([
                    'category_id' => $preIPOCategoryId,
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'description' => "Manage {$sub['name']} content",
                    'sort_order' => $sub['sort_order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Insights Subcategories (idempotent)
        $insightsSubcategories = [
            ['name' => 'Market Analysis', 'slug' => 'market-analysis', 'sort_order' => 1],
            ['name' => 'Reports', 'slug' => 'reports', 'sort_order' => 2],
            ['name' => 'News & Updates', 'slug' => 'news-updates', 'sort_order' => 3],
            ['name' => 'Tutorials', 'slug' => 'tutorials', 'sort_order' => 4],
        ];

        foreach ($insightsSubcategories as $sub) {
            // Check if subcategory already exists
            $exists = DB::table('content_subcategories')
                ->where('slug', $sub['slug'])
                ->where('category_id', $insightsCategoryId)
                ->exists();

            if (!$exists) {
                DB::table('content_subcategories')->insert([
                    'category_id' => $insightsCategoryId,
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'description' => "Manage {$sub['name']} content",
                    'sort_order' => $sub['sort_order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Seed some example sectors (idempotent)
        $sectors = [
            ['name' => 'Technology', 'slug' => 'technology', 'icon' => 'laptop', 'color' => '#3B82F6'],
            ['name' => 'Healthcare', 'slug' => 'healthcare', 'icon' => 'heart', 'color' => '#EF4444'],
            ['name' => 'Fintech', 'slug' => 'fintech', 'icon' => 'credit-card', 'color' => '#10B981'],
            ['name' => 'E-commerce', 'slug' => 'e-commerce', 'icon' => 'shopping-cart', 'color' => '#F59E0B'],
            ['name' => 'EdTech', 'slug' => 'edtech', 'icon' => 'book', 'color' => '#8B5CF6'],
            ['name' => 'Clean Energy', 'slug' => 'clean-energy', 'icon' => 'zap', 'color' => '#14B8A6'],
            ['name' => 'Real Estate', 'slug' => 'real-estate', 'icon' => 'home', 'color' => '#F97316'],
            ['name' => 'Transportation', 'slug' => 'transportation', 'icon' => 'truck', 'color' => '#6366F1'],
        ];

        foreach ($sectors as $sector) {
            // Use updateOrCreate to avoid duplicate key violations
            // Sectors may already exist from FoundationSeeder or SectorSeeder
            Sector::updateOrCreate(
                ['slug' => $sector['slug']],
                [
                    'name' => $sector['name'],
                    'description' => "Companies in the {$sector['name']} sector",
                    'icon' => $sector['icon'],
                    'color' => $sector['color'],
                    'sort_order' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
