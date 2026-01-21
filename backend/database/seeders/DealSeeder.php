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

        // Get products (optional - deals can exist without products)
        // NOTE: Using null product_id to avoid overlap validation conflicts with
        // CompanyDisclosureSystemSeeder's FinSecure deal (product_id=1, 6-month window)
        $product1 = null; // Standalone deals without product packaging

        DB::transaction(function () use ($nexgen, $medicare, $finsecure, $eduverse, $greenpower, $product1) {
            $deals = [
                // Deal 1: NexGen AI Solutions (Draft company - but let's create a deal anyway for testing)
                [
                    'company_id' => $nexgen->id,
                    'product_id' => null, // Standalone deal (avoids product overlap validation)
                    'title' => 'NexGen AI - Series B Investment',
                    'description' => 'Invest in enterprise AI automation leader. Serving 250+ Fortune 500 clients with 99.9% uptime SLA.',
                    'share_price' => 750.00,
                    'min_investment' => 25000.00,
                    'max_investment' => 500000.00,
                    'total_shares_available' => 40000,
                    'shares_allocated' => 8000,
                    'deal_opens_at' => now()->subDays(10),
                    'deal_closes_at' => now()->addDays(45),
                    'status' => 'active',
                    'is_featured' => true,
                    'visibility' => 'public',
                ],
                // Deal 2: MediCare Plus (Live-Limited - Tier 1 approved)
                [
                    'company_id' => $medicare->id,
                    'product_id' => null, // Standalone deal (avoids product overlap validation)
                    'title' => 'MediCare Plus - Series C Pre-IPO Round',
                    'description' => 'Last chance before IPO! AI telemedicine platform with 10,000+ doctors and 5M+ consultations completed.',
                    'share_price' => 1000.00,
                    'min_investment' => 50000.00,
                    'max_investment' => 1000000.00,
                    'total_shares_available' => 50000,
                    'shares_allocated' => 12000,
                    'deal_opens_at' => now()->subDays(7),
                    'deal_closes_at' => now()->addDays(60),
                    'status' => 'active',
                    'is_featured' => true,
                    'visibility' => 'public',
                ],
                // Deal 3: FinSecure (Live-Investable - Already has ACTIVE deal from CompanyDisclosureSystemSeeder)
                // This is a CLOSED historical deal (completed ESOP secondary sale) - won't overlap with active Series D
                [
                    'company_id' => $finsecure->id,
                    'product_id' => null, // Standalone deal (avoids product overlap validation)
                    'title' => 'FinSecure Digital Lending - Employee Stock Ownership (Closed)',
                    'description' => 'Completed ESOP secondary sale. RBI-approved NBFC with ₹1,200 Cr+ loan book and 1.8% NPA ratio.',
                    'share_price' => 4500.00,
                    'min_investment' => 100000.00,
                    'max_investment' => 2000000.00,
                    'total_shares_available' => 20000,
                    'shares_allocated' => 20000, // Fully allocated (closed deal)
                    'deal_opens_at' => now()->subMonths(6), // Historical deal - 6 months ago
                    'deal_closes_at' => now()->subMonths(4), // Closed 4 months ago
                    'status' => 'closed', // CLOSED status - won't trigger overlap validation
                    'is_featured' => false,
                    'visibility' => 'public',
                ],
                // Deal 4: EduVerse (Live-Full - All tiers approved)
                [
                    'company_id' => $eduverse->id,
                    'product_id' => null, // Standalone deal (avoids product overlap validation)
                    'title' => 'EduVerse Learning - Series E Growth Round',
                    'description' => 'India\'s largest K-12 edtech with 2.5M+ students. Partnerships with 500+ schools. Complete disclosure package.',
                    'share_price' => 1500.00,
                    'min_investment' => 75000.00,
                    'max_investment' => 1500000.00,
                    'total_shares_available' => 60000,
                    'shares_allocated' => 18000,
                    'deal_opens_at' => now()->subDays(20),
                    'deal_closes_at' => now()->addDays(75),
                    'status' => 'active',
                    'is_featured' => true,
                    'visibility' => 'public',
                ],
                // Deal 5: GreenPower (Suspended - but deal exists, just not investable)
                [
                    'company_id' => $greenpower->id,
                    'product_id' => null, // Standalone deal (avoids product overlap validation)
                    'title' => 'GreenPower Energy - Series C (Temporarily Paused)',
                    'description' => 'Renewable energy leader with 250 MW capacity. Deal temporarily paused pending compliance review.',
                    'share_price' => 800.00,
                    'min_investment' => 50000.00,
                    'max_investment' => 1000000.00,
                    'total_shares_available' => 35000,
                    'shares_allocated' => 3000,
                    'deal_opens_at' => now()->subDays(30),
                    'deal_closes_at' => now()->addDays(90),
                    'status' => 'suspended', // Suspended deal for suspended company
                    'is_featured' => false,
                    'visibility' => 'public',
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
