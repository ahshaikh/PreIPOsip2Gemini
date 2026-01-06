<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductHighlight;
use App\Models\ProductFounder;
use App\Models\ProductFundingRound;
use App\Models\ProductKeyMetric;
use App\Models\ProductRiskDisclosure;
use App\Models\ProductPriceHistory;
use App\Models\BulkPurchase;
use App\Models\CompanyShareListing;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Companies & Products Seeder - Phase 3
 *
 * Seeds company and product catalog:
 * - Companies (with sector references)
 * - Products (Pre-IPO shares)
 * - Product details (highlights, founders, metrics, risks)
 * - Bulk Purchases (initial inventory)
 * - Company Share Listings
 *
 * CRITICAL:
 * - Requires FoundationSeeder (sectors)
 * - Requires IdentityAccessSeeder (admin users)
 * - Creates inventory that will be allocated to users
 * - Ensures inventory conservation constraints
 */
class CompaniesProductsSeeder extends Seeder
{
    private $adminUser;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user for approvals
        $this->adminUser = User::where('email', 'admin@preiposip.com')->first();

        if (!$this->adminUser) {
            $this->command->error('❌ Admin user not found. Run IdentityAccessSeeder first.');
            return;
        }

        DB::transaction(function () {
            $companies = $this->seedCompanies();
            $this->seedProducts($companies);
        });

        $this->command->info('✅ Companies & Products data seeded successfully');
    }

    /**
     * Seed companies
     */
    private function seedCompanies(): array
    {
        $techSector = Sector::where('slug', 'technology')->first();
        $healthSector = Sector::where('slug', 'healthcare')->first();
        $financeSector = Sector::where('slug', 'financial-services')->first();
        $eduSector = Sector::where('slug', 'education')->first();
        $energySector = Sector::where('slug', 'energy')->first();

        $companies = [
            [
                'name' => 'TechCorp India',
                'slug' => 'techcorp-india',
                'sector' => 'Technology',
                'sector_id' => $techSector->id,
                'description' => 'Leading SaaS platform for enterprise automation and AI-driven workflows.',
                'website' => 'https://techcorpindia.example.com',
                'founded_year' => '2018',
                'headquarters' => 'Bangalore, Karnataka',
                'employees_count' => 250,
                'status' => 'active',
                'is_featured' => true,
            ],
            [
                'name' => 'HealthPlus Solutions',
                'slug' => 'healthplus-solutions',
                'sector' => 'Healthcare',
                'sector_id' => $healthSector->id,
                'description' => 'AI-powered telemedicine platform connecting patients with healthcare providers.',
                'website' => 'https://healthplus.example.com',
                'founded_year' => '2019',
                'headquarters' => 'Mumbai, Maharashtra',
                'employees_count' => 180,
                'status' => 'active',
                'is_featured' => true,
            ],
            [
                'name' => 'FinanceHub Technologies',
                'slug' => 'financehub-technologies',
                'sector' => 'Financial Services',
                'sector_id' => $financeSector->id,
                'description' => 'Digital lending platform providing instant personal and business loans.',
                'website' => 'https://financehub.example.com',
                'founded_year' => '2020',
                'headquarters' => 'Gurugram, Haryana',
                'employees_count' => 320,
                'status' => 'active',
                'is_featured' => true,
            ],
            [
                'name' => 'EduTech Academy',
                'slug' => 'edutech-academy',
                'sector' => 'Education',
                'sector_id' => $eduSector->id,
                'description' => 'Online learning platform offering skill development courses and certifications.',
                'website' => 'https://edutech.example.com',
                'founded_year' => '2017',
                'headquarters' => 'Pune, Maharashtra',
                'employees_count' => 150,
                'status' => 'active',
                'is_featured' => false,
            ],
            [
                'name' => 'GreenEnergy Innovations',
                'slug' => 'greenenergy-innovations',
                'sector' => 'Energy',
                'sector_id' => $energySector->id,
                'description' => 'Renewable energy solutions provider focused on solar and wind power.',
                'website' => 'https://greenenergy.example.com',
                'founded_year' => '2016',
                'headquarters' => 'Chennai, Tamil Nadu',
                'employees_count' => 200,
                'status' => 'active',
                'is_featured' => true,
            ],
        ];

        $createdCompanies = [];
        foreach ($companies as $companyData) {
            $company = Company::updateOrCreate(
                ['slug' => $companyData['slug']],
                $companyData
            );
            $createdCompanies[] = $company;
        }

        $this->command->info('  ✓ Companies seeded: ' . count($companies) . ' records');

        return $createdCompanies;
    }

    /**
     * Seed products and related data
     */
    private function seedProducts(array $companies): void
    {
        foreach ($companies as $company) {
            $product = $this->createProduct($company);
            $this->createProductHighlights($product);
            $this->createProductFounders($product, $company);
            $this->createProductFundingRounds($product, $company);
            $this->createProductKeyMetrics($product);
            $this->createProductRiskDisclosures($product);
            $this->createProductPriceHistory($product);
            $this->createBulkPurchase($product, $company);
            $this->createCompanyShareListing($company, $product);
        }

        $this->command->info('  ✓ Products and related data seeded');
    }

    /**
     * Create product for a company
     */
    private function createProduct(Company $company): Product
    {
        $productNames = [
            'TechCorp India' => 'TechCorp Equity Shares',
            'HealthPlus Solutions' => 'HealthPlus Equity Shares',
            'FinanceHub Technologies' => 'FinanceHub Equity Shares',
            'EduTech Academy' => 'EduTech Equity Shares',
            'GreenEnergy Innovations' => 'GreenEnergy Equity Shares',
        ];

        $pricePerShare = [
            'TechCorp India' => 500,
            'HealthPlus Solutions' => 800,
            'FinanceHub Technologies' => 600,
            'EduTech Academy' => 1000,
            'GreenEnergy Innovations' => 750,
        ];

        return Product::updateOrCreate(
            ['slug' => $company->slug . '-shares'],
            [
                'company_id' => $company->id,
                'name' => $productNames[$company->name],
                'slug' => $company->slug . '-shares',
                'description' => json_encode([
                    'overview' => 'Pre-IPO equity shares of ' . $company->name,
                    'investment_thesis' => 'Strong growth potential in ' . $company->sector . ' sector',
                ]),
                'category' => 'equity',
                'price_per_share' => $pricePerShare[$company->name],
                'min_investment' => 5000,
                'max_investment' => 1000000,
                'is_active' => true,
                'is_featured' => $company->is_featured,
                'listing_date' => now()->addMonths(rand(6, 18)),
            ]
        );
    }

    /**
     * Create product highlights
     */
    private function createProductHighlights(Product $product): void
    {
        $highlightsByCompany = [
            'TechCorp Equity Shares' => [
                '250+ Enterprise Clients',
                '40% YoY Revenue Growth',
                'AI-Powered Automation Platform',
            ],
            'HealthPlus Equity Shares' => [
                '1M+ Registered Users',
                'Network of 5,000+ Doctors',
                'ISO 27001 Certified Platform',
            ],
            'FinanceHub Equity Shares' => [
                '₹500 Cr+ Loan Book',
                'NBFC License Approved',
                '15% Average Monthly Growth',
            ],
            'EduTech Equity Shares' => [
                '500,000+ Active Learners',
                '200+ Industry-Certified Courses',
                'Partnerships with Top Corporations',
            ],
            'GreenEnergy Equity Shares' => [
                '100 MW+ Renewable Capacity',
                'Government-Approved Projects',
                'Carbon Credit Certified',
            ],
        ];

        $highlights = $highlightsByCompany[$product->name] ?? ['Key Feature 1', 'Key Feature 2', 'Key Feature 3'];

        foreach ($highlights as $index => $text) {
            ProductHighlight::updateOrCreate(
                ['product_id' => $product->id, 'highlight_text' => $text],
                ['display_order' => $index + 1]
            );
        }
    }

    /**
     * Create product founders
     */
    private function createProductFounders(Product $product, Company $company): void
    {
        $founderData = [
            ['name' => 'Rajesh Kumar', 'role' => 'CEO & Co-Founder', 'bio' => 'Former VP at Tech Giant, IIT Delhi alumni', 'display_order' => 1],
            ['name' => 'Priya Sharma', 'role' => 'CTO & Co-Founder', 'bio' => 'Ex-Engineering Lead, Stanford MS', 'display_order' => 2],
        ];

        foreach ($founderData as $founder) {
            ProductFounder::updateOrCreate(
                ['product_id' => $product->id, 'name' => $founder['name']],
                $founder
            );
        }
    }

    /**
     * Create funding rounds
     */
    private function createProductFundingRounds(Product $product, Company $company): void
    {
        $fundingRounds = [
            ['round_type' => 'Seed', 'amount_raised' => 50000000, 'valuation' => 200000000, 'funded_at' => now()->subYears(2)],
            ['round_type' => 'Series A', 'amount_raised' => 150000000, 'valuation' => 600000000, 'funded_at' => now()->subYear()],
        ];

        foreach ($fundingRounds as $round) {
            ProductFundingRound::updateOrCreate(
                ['product_id' => $product->id, 'round_type' => $round['round_type']],
                $round
            );
        }
    }

    /**
     * Create key metrics
     */
    private function createProductKeyMetrics(Product $product): void
    {
        $metrics = [
            ['metric_name' => 'Annual Revenue', 'metric_value' => '₹' . rand(10, 100) . ' Cr', 'display_order' => 1],
            ['metric_name' => 'Monthly Active Users', 'metric_value' => rand(10000, 1000000), 'display_order' => 2],
            ['metric_name' => 'YoY Growth', 'metric_value' => rand(20, 60) . '%', 'display_order' => 3],
        ];

        foreach ($metrics as $metric) {
            ProductKeyMetric::updateOrCreate(
                ['product_id' => $product->id, 'metric_name' => $metric['metric_name']],
                $metric
            );
        }
    }

    /**
     * Create risk disclosures
     */
    private function createProductRiskDisclosures(Product $product): void
    {
        $risks = [
            ['risk_type' => 'market', 'description' => 'Market volatility and regulatory changes may impact valuation.', 'severity' => 'medium'],
            ['risk_type' => 'liquidity', 'description' => 'Limited liquidity until IPO or secondary sale opportunities arise.', 'severity' => 'high'],
        ];

        foreach ($risks as $risk) {
            ProductRiskDisclosure::updateOrCreate(
                ['product_id' => $product->id, 'risk_type' => $risk['risk_type']],
                $risk
            );
        }
    }

    /**
     * Create price history
     */
    private function createProductPriceHistory(Product $product): void
    {
        $prices = [
            ['price_per_share' => $product->price_per_share * 0.8, 'effective_date' => now()->subMonths(6), 'reason' => 'Initial offering price'],
            ['price_per_share' => $product->price_per_share, 'effective_date' => now()->subMonths(1), 'reason' => 'Post-funding valuation adjustment'],
        ];

        foreach ($prices as $price) {
            ProductPriceHistory::updateOrCreate(
                ['product_id' => $product->id, 'effective_date' => $price['effective_date']],
                $price
            );
        }
    }

    /**
     * Create bulk purchase (inventory)
     *
     * Inventory allocation:
     * - TechCorp: 10,000 shares @ ₹500 = ₹50,00,000 (5,000 allocated, 5,000 reserved)
     * - HealthPlus: 5,000 shares @ ₹800 = ₹40,00,000 (2,000 allocated, 3,000 reserved)
     * - FinanceHub: 8,000 shares @ ₹600 = ₹48,00,000 (4,000 allocated, 4,000 reserved)
     * - EduTech: 3,000 shares @ ₹1000 = ₹30,00,000 (1,000 allocated, 2,000 reserved)
     * - GreenEnergy: 6,000 shares @ ₹750 = ₹45,00,000 (3,000 allocated, 3,000 reserved)
     */
    private function createBulkPurchase(Product $product, Company $company): void
    {
        $inventoryConfig = [
            'TechCorp India' => ['quantity' => 10000, 'allocated' => 5000],
            'HealthPlus Solutions' => ['quantity' => 5000, 'allocated' => 2000],
            'FinanceHub Technologies' => ['quantity' => 8000, 'allocated' => 4000],
            'EduTech Academy' => ['quantity' => 3000, 'allocated' => 1000],
            'GreenEnergy Innovations' => ['quantity' => 6000, 'allocated' => 3000],
        ];

        $config = $inventoryConfig[$company->name];
        $totalQuantity = $config['quantity'];
        $quantityAllocated = $config['allocated'];
        $quantityReserved = $totalQuantity - $quantityAllocated;

        $totalCost = $totalQuantity * $product->price_per_share;
        $discountPercentage = 5.0; // 5% bulk discount
        $finalCost = $totalCost * (1 - $discountPercentage / 100);

        BulkPurchase::updateOrCreate(
            ['product_id' => $product->id, 'company_id' => $company->id],
            [
                'admin_id' => $this->adminUser->id,
                'total_quantity' => $totalQuantity,
                'quantity_allocated' => $quantityAllocated,
                'quantity_reserved' => $quantityReserved,
                'price_per_unit' => $product->price_per_share,
                'total_cost' => $finalCost,
                'discount_percentage' => $discountPercentage,
                'purchase_date' => now()->subMonths(rand(1, 3)),
                'source_type' => 'direct_company_purchase',
                'status' => 'active',
                'approved_by_admin_id' => $this->adminUser->id,
                'approved_at' => now()->subMonths(rand(1, 3)),
            ]
        );
    }

    /**
     * Create company share listing
     */
    private function createCompanyShareListing(Company $company, Product $product): void
    {
        CompanyShareListing::updateOrCreate(
            ['company_id' => $company->id],
            [
                'share_type' => 'equity',
                'total_shares_available' => BulkPurchase::where('product_id', $product->id)->sum('total_quantity'),
                'shares_allocated' => BulkPurchase::where('product_id', $product->id)->sum('quantity_allocated'),
                'shares_reserved' => BulkPurchase::where('product_id', $product->id)->sum('quantity_reserved'),
                'price_per_share' => $product->price_per_share,
                'listing_status' => 'approved',
                'approved_at' => now()->subMonths(rand(1, 3)),
                'approved_by_admin_id' => $this->adminUser->id,
            ]
        );
    }
}
