<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deal Seeder
 *
 * Seeds active investment deals for verified companies.
 * Deals represent investment opportunities visible to investors on the deals page.
 */
class DealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get verified companies from CompanyDisclosureSystemSeeder
        // Note: FinSecure already has a deal created by CompanyDisclosureSystemSeeder, so we skip it
        $nexgen = Company::where('slug', 'nexgen-ai-solutions')->first();
        $medicare = Company::where('slug', 'medicare-plus-healthtech')->first();
        $finsecure = Company::where('slug', 'finsecure-digital-lending')->first(); // Already has deal
        $eduverse = Company::where('slug', 'eduverse-learning-platform')->first();
        $greenpower = Company::where('slug', 'greenpower-energy-solutions')->first();

        if (!$medicare && !$eduverse) {
            $this->command->warn('⚠ Companies not found. Run CompanyDisclosureSystemSeeder first.');
            return;
        }

        // Create unique products for each deal to satisfy NOT NULL constraint
        // and avoid overlap validation (validation only blocks same product_id overlaps)
        DB::transaction(function () use ($nexgen, $medicare, $finsecure, $eduverse, $greenpower) {
            // Create products for each deal
            $productNexgen = Product::firstOrCreate(['name' => 'NexGen Series B Offering'], [
                'slug' => 'nexgen-series-b-offering',
                'sector' => 'Technology',
                'description' => 'AI automation investment package',
            ]);

            $productMedicare = Product::firstOrCreate(['name' => 'MediCare Series C Offering'], [
                'slug' => 'medicare-series-c-offering',
                'sector' => 'Healthcare',
                'description' => 'Telemedicine platform investment package',
            ]);

            $productFinsecure = Product::firstOrCreate(['name' => 'FinSecure ESOP Package'], [
                'slug' => 'finsecure-esop-package',
                'sector' => 'Financial Services',
                'description' => 'Employee stock ownership secondary sale',
            ]);

            $productEduverse = Product::firstOrCreate(['name' => 'EduVerse Series E Offering'], [
                'slug' => 'eduverse-series-e-offering',
                'sector' => 'Education',
                'description' => 'Edtech platform investment package',
            ]);

            $productGreenpower = Product::firstOrCreate(['name' => 'GreenPower Series C Offering'], [
                'slug' => 'greenpower-series-c-offering',
                'sector' => 'Clean Energy',
                'description' => 'Renewable energy investment package',
            ]);

            $deals = [
                // Deal 1: NexGen AI Solutions (Draft company - but let's create a deal anyway for testing)
                [
                    'company_id' => $nexgen->id,
                    'product_id' => $productNexgen->id, // Unique product - no overlap
                    'title' => 'NexGen AI - Series B Investment',
                    'slug' => 'nexgen-series-b',
                    'description' => 'Invest in enterprise AI automation leader. Serving 250+ Fortune 500 clients with 99.9% uptime SLA.',
                    'sector' => 'Technology',
                    'deal_type' => 'live',
                    'share_price' => 750.00,
                    'min_investment' => 25000.00,
                    'max_investment' => 500000.00,
                    'deal_opens_at' => now()->subDays(10),
                    'deal_closes_at' => now()->addDays(45),
                    'days_remaining' => 45,
                    'status' => 'active',
                    'is_featured' => true,
                    'sort_order' => 1,
                ],
                // Deal 2: MediCare Plus (Live-Limited - Tier 1 approved)
                [
                    'company_id' => $medicare->id,
                    'product_id' => $productMedicare->id, // Unique product - no overlap
                    'title' => 'MediCare Plus - Series C Pre-IPO Round',
                    'slug' => 'medicare-series-c',
                    'description' => 'Last chance before IPO! AI telemedicine platform with 10,000+ doctors and 5M+ consultations completed.',
                    'sector' => 'Healthcare',
                    'deal_type' => 'live',
                    'share_price' => 1000.00,
                    'min_investment' => 50000.00,
                    'max_investment' => 1000000.00,
                    'deal_opens_at' => now()->subDays(7),
                    'deal_closes_at' => now()->addDays(60),
                    'days_remaining' => 60,
                    'status' => 'active',
                    'is_featured' => true,
                    'sort_order' => 2,
                ],
                // Deal 3: FinSecure (Live-Investable - Already has ACTIVE deal from CompanyDisclosureSystemSeeder)
                // This is a CLOSED historical deal (completed ESOP secondary sale) - won't overlap with active Series D
                [
                    'company_id' => $finsecure->id,
                    'product_id' => $productFinsecure->id, // Different product from Series D - no overlap
                    'title' => 'FinSecure Digital Lending - Employee Stock Ownership (Closed)',
                    'slug' => 'finsecure-esop-closed',
                    'description' => 'Completed ESOP secondary sale. RBI-approved NBFC with ₹1,200 Cr+ loan book and 1.8% NPA ratio.',
                    'sector' => 'Financial Services',
                    'deal_type' => 'closed',
                    'share_price' => 4500.00,
                    'min_investment' => 100000.00,
                    'max_investment' => 2000000.00,
                    'deal_opens_at' => now()->subMonths(6), // Historical deal - 6 months ago
                    'deal_closes_at' => now()->subMonths(4), // Closed 4 months ago
                    'days_remaining' => 0,
                    'status' => 'closed', // CLOSED status - won't trigger overlap validation
                    'is_featured' => false,
                    'sort_order' => 99,
                ],
                // Deal 4: EduVerse (Live-Full - All tiers approved)
                [
                    'company_id' => $eduverse->id,
                    'product_id' => $productEduverse->id, // Unique product - no overlap
                    'title' => 'EduVerse Learning - Series E Growth Round',
                    'slug' => 'eduverse-series-e',
                    'description' => 'India\'s largest K-12 edtech with 2.5M+ students. Partnerships with 500+ schools. Complete disclosure package.',
                    'sector' => 'Education',
                    'deal_type' => 'live',
                    'share_price' => 1500.00,
                    'min_investment' => 75000.00,
                    'max_investment' => 1500000.00,
                    'deal_opens_at' => now()->subDays(20),
                    'deal_closes_at' => now()->addDays(75),
                    'days_remaining' => 75,
                    'status' => 'active',
                    'is_featured' => true,
                    'sort_order' => 3,
                ],
                // Deal 5: GreenPower (Paused - but deal exists, just not investable)
                [
                    'company_id' => $greenpower->id,
                    'product_id' => $productGreenpower->id, // Unique product - no overlap
                    'title' => 'GreenPower Energy - Series C (Temporarily Paused)',
                    'slug' => 'greenpower-series-c',
                    'description' => 'Renewable energy leader with 250 MW capacity. Deal temporarily paused pending compliance review.',
                    'sector' => 'Clean Energy',
                    'deal_type' => 'live',
                    'share_price' => 800.00,
                    'min_investment' => 50000.00,
                    'max_investment' => 1000000.00,
                    'deal_opens_at' => now()->subDays(30),
                    'deal_closes_at' => now()->addDays(90),
                    'days_remaining' => 90,
                    'status' => 'paused', // Paused deal for suspended company
                    'is_featured' => false,
                    'sort_order' => 4,
                ],
            ];

            foreach ($deals as $dealData) {
                Deal::updateOrCreate(
                    [
                        'company_id' => $dealData['company_id'],
                        'title' => $dealData['title']
                    ],
                    $dealData
                );
            }

            $this->command->info('✓ Deals seeded: ' . count($deals) . ' investment opportunities for CompanyDisclosureSystemSeeder companies');
        });
    }
}
