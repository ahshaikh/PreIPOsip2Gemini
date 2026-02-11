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
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Companies & Products Seeder - FULL ARCHITECTURAL VERSION
 * * This seeder manages the complex relationship between:
 * 1. Companies (Respecting Immutability Guards)
 * 2. Products (Respecting State Machine Lifecycle)
 * 3. Inventory (Bulk Purchases with Manual Entry Provenance)
 * 4. Listings (Exact Table Schema Alignment)
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
     * Seed companies with full sector references and detail
     */
    private function seedCompanies(): array
    {
        $techSector = Sector::where('slug', 'technology')->first();
        $healthSector = Sector::where('slug', 'healthcare')->first();
        $financeSector = Sector::where('slug', 'financial-services')->first();
        $eduSector = Sector::where('slug', 'education')->first();
        $energySector = Sector::where('slug', 'energy')->first();

        $companiesData = [
            [
                'name' => 'TechCorp India',
                'slug' => 'techcorp-india',
                'sector' => 'Technology',
                'sector_id' => $techSector?->id,
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
                'sector_id' => $healthSector?->id,
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
                'sector_id' => $financeSector?->id,
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
                'sector_id' => $eduSector?->id,
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
                'sector_id' => $energySector?->id,
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
        foreach ($companiesData as $data) {
            // CRITICAL: Check existence first to avoid triggering the 'Immutable' guard on protected fields
            // Your Company.php model throws a RuntimeException if we try to update an approved company.
            $company = Company::where('slug', $data['slug'])->first();
            
            if (!$company) {
                $company = Company::create($data);
                $this->command->info("  ✓ Created new company: {$data['name']}");
            } else {
                $this->command->comment("  - Company exists, skipping update to respect protected fields: {$data['name']}");
            }
            $createdCompanies[] = $company;
        }

        return $createdCompanies;
    }

    /**
     * Seed products and related metadata
     */
    private function seedProducts(array $companies): void
    {
        foreach ($companies as $company) {
            $product = Product::where('company_id', $company->id)->first();
            
            if ($product) {
                $this->command->comment("  - Product for {$company->name} already exists. Skipping.");
                continue;
            }

            // 1. Create Product (Must start as 'draft' to pass boot logic in Product.php)
            $product = Product::create([
                'company_id' => $company->id,
                'name' => "{$company->name} Equity Shares",
                'slug' => $company->slug . '-shares-' . Str::random(4),
                'sector' => $company->sector,
                'face_value_per_unit' => 10,
                'current_market_price' => 500,
                'min_investment' => 50000,
                'expected_ipo_date' => now()->addMonths(18),
                'status' => 'draft',
                'eligibility_mode' => 'all_plans',
                'is_featured' => $company->is_featured,
                'description' => [
                    'overview' => "Institutional grade pre-IPO equity shares of {$company->name}.",
                    'investment_thesis' => "High-growth exposure to the {$company->sector} sector with clear IPO roadmap."
                ],
            ]);

            // 2. Add Compliance Inventory
            $bulk = $this->createBulkPurchase($product, $company);

            // 3. Populate full metadata suites (Expanded Line Count)
            $this->seedMetadata($product, $company);

            // 4. Sequential Transitions (draft -> submitted -> approved)
            // This satisfies the HasWorkflowActions trait requirements.
            $product->update(['status' => 'submitted']);
            $product->update(['status' => 'approved']);

            // 5. Activation (Bypass model-level active guard via raw DB update)
            DB::table('products')->where('id', $product->id)->update(['status' => 'active']);

            // 6. Final Listing (Aligned with your exact migration schema)
            $this->createCompanyShareListing($company, $product, $bulk);
        }
    }

    /**
     * Seed full metadata suite for products
     */
    private function seedMetadata(Product $product, Company $company): void
    {
        // Highlights
        ProductHighlight::create(['product_id' => $product->id, 'content' => '250+ Enterprise Clients', 'display_order' => 1]);
        ProductHighlight::create(['product_id' => $product->id, 'content' => '40% YoY Revenue Growth', 'display_order' => 2]);
        ProductHighlight::create(['product_id' => $product->id, 'content' => 'AI-Powered Automation Platform', 'display_order' => 3]);
        ProductHighlight::create(['product_id' => $product->id, 'content' => 'Tier-1 VC Backed', 'display_order' => 4]);

        // Founders
        ProductFounder::create([
            'product_id' => $product->id,
            'name' => 'Vikram Seth',
            'title' => 'CEO & Co-Founder',
            'linkedin_url' => 'https://linkedin.com/in/vikram-seth',
            'display_order' => 1
        ]);
        ProductFounder::create([
            'product_id' => $product->id,
            'name' => 'Ananya Rao',
            'title' => 'CTO & Co-Founder',
            'linkedin_url' => 'https://linkedin.com/in/ananya-rao',
            'display_order' => 2
        ]);

        // Funding Rounds
        ProductFundingRound::create([
            'product_id' => $product->id,
            'round_name' => 'Seed Round',
            'amount' => 50000000,
            'valuation' => 200000000,
            'date' => now()->subYears(2),
            'investors' => 'Angel Network India'
        ]);
        ProductFundingRound::create([
            'product_id' => $product->id,
            'round_name' => 'Series A',
            'amount' => 250000000,
            'valuation' => 1000000000,
            'date' => now()->subYear(),
            'investors' => 'Global Venture Capital Partners'
        ]);

        // Key Metrics
        ProductKeyMetric::create(['product_id' => $product->id, 'metric_name' => 'Annual Revenue', 'value' => 120, 'unit' => 'Cr']);
        ProductKeyMetric::create(['product_id' => $product->id, 'metric_name' => 'Monthly Active Users', 'value' => 500, 'unit' => 'K']);
        ProductKeyMetric::create(['product_id' => $product->id, 'metric_name' => 'EBITDA Margin', 'value' => 22, 'unit' => '%']);

        // Risk Disclosures
        ProductRiskDisclosure::create([
            'product_id' => $product->id,
            'risk_category' => 'market',
            'risk_title' => 'Market Volatility',
            'risk_description' => 'The value of unlisted shares can fluctuate based on market conditions.',
            'severity' => 'medium',
            'display_order' => 1
        ]);
        ProductRiskDisclosure::create([
            'product_id' => $product->id,
            'risk_category' => 'liquidity',
            'risk_title' => 'Liquidity Risk',
            'risk_description' => 'Pre-IPO shares are illiquid assets until an IPO or secondary sale.',
            'severity' => 'high',
            'display_order' => 2
        ]);

        // Price History
        ProductPriceHistory::create(['product_id' => $product->id, 'price' => 450, 'recorded_at' => now()->subMonths(6)]);
        ProductPriceHistory::create(['product_id' => $product->id, 'price' => 500, 'recorded_at' => now()]);
    }

    /**
     * Create bulk purchase (inventory) with full audit reason
     */
    private function createBulkPurchase(Product $product, Company $company): BulkPurchase
    {
        $faceValueTotal = 10000 * $product->face_value_per_unit;

        return BulkPurchase::create([
            'product_id' => $product->id,
            'company_id' => $company->id,
            'admin_id' => $this->adminUser->id,
            'face_value_purchased' => $faceValueTotal,
            'actual_cost_paid' => $faceValueTotal * 0.95,
            'discount_percentage' => 5.0,
            'extra_allocation_percentage' => 0.0,
            'seller_name' => "Primary Market Issuer: {$company->name}",
            'purchase_date' => now()->subMonth(),
            'source_type' => 'manual_entry',
            'source_documentation' => json_encode(['type' => 'seed_injection', 'verified' => true]),
            'approved_by_admin_id' => $this->adminUser->id,
            'manual_entry_reason' => "SEED DATA INJECTION: Initialized inventory for {$company->name} IPO inventory initialization. Verified via simulated manual ledger entry for primary allocation of 10,000 units.",
            'verified_at' => now(),
            'total_value_received' => $faceValueTotal,
            'value_remaining' => $faceValueTotal,
        ]);
    }

    /**
     * Create company share listing matching exact table schema
     */
    private function createCompanyShareListing(Company $company, Product $product, BulkPurchase $bulk): void
    {
        DB::table('company_share_listings')->insert([
            'company_id'                => $company->id,
            'submitted_by'              => $this->adminUser->id,
            'listing_title'             => "Primary Offering: {$company->name} Equity",
            'description'               => "Institutional allocation for {$company->name}. Secured via primary market channel.",
            'total_shares_offered'      => 10000,
            'face_value_per_share'      => $product->face_value_per_unit,
            'asking_price_per_share'    => $product->current_market_price,
            'total_value'               => 10000 * $product->current_market_price,
            'minimum_purchase_value'    => $product->min_investment,
            'current_company_valuation' => 1000000000,
            'valuation_currency'        => 'INR',
            'percentage_of_company'     => 0.01,
            'terms_and_conditions'      => 'Standard pre-IPO lock-in period of 6 months applies.',
            'status'                    => 'approved',
            'bulk_purchase_id'          => $bulk->id,
            'approved_quantity'         => 10000,
            'approved_price'            => $product->current_market_price,
            'reviewed_by'               => $this->adminUser->id,
            'reviewed_at'               => now(),
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
    }
}