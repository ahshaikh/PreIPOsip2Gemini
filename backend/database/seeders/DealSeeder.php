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
        // Get verified companies
        $techCorp = Company::where('slug', 'techcorp-india')->first();
        $healthPlus = Company::where('slug', 'healthplus-solutions')->first();
        $financeHub = Company::where('slug', 'financehub-technologies')->first();
        $eduTech = Company::where('slug', 'edutech-academy')->first();
        $greenEnergy = Company::where('slug', 'greenenergy-innovations')->first();

        if (!$techCorp || !$healthPlus || !$financeHub || !$eduTech || !$greenEnergy) {
            $this->command->warn('⚠ Companies not found. Run CompanySeeder first.');
            return;
        }

        // Get products (optional - deals can exist without products)
        $product1 = Product::where('slug', 'techcorp-india-series-a')->first();
        $product2 = Product::where('slug', 'healthplus-pre-ipo')->first();
        $product3 = Product::where('slug', 'financehub-seed-round')->first();

        DB::transaction(function () use ($techCorp, $healthPlus, $financeHub, $eduTech, $greenEnergy, $product1, $product2, $product3) {
            $deals = [
                [
                    'company_id' => $techCorp->id,
                    'product_id' => $product1?->id,
                    'title' => 'TechCorp India - Series A Investment',
                    'description' => 'Invest in India\'s leading SaaS automation platform. Pre-IPO opportunity with strong growth trajectory.',
                    'share_price' => 500.00,
                    'min_investment' => 10000.00,
                    'max_investment' => 500000.00,
                    'total_shares_available' => 50000,
                    'shares_allocated' => 15000,
                    'deal_opens_at' => now()->subDays(5),
                    'deal_closes_at' => now()->addDays(60),
                    'status' => 'active',
                    'is_featured' => true,
                    'visibility' => 'public',
                ],
                [
                    'company_id' => $healthPlus->id,
                    'product_id' => $product2?->id,
                    'title' => 'HealthPlus Solutions - Pre-IPO Round',
                    'description' => 'Last chance to invest before IPO. AI-powered telemedicine with 5,000+ doctors and rapid growth.',
                    'share_price' => 750.00,
                    'min_investment' => 25000.00,
                    'max_investment' => 1000000.00,
                    'total_shares_available' => 40000,
                    'shares_allocated' => 8000,
                    'deal_opens_at' => now()->subDays(10),
                    'deal_closes_at' => now()->addDays(45),
                    'status' => 'active',
                    'is_featured' => true,
                    'visibility' => 'public',
                ],
                [
                    'company_id' => $financeHub->id,
                    'product_id' => $product3?->id,
                    'title' => 'FinanceHub Technologies - Seed Investment',
                    'description' => 'High-growth fintech with ₹500 Cr+ loan book. NBFC licensed and ready to scale.',
                    'share_price' => 300.00,
                    'min_investment' => 5000.00,
                    'max_investment' => 250000.00,
                    'total_shares_available' => 100000,
                    'shares_allocated' => 25000,
                    'deal_opens_at' => now()->subDays(3),
                    'deal_closes_at' => now()->addDays(90),
                    'status' => 'active',
                    'is_featured' => true,
                    'visibility' => 'public',
                ],
                [
                    'company_id' => $eduTech->id,
                    'product_id' => null,
                    'title' => 'EduTech Academy - Growth Round',
                    'description' => 'Invest in India\'s fastest-growing edtech platform. 500,000+ active learners and growing.',
                    'share_price' => 200.00,
                    'min_investment' => 10000.00,
                    'max_investment' => 300000.00,
                    'total_shares_available' => 75000,
                    'shares_allocated' => 20000,
                    'deal_opens_at' => now()->subDays(7),
                    'deal_closes_at' => now()->addDays(30),
                    'status' => 'active',
                    'is_featured' => false,
                    'visibility' => 'public',
                ],
                [
                    'company_id' => $greenEnergy->id,
                    'product_id' => null,
                    'title' => 'GreenEnergy Innovations - Series B',
                    'description' => 'Renewable energy leader with 100 MW+ capacity. Government-backed green energy projects.',
                    'share_price' => 1000.00,
                    'min_investment' => 50000.00,
                    'max_investment' => 2000000.00,
                    'total_shares_available' => 30000,
                    'shares_allocated' => 5000,
                    'deal_opens_at' => now()->subDays(15),
                    'deal_closes_at' => now()->addDays(75),
                    'status' => 'active',
                    'is_featured' => true,
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

            $this->command->info('✓ Deals seeded: ' . count($deals) . ' active investment opportunities');
        });
    }
}
