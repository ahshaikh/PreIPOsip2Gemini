<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sector Seeder
 *
 * Seeds industry sector taxonomy for company classification.
 * Used by companies table for sector categorization.
 */
class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $sectors = [
                ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Software, Hardware, IT Services, SaaS'],
                ['name' => 'Healthcare', 'slug' => 'healthcare', 'description' => 'Medical, Pharmaceuticals, Biotech, Health Services'],
                ['name' => 'Financial Services', 'slug' => 'financial-services', 'description' => 'Banking, FinTech, Insurance, Investment'],
                ['name' => 'E-commerce', 'slug' => 'ecommerce', 'description' => 'Online Retail, Marketplaces, D2C'],
                ['name' => 'Education', 'slug' => 'education', 'description' => 'EdTech, Online Learning, Training, Skill Development'],
                ['name' => 'Real Estate', 'slug' => 'real-estate', 'description' => 'PropTech, Real Estate Services, Construction'],
                ['name' => 'Manufacturing', 'slug' => 'manufacturing', 'description' => 'Industrial, Automotive, Consumer Goods'],
                ['name' => 'Energy', 'slug' => 'energy', 'description' => 'Renewable Energy, CleanTech, Power, Solar, Wind'],
                ['name' => 'Consumer Services', 'slug' => 'consumer-services', 'description' => 'Food & Beverage, Hospitality, Lifestyle'],
                ['name' => 'Logistics', 'slug' => 'logistics', 'description' => 'Supply Chain, Transportation, Delivery, Warehousing'],
                ['name' => 'Agriculture', 'slug' => 'agriculture', 'description' => 'AgriTech, Farming, Food Production'],
                ['name' => 'Media & Entertainment', 'slug' => 'media-entertainment', 'description' => 'Content, Gaming, Streaming, Publishing'],
                ['name' => 'Telecommunications', 'slug' => 'telecommunications', 'description' => 'Telecom, Networking, Communication Infrastructure'],
                ['name' => 'Travel & Tourism', 'slug' => 'travel-tourism', 'description' => 'Hospitality, Travel Tech, Tourism Services'],
                ['name' => 'Others', 'slug' => 'others', 'description' => 'Miscellaneous and emerging sectors'],
            ];

            foreach ($sectors as $sector) {
                Sector::updateOrCreate(
                    ['slug' => $sector['slug']],
                    $sector
                );
            }

            $this->command->info('✓ Sectors seeded: ' . count($sectors) . ' records');
        });
    }
}
