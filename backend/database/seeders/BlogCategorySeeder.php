<?php
// V-CMS-ENHANCEMENT-008 | BlogCategorySeeder
// Created: 2025-12-10 | Purpose: Seed default blog categories

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogCategory;
use Illuminate\Support\Facades\DB;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        BlogCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            [
                'name' => 'News & Updates',
                'slug' => 'news',
                'description' => 'Latest news and platform updates',
                'color' => '#3B82F6', // Blue
                'icon' => 'Newspaper',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Investment Tips',
                'slug' => 'investment',
                'description' => 'Expert investment advice and strategies',
                'color' => '#10B981', // Green
                'icon' => 'TrendingUp',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Market Analysis',
                'slug' => 'market',
                'description' => 'In-depth market trends and analysis',
                'color' => '#F59E0B', // Amber
                'icon' => 'BarChart3',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'How-to Guides',
                'slug' => 'guide',
                'description' => 'Step-by-step tutorials and guides',
                'color' => '#8B5CF6', // Purple
                'icon' => 'BookOpen',
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Company Spotlights',
                'slug' => 'spotlight',
                'description' => 'Featured Pre-IPO companies',
                'color' => '#EC4899', // Pink
                'icon' => 'Sparkles',
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Success Stories',
                'slug' => 'success',
                'description' => 'Investor success stories and case studies',
                'color' => '#14B8A6', // Teal
                'icon' => 'Trophy',
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Announcements',
                'slug' => 'announcement',
                'description' => 'Official platform announcements',
                'color' => '#EF4444', // Red
                'icon' => 'Megaphone',
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Educational',
                'slug' => 'education',
                'description' => 'Learn about Pre-IPO investing',
                'color' => '#6366F1', // Indigo
                'icon' => 'GraduationCap',
                'display_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            BlogCategory::create($category);
        }

        $this->command->info('✓ Created ' . count($categories) . ' blog categories');
    }
}
