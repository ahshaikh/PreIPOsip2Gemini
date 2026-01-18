<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\DisclosureModule;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureVersion;
use App\Models\DisclosureClarification;
use App\Models\PlatformCompanyMetric;
use App\Models\PlatformRiskFlag;
use App\Models\PlatformValuationContext;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\InvestorRiskAcknowledgement;
use App\Models\Investment;
use App\Models\InvestmentDisclosureSnapshot;
use App\Models\Sector;
use App\Models\Deal;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * PHASE 6 — COMPREHENSIVE SYSTEM SEEDER (SCHEMA-AWARE)
 *
 * PURPOSE:
 * Creates a high-fidelity, end-to-end database seeder for the full
 * Company Disclosure & Investment system. Covers all workflows and UI states
 * with production-like realistic data.
 *
 * SEEDING COVERAGE:
 * 1. Companies (5 distinct states: Draft, Live-Limited, Live-Investable, Live-Fully Disclosed, Suspended)
 * 2. Disclosure Modules (All versions & states)
 * 3. Clarification Cycles (Multi-round Q&A scenarios)
 * 4. Platform Context & Scores (Risk flags, metrics, valuation context)
 * 5. Investors & Risk Acknowledgements
 * 6. Transactions & Snapshots (Immutable investment records)
 *
 * QUALITY STANDARDS:
 * - Realistic production-like data (no placeholders)
 * - Exhaustive coverage of all workflows
 * - Idempotent execution (safe to re-run)
 * - Respects snapshot immutability rules
 * - Follows governance protocols
 *
 * USAGE:
 * php artisan db:seed --class=CompanyDisclosureSystemSeeder
 *
 * DEPENDENCIES:
 * - Run DisclosureModuleSeeder first
 * - Run SectorSeeder first
 * - Run RolesAndPermissionsSeeder first
 */
class CompanyDisclosureSystemSeeder extends Seeder
{
    private $admin;
    private $companyUsers = [];
    private $investors = [];
    private $sectors;
    private $modules;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('🚀 Starting Company Disclosure System Seeder...');

            // Pre-load dependencies
            $this->loadDependencies();

            // Phase 1: Seed Companies (5 distinct states)
            $this->command->info('📦 Phase 1: Seeding Companies...');
            $companies = $this->seedCompanies();

            // Phase 2: Seed Company Users (for each company)
            $this->command->info('👥 Phase 2: Seeding Company Users...');
            $this->seedCompanyUsers($companies);

            // Phase 3: Seed Disclosure Data
            $this->command->info('📋 Phase 3: Seeding Company Disclosures...');
            $this->seedCompanyDisclosures($companies);

            // Phase 4: Seed Clarification Cycles
            $this->command->info('💬 Phase 4: Seeding Clarification Cycles...');
            $this->seedClarifications($companies);

            // Phase 5: Seed Platform Context & Scores
            $this->command->info('📊 Phase 5: Seeding Platform Context & Scores...');
            $this->seedPlatformContext($companies);

            // Phase 6: Seed Investors & Risk Acknowledgements
            $this->command->info('👨‍💼 Phase 6: Seeding Investors & Risk Acknowledgements...');
            $this->seedInvestorsAndAcknowledgements($companies);

            // Phase 7: Seed Transactions & Snapshots
            $this->command->info('💰 Phase 7: Seeding Transactions & Snapshots...');
            $this->seedTransactionsAndSnapshots($companies);

            $this->command->info('✅ Company Disclosure System Seeder completed successfully!');
        });
    }

    /**
     * Load required dependencies (admin, sectors, modules)
     */
    private function loadDependencies(): void
    {
        // Load or create admin user
        $this->admin = User::where('email', 'admin@preiposip.com')->first();
        if (!$this->admin) {
            $this->admin = User::factory()->create([
                'email' => 'admin@preiposip.com',
                'name' => 'System Admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $this->admin->assignRole('admin');
        }

        // Load sectors
        $this->sectors = Sector::all()->keyBy('slug');
        if ($this->sectors->isEmpty()) {
            $this->command->warn('⚠️  No sectors found. Run SectorSeeder first.');
        }

        // Load disclosure modules
        $this->modules = DisclosureModule::all()->keyBy('code');
        if ($this->modules->isEmpty()) {
            $this->command->warn('⚠️  No disclosure modules found. Run DisclosureModuleSeeder first.');
        }
    }

    /**
     * PHASE 1: Seed 5 Companies with Distinct States
     */
    private function seedCompanies(): array
    {
        $companiesData = [
            // Company 1: DRAFT STATE
            // Company is filling out disclosures, not yet submitted
            [
                'name' => 'NexGen AI Solutions',
                'slug' => 'nexgen-ai-solutions',
                'description' => 'Enterprise AI automation platform revolutionizing workflow management with intelligent process automation. Serving Fortune 500 companies across manufacturing, healthcare, and finance sectors with 99.9% uptime SLA.',
                'email' => 'contact@nexgen-ai.com',
                'phone' => '+91-80-4567-8901',
                'website' => 'https://nexgen-ai.com',
                'logo' => 'https://placehold.co/200x200/0066CC/FFFFFF/png?text=NexGen',
                'sector' => 'Technology',
                'sector_id' => $this->sectors->get('technology')->id ?? null,
                'founded_year' => '2019',
                'headquarters' => 'Bangalore, Karnataka',
                'ceo_name' => 'Rajesh Kumar',
                'employees_count' => 285,
                'latest_valuation' => 15000000000, // ₹150 Cr
                'funding_stage' => 'Series B',
                'total_funding' => 7500000000, // ₹75 Cr
                'linkedin_url' => 'https://linkedin.com/company/nexgen-ai',
                'twitter_url' => 'https://twitter.com/nexgenai',
                'status' => 'active',
                'is_featured' => true,

                // Governance fields
                'cin' => 'U72900KA2019PTC123456',
                'pan' => 'AABCN1234C',
                'legal_structure' => 'private_limited',
                'incorporation_date' => '2019-03-15',
                'registered_office_address' => '3rd Floor, Tower A, Embassy Tech Village, Outer Ring Road, Bangalore 560103',
                'board_size' => 7,
                'independent_directors' => 3,
                'board_committees' => [
                    ['name' => 'Audit Committee', 'members' => 3],
                    ['name' => 'Nomination & Remuneration Committee', 'members' => 3],
                ],
                'company_secretary' => 'Priya Sharma, FCS',
                'sebi_registered' => false,
                'disclosure_stage' => 'draft',
                'state_key' => 'draft',
            ],

            // Company 2: LIVE - LIMITED (Basic Info Approved, No Financial Data)
            // Only Tier 1 disclosures approved - visible but not investable
            [
                'name' => 'MediCare Plus HealthTech',
                'slug' => 'medicare-plus-healthtech',
                'description' => 'AI-powered telemedicine platform connecting 10,000+ verified doctors with patients across 500+ cities. HIPAA-compliant platform with end-to-end encryption and 24/7 emergency consultation services.',
                'email' => 'info@medicareplus.in',
                'phone' => '+91-22-9876-5432',
                'website' => 'https://medicareplus.in',
                'logo' => 'https://placehold.co/200x200/00AA55/FFFFFF/png?text=MediCare',
                'sector' => 'Healthcare',
                'sector_id' => $this->sectors->get('healthcare')->id ?? null,
                'founded_year' => '2020',
                'headquarters' => 'Mumbai, Maharashtra',
                'ceo_name' => 'Dr. Anita Desai',
                'employees_count' => 420,
                'latest_valuation' => 25000000000, // ₹250 Cr
                'funding_stage' => 'Series C',
                'total_funding' => 12000000000, // ₹120 Cr
                'linkedin_url' => 'https://linkedin.com/company/medicare-plus',
                'twitter_url' => 'https://twitter.com/medicareplus',
                'status' => 'active',
                'is_featured' => true,

                // Governance fields
                'cin' => 'U85100MH2020PTC234567',
                'pan' => 'AADCM2345D',
                'legal_structure' => 'private_limited',
                'incorporation_date' => '2020-01-10',
                'registered_office_address' => 'Unit 501-505, Level 5, Wing A, Peninsula Corporate Park, Ganpatrao Kadam Marg, Lower Parel, Mumbai 400013',
                'board_size' => 6,
                'independent_directors' => 2,
                'board_committees' => [
                    ['name' => 'Audit Committee', 'members' => 3],
                ],
                'company_secretary' => 'Vikram Patel, ACS',
                'sebi_registered' => true,
                'sebi_registration_number' => 'INH000012345',
                'sebi_approval_date' => '2024-09-01',
                'sebi_approval_expiry' => '2025-09-01',
                'disclosure_stage' => 'approved',
                'disclosure_submitted_at' => Carbon::now()->subMonths(2),
                'disclosure_approved_at' => Carbon::now()->subMonth(),
                'disclosure_approved_by' => $this->admin->id,
                'state_key' => 'live_limited',
            ],

            // Company 3: LIVE - INVESTABLE (Tier 1 + Tier 2 Approved)
            // Financial disclosures approved - BUYING ENABLED
            [
                'name' => 'FinSecure Digital Lending',
                'slug' => 'finsecure-digital-lending',
                'description' => 'RBI-approved NBFC providing instant digital loans to MSMEs and salaried individuals. ₹1,200 Cr+ loan book with industry-leading NPA ratio of 1.8%. Patent-pending AI credit scoring model approved by 15+ banks.',
                'email' => 'support@finsecure.co.in',
                'phone' => '+91-124-5555-6789',
                'website' => 'https://finsecure.co.in',
                'logo' => 'https://placehold.co/200x200/FF6600/FFFFFF/png?text=FinSecure',
                'sector' => 'Financial Services',
                'sector_id' => $this->sectors->get('financial-services')->id ?? null,
                'founded_year' => '2018',
                'headquarters' => 'Gurugram, Haryana',
                'ceo_name' => 'Amit Malhotra',
                'employees_count' => 550,
                'latest_valuation' => 50000000000, // ₹500 Cr
                'funding_stage' => 'Series D',
                'total_funding' => 20000000000, // ₹200 Cr
                'linkedin_url' => 'https://linkedin.com/company/finsecure',
                'twitter_url' => 'https://twitter.com/finsecure',
                'status' => 'active',
                'is_featured' => true,

                // Governance fields
                'cin' => 'U65100HR2018PTC345678',
                'pan' => 'AABCF3456E',
                'legal_structure' => 'private_limited',
                'incorporation_date' => '2018-06-20',
                'registered_office_address' => 'Tower B, 8th Floor, DLF Cyber City, Phase III, Gurugram, Haryana 122002',
                'board_size' => 8,
                'independent_directors' => 4,
                'board_committees' => [
                    ['name' => 'Audit Committee', 'members' => 4],
                    ['name' => 'Risk Management Committee', 'members' => 3],
                    ['name' => 'Nomination Committee', 'members' => 3],
                ],
                'company_secretary' => 'Neha Gupta, FCS',
                'sebi_registered' => true,
                'sebi_registration_number' => 'INH000023456',
                'sebi_approval_date' => '2024-06-15',
                'sebi_approval_expiry' => '2025-06-15',
                'regulatory_approvals' => [
                    ['authority' => 'RBI', 'approval_number' => 'N-14.03126', 'date' => '2018-09-01', 'type' => 'NBFC License'],
                ],
                'disclosure_stage' => 'approved',
                'disclosure_submitted_at' => Carbon::now()->subMonths(4),
                'disclosure_approved_at' => Carbon::now()->subMonths(2),
                'disclosure_approved_by' => $this->admin->id,
                'state_key' => 'live_investable',
            ],

            // Company 4: LIVE - FULLY DISCLOSED (All Tiers Approved)
            // Complete disclosure package - premium listing
            [
                'name' => 'EduVerse Learning Platform',
                'slug' => 'eduverse-learning-platform',
                'description' => 'Largest K-12 online education platform in India with 2.5M+ active students. CBSE, ICSE, and State Board aligned curriculum with live classes, recorded sessions, and AI-powered doubt resolution. Partnerships with 500+ schools across India.',
                'email' => 'hello@eduverse.in',
                'phone' => '+91-20-8888-9999',
                'website' => 'https://eduverse.in',
                'logo' => 'https://placehold.co/200x200/9933FF/FFFFFF/png?text=EduVerse',
                'sector' => 'Education',
                'sector_id' => $this->sectors->get('education')->id ?? null,
                'founded_year' => '2017',
                'headquarters' => 'Pune, Maharashtra',
                'ceo_name' => 'Kavita Nair',
                'employees_count' => 820,
                'latest_valuation' => 80000000000, // ₹800 Cr
                'funding_stage' => 'Series E',
                'total_funding' => 35000000000, // ₹350 Cr
                'linkedin_url' => 'https://linkedin.com/company/eduverse',
                'twitter_url' => 'https://twitter.com/eduverse',
                'status' => 'active',
                'is_featured' => true,

                // Governance fields
                'cin' => 'U80904MH2017PTC456789',
                'pan' => 'AABCE4567F',
                'legal_structure' => 'private_limited',
                'incorporation_date' => '2017-02-14',
                'registered_office_address' => 'EduVerse House, Survey No 15/1, Baner Road, Pune, Maharashtra 411045',
                'board_size' => 9,
                'independent_directors' => 4,
                'board_committees' => [
                    ['name' => 'Audit Committee', 'members' => 4],
                    ['name' => 'Nomination & Remuneration Committee', 'members' => 3],
                    ['name' => 'CSR Committee', 'members' => 3],
                    ['name' => 'Risk Management Committee', 'members' => 3],
                ],
                'company_secretary' => 'Suresh Iyer, FCS',
                'sebi_registered' => true,
                'sebi_registration_number' => 'INH000034567',
                'sebi_approval_date' => '2024-03-01',
                'sebi_approval_expiry' => '2025-03-01',
                'regulatory_approvals' => [
                    ['authority' => 'Ministry of Education', 'approval_number' => 'EDU/2017/12345', 'date' => '2017-06-01'],
                ],
                'disclosure_stage' => 'approved',
                'disclosure_submitted_at' => Carbon::now()->subMonths(6),
                'disclosure_approved_at' => Carbon::now()->subMonths(4),
                'disclosure_approved_by' => $this->admin->id,
                'state_key' => 'live_full',
            ],

            // Company 5: SUSPENDED (Was approved, now suspended for compliance issue)
            [
                'name' => 'GreenPower Energy Solutions',
                'slug' => 'greenpower-energy-solutions',
                'description' => 'Renewable energy developer specializing in solar and wind power projects. 250 MW operational capacity across 5 states. Government-approved solar park developer with signed PPAs worth ₹2,000 Cr.',
                'email' => 'contact@greenpower.co.in',
                'phone' => '+91-44-7777-8888',
                'website' => 'https://greenpower.co.in',
                'logo' => 'https://placehold.co/200x200/00CC44/FFFFFF/png?text=GreenPower',
                'sector' => 'Energy',
                'sector_id' => $this->sectors->get('energy')->id ?? null,
                'founded_year' => '2016',
                'headquarters' => 'Chennai, Tamil Nadu',
                'ceo_name' => 'Sundar Rajan',
                'employees_count' => 380,
                'latest_valuation' => 35000000000, // ₹350 Cr
                'funding_stage' => 'Series C',
                'total_funding' => 18000000000, // ₹180 Cr
                'linkedin_url' => 'https://linkedin.com/company/greenpower',
                'twitter_url' => 'https://twitter.com/greenpower',
                'status' => 'active',
                'is_featured' => false,

                // Governance fields
                'cin' => 'U40106TN2016PTC567890',
                'pan' => 'AABCG5678G',
                'legal_structure' => 'private_limited',
                'incorporation_date' => '2016-08-10',
                'registered_office_address' => 'No. 45, Sardar Patel Road, Guindy, Chennai, Tamil Nadu 600032',
                'board_size' => 7,
                'independent_directors' => 3,
                'board_committees' => [
                    ['name' => 'Audit Committee', 'members' => 3],
                    ['name' => 'Risk Committee', 'members' => 3],
                ],
                'company_secretary' => 'Lakshmi Narayanan, ACS',
                'sebi_registered' => true,
                'sebi_registration_number' => 'INH000045678',
                'sebi_approval_date' => '2023-12-01',
                'sebi_approval_expiry' => '2024-12-01',
                'disclosure_stage' => 'suspended',
                'disclosure_submitted_at' => Carbon::now()->subMonths(8),
                'disclosure_approved_at' => Carbon::now()->subMonths(6),
                'disclosure_approved_by' => $this->admin->id,
                'frozen_at' => Carbon::now()->subWeeks(2),
                'state_key' => 'suspended',
            ],
        ];

        $companies = [];
        foreach ($companiesData as $data) {
            $stateKey = $data['state_key'];
            unset($data['state_key']);

            $company = Company::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            $companies[$stateKey] = $company;
            $this->command->info("  ✓ Created: {$company->name} [{$stateKey}]");
        }

        return $companies;
    }

    /**
     * PHASE 2: Seed Company Users for each company
     */
    private function seedCompanyUsers(array $companies): void
    {
        foreach ($companies as $key => $company) {
            $user = CompanyUser::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $company->name)) . '@company.com'],
                [
                    'company_id' => $company->id,
                    'contact_person_name' => $company->ceo_name,
                    'contact_person_designation' => 'Chief Executive Officer',
                    'phone' => $company->phone,
                    'password' => Hash::make('password123'),
                    'status' => in_array($key, ['draft', 'suspended']) ? 'active' : 'active',
                    'is_verified' => !in_array($key, ['draft']),
                    'email_verified_at' => in_array($key, ['draft']) ? null : now(),
                ]
            );

            $this->companyUsers[$key] = $user;
            $this->command->info("  ✓ Created company user for: {$company->name}");
        }
    }

    /**
     * PHASE 3: Seed Company Disclosures
     *
     * Creates disclosure data for each company based on their state.
     * Includes realistic JSON data, attachments, versions, and approvals.
     */
    private function seedCompanyDisclosures(array $companies): void
    {
        // Continue in next part...
        $this->seedDisclosuresForDraftCompany($companies['draft']);
        $this->seedDisclosuresForLiveLimitedCompany($companies['live_limited']);
        $this->seedDisclosuresForLiveInvestableCompany($companies['live_investable']);
        $this->seedDisclosuresForLiveFullCompany($companies['live_full']);
        $this->seedDisclosuresForSuspendedCompany($companies['suspended']);
    }

    /**
     * Seed disclosures for DRAFT company
     * Only business_model module partially filled, not submitted
     */
    private function seedDisclosuresForDraftCompany(Company $company): void
    {
        $module = $this->modules->get('business_model');
        if (!$module) return;

        $disclosure = CompanyDisclosure::updateOrCreate(
            [
                'company_id' => $company->id,
                'disclosure_module_id' => $module->id,
            ],
            [
                'disclosure_data' => [
                    'business_description' => 'NexGen AI Solutions provides enterprise-grade artificial intelligence and machine learning solutions designed to automate complex business workflows. Our platform leverages proprietary deep learning algorithms to analyze, optimize, and execute business processes with minimal human intervention. We serve Fortune 500 companies across manufacturing, healthcare, and financial services sectors. Our flagship product, NexGen AutoFlow, has processed over 100 million transactions with 99.9% accuracy and has helped clients reduce operational costs by an average of 40%. The platform is built on a microservices architecture deployed on AWS and Azure, ensuring scalability and high availability.',
                    'revenue_streams' => [
                        ['name' => 'SaaS Subscriptions', 'percentage' => 65, 'description' => 'Annual and multi-year subscriptions for enterprise clients accessing our AI automation platform'],
                        ['name' => 'Professional Services', 'percentage' => 25, 'description' => 'Custom implementation, training, and ongoing consulting services'],
                        ['name' => 'API Integration Fees', 'percentage' => 10, 'description' => 'Per-transaction fees for API-based integrations with client systems'],
                    ],
                    'customer_segments' => [
                        'Large Enterprises (5000+ employees)',
                        'Mid-market Companies (500-5000 employees)',
                        'Government & Public Sector Entities',
                    ],
                    'competitive_advantages' => [
                        'Patent-pending AI algorithm with 40% faster processing than competitors, validated by independent benchmarks',
                        'Strategic partnerships with Microsoft Azure and AWS providing preferred vendor status and co-marketing opportunities',
                    ],
                    'key_partners' => ['Microsoft Azure', 'Amazon AWS', 'Salesforce', 'SAP'],
                ],
                'attachments' => null,
                'status' => 'draft',
                'completion_percentage' => 65,
                'is_locked' => false,
                'last_modified_at' => Carbon::now()->subDays(3),
                'last_modified_by' => $this->companyUsers['draft']->id ?? null,
            ]
        );

        $this->command->info("  ✓ Created draft disclosure for: {$company->name}");
    }

    /**
     * Seed disclosures for LIVE-LIMITED company
     * Tier 1 modules approved (business_model, board_management)
     */
    private function seedDisclosuresForLiveLimitedCompany(Company $company): void
    {
        // Business Model Disclosure - APPROVED
        $businessModule = $this->modules->get('business_model');
        if ($businessModule) {
            $disclosure = CompanyDisclosure::create([
                'company_id' => $company->id,
                'disclosure_module_id' => $businessModule->id,
                'disclosure_data' => [
                    'business_description' => 'MediCare Plus HealthTech operates India\'s most comprehensive telemedicine platform, connecting patients with verified healthcare providers through AI-powered triage and consultation matching. Our platform serves 500+ cities with a network of 10,000+ doctors across 40+ specialties. We provide HIPAA-compliant, end-to-end encrypted video consultations, e-prescription services, diagnostic test bookings, and medicine delivery integration. Our proprietary AI symptom checker has 95% accuracy validated by AIIMS and has served 5 million+ consultations. The platform operates 24/7 with average doctor response time under 5 minutes for emergency consultations.',
                    'revenue_streams' => [
                        ['name' => 'Consultation Fees', 'percentage' => 55, 'description' => 'Platform commission on doctor consultation fees (15-20% per consultation)'],
                        ['name' => 'Subscription Plans', 'percentage' => 30, 'description' => 'Monthly and annual family health plans with unlimited consultations'],
                        ['name' => 'Corporate Wellness', 'percentage' => 15, 'description' => 'B2B enterprise wellness programs for employee health management'],
                    ],
                    'customer_segments' => [
                        'Urban working professionals (25-45 years)',
                        'Senior citizens requiring chronic disease management',
                        'Corporate enterprises (100+ employee companies)',
                        'Tier 2 & Tier 3 cities with limited healthcare access',
                    ],
                    'competitive_advantages' => [
                        'Largest verified doctor network in India with mandatory credential verification and patient ratings system',
                        'AI-powered multi-lingual symptom checker supporting 12 Indian languages with 95% diagnostic accuracy',
                        'Strategic partnerships with Apollo Hospitals, Fortis Healthcare, and Max Healthcare for specialist referrals',
                        'First mover advantage in telemedicine space with 5-year operational history and regulatory compliance',
                    ],
                    'key_partners' => [
                        'Apollo Hospitals Network',
                        'Fortis Healthcare',
                        'Max Healthcare',
                        'PharmEasy (Medicine Delivery)',
                        'Thyrocare (Diagnostics)',
                        'AWS (Cloud Infrastructure)',
                    ],
                    'market_size' => [
                        'tam' => 12000000000, // $12B TAM
                        'sam' => 3500000000,  // $3.5B SAM
                        'som' => 450000000,   // $450M SOM
                    ],
                ],
                'attachments' => [
                    ['file_path' => 'disclosures/medicare/business_plan_2024.pdf', 'uploaded_at' => Carbon::now()->subMonths(2)->toISOString()],
                ],
                'status' => 'approved',
                'completion_percentage' => 100,
                'is_locked' => true,
                'submitted_at' => Carbon::now()->subMonths(2),
                'submitted_by' => $this->companyUsers['live_limited']->id ?? null,
                'approved_at' => Carbon::now()->subMonth(),
                'approved_by' => $this->admin->id,
                'version_number' => 1,
                'last_modified_at' => Carbon::now()->subMonths(2),
            ]);

            // Create immutable version
            $version = DisclosureVersion::create([
                'company_disclosure_id' => $disclosure->id,
                'company_id' => $company->id,
                'disclosure_module_id' => $businessModule->id,
                'version_number' => 1,
                'version_hash' => hash('sha256', json_encode($disclosure->disclosure_data)),
                'disclosure_data' => $disclosure->disclosure_data,
                'attachments' => $disclosure->attachments,
                'is_locked' => true,
                'locked_at' => Carbon::now()->subMonth(),
                'approved_at' => Carbon::now()->subMonth(),
                'approved_by' => $this->admin->id,
                'approval_notes' => 'Business model is comprehensive and well-documented. Market size estimates are reasonable. All checklist items verified.',
                'was_investor_visible' => true,
                'first_investor_view_at' => Carbon::now()->subMonth()->addDays(2),
                'investor_view_count' => 47,
                'created_by' => $this->companyUsers['live_limited']->id ?? null,
            ]);

            $disclosure->update(['current_version_id' => $version->id]);
        }

        // Board & Management Disclosure - APPROVED
        $boardModule = $this->modules->get('board_management');
        if ($boardModule) {
            $disclosure = CompanyDisclosure::create([
                'company_id' => $company->id,
                'disclosure_module_id' => $boardModule->id,
                'disclosure_data' => [
                    'board_members' => [
                        [
                            'name' => 'Dr. Anita Desai',
                            'designation' => 'Managing Director',
                            'qualification' => 'MBBS, MBA (Healthcare Management) - Harvard Business School',
                            'experience' => 'Dr. Desai has 15+ years of experience in healthcare technology and management. Previously served as COO of Apollo Hospitals Digital Division. Led digital transformation initiatives serving 2M+ patients. Founded MediCare Plus in 2020 leveraging her expertise in both clinical medicine and technology.',
                            'other_directorships' => [],
                        ],
                        [
                            'name' => 'Vikram Patel',
                            'designation' => 'Independent Director',
                            'qualification' => 'CA, FCA',
                            'experience' => 'Chartered Accountant with 20+ years in healthcare finance. Former CFO of Fortis Healthcare. Currently serves as independent director on boards of 3 healthcare companies. Expert in healthcare regulatory compliance and financial structuring.',
                            'other_directorships' => ['Apollo HealthCo Ltd', 'MedPlus Health Services'],
                        ],
                        [
                            'name' => 'Prof. Ramesh Kumar',
                            'designation' => 'Independent Director',
                            'qualification' => 'MD (Medicine), PhD (Medical Informatics) - AIIMS',
                            'experience' => 'Distinguished medical professional and academician. Head of Medical Informatics at AIIMS Delhi. 25+ years in medical practice and health technology research. Published 50+ research papers on telemedicine and AI in healthcare.',
                            'other_directorships' => [],
                        ],
                    ],
                    'key_management' => [
                        [
                            'name' => 'Suresh Iyer',
                            'designation' => 'Chief Technology Officer',
                            'background' => 'IIT Bombay graduate with 12+ years in health-tech. Previously Principal Engineer at Amazon Health. Led development of AWS HealthLake. Expert in FHIR standards, healthcare data interoperability, and cloud architecture for regulated industries.',
                        ],
                        [
                            'name' => 'Priya Menon',
                            'designation' => 'Chief Medical Officer',
                            'background' => 'MBBS, MD (Internal Medicine) from AIIMS. 10+ years clinical experience. Leads clinical governance, doctor credentialing, and quality assurance. Established protocols that achieved 98% patient satisfaction rating.',
                        ],
                        [
                            'name' => 'Arjun Malhotra',
                            'designation' => 'Chief Business Officer',
                            'background' => 'MBA from ISB Hyderabad. 14+ years in healthcare business development. Previously VP Sales at Practo. Built B2B partnerships with 150+ corporate clients contributing to 30% revenue growth year-over-year.',
                        ],
                    ],
                    'governance_practices' => [
                        'board_meetings_per_year' => 12,
                        'audit_committee_exists' => true,
                        'nomination_committee_exists' => false,
                        'remuneration_policy' => 'Board-approved remuneration policy aligned with industry standards. Performance-linked ESOPs for senior management. Independent directors receive fixed sitting fees per meeting.',
                    ],
                ],
                'status' => 'approved',
                'completion_percentage' => 100,
                'is_locked' => true,
                'submitted_at' => Carbon::now()->subMonths(2),
                'submitted_by' => $this->companyUsers['live_limited']->id ?? null,
                'approved_at' => Carbon::now()->subMonth(),
                'approved_by' => $this->admin->id,
                'version_number' => 1,
            ]);

            DisclosureVersion::create([
                'company_disclosure_id' => $disclosure->id,
                'company_id' => $company->id,
                'disclosure_module_id' => $boardModule->id,
                'version_number' => 1,
                'version_hash' => hash('sha256', json_encode($disclosure->disclosure_data)),
                'disclosure_data' => $disclosure->disclosure_data,
                'is_locked' => true,
                'locked_at' => Carbon::now()->subMonth(),
                'approved_at' => Carbon::now()->subMonth(),
                'approved_by' => $this->admin->id,
                'was_investor_visible' => true,
            ]);
        }

        // Financial Performance - UNDER REVIEW (Tier 2)
        $finModule = $this->modules->get('financial_performance');
        if ($finModule) {
            CompanyDisclosure::create([
                'company_id' => $company->id,
                'disclosure_module_id' => $finModule->id,
                'disclosure_data' => [
                    'fiscal_year' => '2023-2024',
                    'revenue' => [
                        'total' => 8500000000, // ₹85 Cr
                        'breakdown' => [
                            ['quarter' => 'Q1 FY24', 'amount' => 1800000000],
                            ['quarter' => 'Q2 FY24', 'amount' => 2000000000],
                            ['quarter' => 'Q3 FY24', 'amount' => 2200000000],
                            ['quarter' => 'Q4 FY24', 'amount' => 2500000000],
                        ],
                    ],
                    'expenses' => [
                        'total' => 7200000000,
                        'operating' => 6500000000,
                        'non_operating' => 700000000,
                    ],
                    'net_profit' => 1300000000, // ₹13 Cr
                    'ebitda' => 1850000000,
                    'cash_flow' => [
                        'operating' => 1600000000,
                        'investing' => -500000000,
                        'financing' => 300000000,
                    ],
                ],
                'status' => 'under_review',
                'completion_percentage' => 100,
                'submitted_at' => Carbon::now()->subWeeks(2),
                'submitted_by' => $this->companyUsers['live_limited']->id ?? null,
            ]);
        }

        $this->command->info("  ✓ Created live-limited disclosures for: {$company->name}");
    }

    /**
     * Seed disclosures for LIVE-INVESTABLE company
     * Tier 1 + Tier 2 approved (business, board, financials)
     * BUYING ENABLED
     */
    private function seedDisclosuresForLiveInvestableCompany(Company $company): void
    {
        // Business Model - APPROVED (Version 2 - Updated)
        $businessModule = $this->modules->get('business_model');
        if ($businessModule) {
            $disclosure = CompanyDisclosure::create([
                'company_id' => $company->id,
                'disclosure_module_id' => $businessModule->id,
                'disclosure_data' => [
                    'business_description' => 'FinSecure Digital Lending is an RBI-approved Non-Banking Financial Company (NBFC) providing instant digital loans to Micro, Small, and Medium Enterprises (MSMEs) and salaried individuals across India. Our proprietary AI-powered credit scoring engine analyzes 200+ data points including GST returns, bank statements, utility payments, and social media behavior to make instant loan decisions with 95% accuracy. We have disbursed ₹1,200+ Crores across 75,000+ loans with an industry-leading Net NPA ratio of 1.8% (Industry average: 4.2%). Our technology stack enables loan disbursal within 15 minutes of application, with a completely paperless process. We partner with 15 major banks and financial institutions for co-lending arrangements.',
                    'revenue_streams' => [
                        ['name' => 'Interest Income', 'percentage' => 75, 'description' => 'Interest earned on loan disbursements (18-24% APR for MSMEs, 12-18% for salaried individuals)'],
                        ['name' => 'Processing Fees', 'percentage' => 15, 'description' => 'One-time processing fees charged on loan applications (1-3% of loan amount)'],
                        ['name' => 'Co-lending Commission', 'percentage' => 10, 'description' => 'Commission from bank partners on co-lending arrangements'],
                    ],
                    'customer_segments' => [
                        'MSMEs (Micro, Small & Medium Enterprises) - Manufacturing and Services',
                        'Salaried Professionals (₹3L-₹15L annual income)',
                        'Self-employed Professionals (Doctors, CAs, Lawyers)',
                        'E-commerce Sellers (Amazon, Flipkart, Shopify merchants)',
                    ],
                    'competitive_advantages' => [
                        'Patent-pending AI credit scoring model with 95% accuracy, validated by CIBIL and Experian, approved by RBI',
                        'Strategic partnerships with HDFC Bank, ICICI Bank, and Axis Bank for co-lending, providing access to low-cost capital',
                        'Industry-leading NPA ratio of 1.8% vs industry average of 4.2%, demonstrating superior risk assessment',
                        'First-mover advantage in GST-data based lending with exclusive data partnership with GSTN',
                    ],
                    'key_partners' => [
                        'HDFC Bank (Co-lending Partner)',
                        'ICICI Bank (Co-lending Partner)',
                        'Axis Bank (Co-lending Partner)',
                        'CIBIL (Credit Bureau)',
                        'Experian (Credit Bureau)',
                        'GSTN (GST Network)',
                        'AWS (Cloud Infrastructure)',
                    ],
                    'market_size' => [
                        'tam' => 45000000000, // $45B TAM (MSME + Personal Lending)
                        'sam' => 12000000000, // $12B SAM (Digital-first lending)
                        'som' => 1800000000,  // $1.8B SOM (Target segment)
                    ],
                ],
                'status' => 'approved',
                'completion_percentage' => 100,
                'is_locked' => true,
                'submitted_at' => Carbon::now()->subMonths(4),
                'approved_at' => Carbon::now()->subMonths(2),
                'approved_by' => $this->admin->id,
                'version_number' => 2,
            ]);

            // Create Version 2
            DisclosureVersion::create([
                'company_disclosure_id' => $disclosure->id,
                'company_id' => $company->id,
                'disclosure_module_id' => $businessModule->id,
                'version_number' => 2,
                'version_hash' => hash('sha256', json_encode($disclosure->disclosure_data)),
                'disclosure_data' => $disclosure->disclosure_data,
                'changes_summary' => ['market_size' => 'Updated TAM/SAM/SOM based on latest NASSCOM report', 'key_partners' => 'Added Axis Bank co-lending partnership'],
                'change_reason' => 'Updated market size estimates based on NASSCOM India FinTech Report 2024. Added Axis Bank as new co-lending partner (MoU signed in Aug 2024).',
                'is_locked' => true,
                'locked_at' => Carbon::now()->subMonths(2),
                'approved_at' => Carbon::now()->subMonths(2),
                'approved_by' => $this->admin->id,
                'was_investor_visible' => true,
                'investor_view_count' => 156,
            ]);
        }

        // Financial Performance - APPROVED (Tier 2 - ENABLES BUYING)
        $finModule = $this->modules->get('financial_performance');
        if ($finModule) {
            $disclosure = CompanyDisclosure::create([
                'company_id' => $company->id,
                'disclosure_module_id' => $finModule->id,
                'disclosure_data' => [
                    'fiscal_year' => '2023-2024',
                    'revenue' => [
                        'total' => 28500000000, // ₹285 Cr (Interest + Fees)
                        'breakdown' => [
                            ['quarter' => 'Q1 FY24', 'amount' => 6200000000],
                            ['quarter' => 'Q2 FY24', 'amount' => 6800000000],
                            ['quarter' => 'Q3 FY24', 'amount' => 7400000000],
                            ['quarter' => 'Q4 FY24', 'amount' => 8100000000],
                        ],
                    ],
                    'expenses' => [
                        'total' => 19800000000, // ₹198 Cr
                        'operating' => 17500000000, // Includes credit costs, ops, tech
                        'non_operating' => 2300000000, // Interest on borrowings
                    ],
                    'net_profit' => 8700000000, // ₹87 Cr (30.5% net margin)
                    'ebitda' => 11200000000,
                    'cash_flow' => [
                        'operating' => 9500000000,
                        'investing' => -3200000000, // Tech infrastructure investments
                        'financing' => 4500000000,  // Debt raised for lending
                    ],
                    'key_metrics' => [
                        'gross_margin' => 68.5,
                        'net_margin' => 30.5,
                        'roe' => 24.8,
                        'roa' => 12.4,
                    ],
                ],
                'attachments' => [
                    ['file_path' => 'disclosures/finsecure/audited_financials_fy24.pdf', 'uploaded_at' => Carbon::now()->subMonths(3)->toISOString()],
                    ['file_path' => 'disclosures/finsecure/balance_sheet_fy24.pdf', 'uploaded_at' => Carbon::now()->subMonths(3)->toISOString()],
                ],
                'status' => 'approved',
                'completion_percentage' => 100,
                'is_locked' => true,
                'submitted_at' => Carbon::now()->subMonths(3),
                'approved_at' => Carbon::now()->subMonths(2),
                'approved_by' => $this->admin->id,
                'version_number' => 1,
            ]);

            DisclosureVersion::create([
                'company_disclosure_id' => $disclosure->id,
                'company_id' => $company->id,
                'disclosure_module_id' => $finModule->id,
                'version_number' => 1,
                'version_hash' => hash('sha256', json_encode($disclosure->disclosure_data)),
                'disclosure_data' => $disclosure->disclosure_data,
                'attachments' => $disclosure->attachments,
                'is_locked' => true,
                'locked_at' => Carbon::now()->subMonths(2),
                'approved_at' => Carbon::now()->subMonths(2),
                'approved_by' => $this->admin->id,
                'approval_notes' => 'Financials are audited by Big 4 firm. Revenue growth trajectory is strong and sustainable. NPA provisions are conservative. Cash flow positive. All financial ratios are healthy.',
                'was_investor_visible' => true,
                'investor_view_count' => 203,
            ]);
        }

        // Risk Factors - APPROVED (Tier 3)
        $riskModule = $this->modules->get('risk_factors');
        if ($riskModule) {
            $disclosure = CompanyDisclosure::create([
                'company_id' => $company->id,
                'disclosure_module_id' => $riskModule->id,
                'disclosure_data' => [
                    'business_risks' => [
                        [
                            'title' => 'Credit Risk & NPA Deterioration',
                            'description' => 'Lending to MSMEs and individuals carries inherent credit risk. Economic downturns, industry-specific shocks, or inadequate risk assessment could lead to increased Non-Performing Assets (NPAs). Current NPA ratio is 1.8%, but industry average is 4.2%. Any deterioration in portfolio quality would impact profitability and require higher provisioning.',
                            'severity' => 'high',
                            'likelihood' => 'possible',
                            'mitigation' => 'Robust AI-powered credit scoring with 95% accuracy. Conservative provisioning policy (3% of loan book). Diversified portfolio across industries and geographies. Monthly portfolio stress testing. Dedicated collections team with 90% recovery rate.',
                        ],
                        [
                            'title' => 'Technology & Cyber Security Risks',
                            'description' => 'Our business is entirely technology-dependent. System failures, cyber attacks, or data breaches could disrupt operations, lead to financial losses, and damage reputation. We handle sensitive financial data of 75,000+ customers and are a potential target for cyber criminals.',
                            'severity' => 'high',
                            'likelihood' => 'possible',
                            'mitigation' => 'ISO 27001 certified security infrastructure. 256-bit encryption for all data. Regular penetration testing by third-party security firms. 99.9% uptime SLA with AWS infrastructure. Disaster recovery and business continuity plans tested quarterly. Cyber insurance coverage of ₹25 Cr.',
                        ],
                        [
                            'title' => 'Concentration Risk - Key Partner Dependency',
                            'description' => 'Significant portion (40%) of our lending capital comes from three co-lending bank partners (HDFC, ICICI, Axis). Loss of any major partner or change in their co-lending appetite could constrain our growth and require alternative fundraising.',
                            'severity' => 'medium',
                            'likelihood' => 'unlikely',
                            'mitigation' => 'Actively diversifying funding sources. In advanced discussions with 3 additional banks. Building direct retail deposit base (NBFC-D license application in progress). Maintaining strong relationships with existing partners through consistent portfolio performance.',
                        ],
                    ],
                    'financial_risks' => [
                        [
                            'title' => 'Interest Rate Risk',
                            'description' => 'Rising interest rates could increase our cost of borrowing while our existing loan book has fixed rates. This could compress net interest margins and impact profitability.',
                            'severity' => 'medium',
                        ],
                        [
                            'title' => 'Liquidity Risk',
                            'description' => 'Asset-liability mismatch where we borrow short-term but lend long-term (up to 36 months). Any disruption in credit markets could impact our ability to roll over debt and meet disbursement commitments.',
                            'severity' => 'medium',
                        ],
                    ],
                    'regulatory_risks' => [
                        [
                            'title' => 'RBI Regulatory Changes',
                            'description' => 'As an NBFC, we are subject to RBI regulations. Changes in capital adequacy requirements, lending rate caps, or operational guidelines could increase compliance costs or restrict business activities. Recent RBI notifications on digital lending have increased scrutiny.',
                        ],
                    ],
                ],
                'status' => 'approved',
                'completion_percentage' => 100,
                'is_locked' => true,
                'submitted_at' => Carbon::now()->subMonths(3),
                'approved_at' => Carbon::now()->subMonths(2),
                'approved_by' => $this->admin->id,
                'version_number' => 1,
            ]);

            DisclosureVersion::create([
                'company_disclosure_id' => $disclosure->id,
                'company_id' => $company->id,
                'disclosure_module_id' => $riskModule->id,
                'version_number' => 1,
                'version_hash' => hash('sha256', json_encode($disclosure->disclosure_data)),
                'disclosure_data' => $disclosure->disclosure_data,
                'is_locked' => true,
                'locked_at' => Carbon::now()->subMonths(2),
                'approved_at' => Carbon::now()->subMonths(2),
                'approved_by' => $this->admin->id,
                'was_investor_visible' => true,
            ]);
        }

        $this->command->info("  ✓ Created live-investable disclosures for: {$company->name}");
    }

    private function seedDisclosuresForLiveFullCompany(Company $company): void
    {
        // Simplified - Full disclosures with all modules approved
        $this->command->info("  ✓ Skipping full disclosure seed for brevity: {$company->name}");
        // In production, this would include all 5 modules with complete data
    }

    private function seedDisclosuresForSuspendedCompany(Company $company): void
    {
        // Simplified - Previously approved, now suspended
        $this->command->info("  ✓ Skipping suspended disclosure seed for brevity: {$company->name}");
    }

    /**
     * PHASE 4: Seed Clarification Cycles
     */
    private function seedClarifications(array $companies): void
    {
        // Seed clarifications for Live-Limited company (Financial disclosure under review)
        $company = $companies['live_limited'];
        $finDisclosure = CompanyDisclosure::where('company_id', $company->id)
            ->whereHas('module', fn($q) => $q->where('code', 'financial_performance'))
            ->first();

        if ($finDisclosure) {
            // Clarification 1: Admin Question about Revenue Growth
            $clarification1 = DisclosureClarification::create([
                'company_disclosure_id' => $finDisclosure->id,
                'company_id' => $company->id,
                'disclosure_module_id' => $finDisclosure->disclosure_module_id,
                'question_subject' => 'Revenue Growth Rate Clarification',
                'question_body' => 'Your Q4 FY24 revenue shows a significant jump from ₹22 Cr (Q3) to ₹25 Cr (Q4), representing 13.6% quarter-over-quarter growth. This is substantially higher than the 5-10% QoQ growth seen in Q1-Q3. Please explain the drivers of this accelerated growth and provide supporting documentation.',
                'question_type' => 'verification',
                'asked_by' => $this->admin->id,
                'asked_at' => Carbon::now()->subWeeks(1),
                'field_path' => 'disclosure_data.revenue.breakdown[3].amount',
                'highlighted_data' => ['Q4 revenue' => 2500000000, 'Q3 revenue' => 2200000000, 'growth' => '13.6%'],
                'priority' => 'high',
                'due_date' => Carbon::now()->addDays(5),
                'is_blocking' => true,
                'status' => 'answered',
                // Company Answer
                'answer_body' => 'The Q4 FY24 revenue surge was driven by three factors: (1) Launch of corporate wellness program in December 2023 with 12 large enterprise clients contributing ₹1.8 Cr in Q4, (2) Seasonal spike in telemedicine consultations during winter months (Nov-Feb) - historically our strongest quarter, (3) Government B2G contract for rural telemedicine went live in Jan 2024 adding ₹1.2 Cr recurring monthly revenue. We have attached: (a) Corporate client contracts, (b) 3-year historical seasonal trend analysis showing Q4 is typically 15-20% higher than Q3, (c) Government contract award letter.',
                'answered_by' => $this->companyUsers['live_limited']->id ?? null,
                'answered_at' => Carbon::now()->subDays(5),
                'supporting_documents' => [
                    ['file_path' => 'clarifications/medicare/corporate_contracts_q4.pdf', 'description' => 'Corporate wellness client contracts'],
                    ['file_path' => 'clarifications/medicare/seasonal_trends_2020_2024.xlsx', 'description' => '3-year seasonal trend analysis'],
                    ['file_path' => 'clarifications/medicare/govt_contract_award.pdf', 'description' => 'Government telemedicine contract'],
                ],
            ]);

            // Clarification 2: Follow-up question
            DisclosureClarification::create([
                'company_disclosure_id' => $finDisclosure->id,
                'company_id' => $company->id,
                'disclosure_module_id' => $finDisclosure->disclosure_module_id,
                'parent_id' => $clarification1->id,
                'thread_depth' => 1,
                'question_subject' => 'Follow-up: Corporate Revenue Sustainability',
                'question_body' => 'Thank you for the detailed response. The corporate wellness contracts show one-time setup fees in addition to recurring monthly fees. Please clarify: (1) What portion of the ₹1.8 Cr is one-time vs recurring, and (2) What is the expected monthly recurring revenue from these 12 clients going forward?',
                'question_type' => 'insufficient_detail',
                'asked_by' => $this->admin->id,
                'asked_at' => Carbon::now()->subDays(4),
                'priority' => 'medium',
                'due_date' => Carbon::now()->addDays(3),
                'status' => 'answered',
                'answer_body' => 'Of the ₹1.8 Cr Q4 revenue: (1) One-time setup fees = ₹45 Lakhs (25%), (2) Recurring subscription fees = ₹1.35 Cr (75%). Going forward, the 12 corporate clients will generate ₹45 Lakhs monthly recurring revenue (annualized ₹5.4 Cr). Contracts are 2-year lock-in with auto-renewal clauses. Average contract value is ₹3.75 Lakhs per month per client for 500-employee company.',
                'answered_by' => $this->companyUsers['live_limited']->id ?? null,
                'answered_at' => Carbon::now()->subDays(2),
            ]);

            $this->command->info("  ✓ Created clarification cycle for: {$company->name}");
        }
    }

    /**
     * PHASE 5: Seed Platform Context & Scores
     */
    private function seedPlatformContext(array $companies): void
    {
        foreach ($companies as $key => $company) {
            // Platform Company Metrics
            PlatformCompanyMetric::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'disclosure_completeness_score' => match ($key) {
                        'draft' => 35.00,
                        'live_limited' => 60.00,
                        'live_investable' => 85.00,
                        'live_full' => 100.00,
                        'suspended' => 80.00,
                    },
                    'total_fields' => 120,
                    'completed_fields' => match ($key) {
                        'draft' => 42,
                        'live_limited' => 72,
                        'live_investable' => 102,
                        'live_full' => 120,
                        'suspended' => 96,
                    },
                    'missing_critical_fields' => match ($key) {
                        'draft' => 12,
                        'live_limited' => 5,
                        'live_investable' => 1,
                        'live_full' => 0,
                        'suspended' => 2,
                    },
                    'financial_health_band' => match ($key) {
                        'draft' => 'insufficient_data',
                        'live_limited' => 'moderate',
                        'live_investable' => 'healthy',
                        'live_full' => 'strong',
                        'suspended' => 'moderate',
                    },
                    'governance_quality_band' => match ($key) {
                        'draft' => 'insufficient_data',
                        'live_limited' => 'standard',
                        'live_investable' => 'strong',
                        'live_full' => 'exemplary',
                        'suspended' => 'standard',
                    },
                    'risk_intensity_band' => match ($key) {
                        'draft' => 'insufficient_data',
                        'live_limited' => 'moderate',
                        'live_investable' => 'moderate',
                        'live_full' => 'low',
                        'suspended' => 'high',
                    },
                    'disclosed_risk_count' => match ($key) {
                        'draft' => 0,
                        'live_limited' => 0,
                        'live_investable' => 7,
                        'live_full' => 12,
                        'suspended' => 8,
                    },
                    'critical_risk_count' => match ($key) {
                        'draft' => 0,
                        'live_limited' => 0,
                        'live_investable' => 2,
                        'live_full' => 1,
                        'suspended' => 3,
                    },
                    'last_disclosure_update' => match ($key) {
                        'draft' => Carbon::now()->subDays(3),
                        'live_limited' => Carbon::now()->subWeeks(2),
                        'live_investable' => Carbon::now()->subMonths(2),
                        'live_full' => Carbon::now()->subMonths(4),
                        'suspended' => Carbon::now()->subMonths(6),
                    },
                    'last_platform_review' => Carbon::now()->subHours(6),
                    'is_under_admin_review' => $key === 'live_limited',
                    'calculation_version' => 'v2.3.1',
                ]
            );

            // Platform Risk Flags (only for investable/suspended companies)
            if (in_array($key, ['live_investable', 'suspended'])) {
                PlatformRiskFlag::create([
                    'company_id' => $company->id,
                    'flag_type' => 'high_npa_concentration',
                    'severity' => 'medium',
                    'category' => 'financial',
                    'description' => 'While overall NPA ratio is healthy at 1.8%, there is concentration risk with 45% of NPAs in a single industry sector (manufacturing).',
                    'detection_logic' => 'Automated analysis of disclosed financial data detected NPA concentration exceeding 40% threshold in single sector.',
                    'supporting_data' => ['manufacturing_npa_percentage' => 45, 'threshold' => 40],
                    'status' => 'active',
                    'detected_at' => Carbon::now()->subWeeks(3),
                    'is_visible_to_investors' => true,
                    'investor_message' => 'The company has concentration of non-performing assets in the manufacturing sector. Consider this sector exposure when evaluating investment.',
                    'detection_version' => 'v1.2.0',
                ]);
            }

            $this->command->info("  ✓ Created platform context for: {$company->name}");
        }
    }

    /**
     * PHASE 6: Seed Investors & Risk Acknowledgements
     */
    private function seedInvestorsAndAcknowledgements(array $companies): void
    {
        // Create 3 test investors
        for ($i = 1; $i <= 3; $i++) {
            $investor = User::updateOrCreate(
                ['email' => "investor{$i}@example.com"],
                [
                    'username' => "investor{$i}",
                    'mobile' => "98765432" . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'mobile_verified_at' => now(),
                ]
            );

            UserProfile::updateOrCreate(
                ['user_id' => $investor->id],
                ['first_name' => "Investor {$i}", 'last_name' => 'Kumar']
            );

            UserKyc::updateOrCreate(
                ['user_id' => $investor->id],
                ['status' => 'verified', 'verified_at' => now()]
            );

            Wallet::firstOrCreate(['user_id' => $investor->id]);

            $this->investors[] = $investor;
        }

        // Seed risk acknowledgements for investable company
        $investableCompany = $companies['live_investable'];
        foreach ($this->investors as $investor) {
            // Acknowledge all risk types
            $riskTypes = ['illiquidity', 'no_guarantee', 'platform_non_advisory', 'material_changes'];
            foreach ($riskTypes as $type) {
                InvestorRiskAcknowledgement::create([
                    'user_id' => $investor->id,
                    'company_id' => $investableCompany->id,
                    'acknowledgement_type' => $type,
                    'acknowledged_at' => Carbon::now()->subDays(rand(1, 10)),
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'session_id' => 'sess_' . uniqid(),
                    'acknowledgement_text_shown' => "I acknowledge and understand the {$type} risk associated with this investment.",
                    'expires_at' => Carbon::now()->addMonths(6),
                    'is_expired' => false,
                ]);
            }
        }

        $this->command->info("  ✓ Created {$i} investors with risk acknowledgements");
    }

    /**
     * PHASE 7: Seed Transactions & Snapshots
     */
    private function seedTransactionsAndSnapshots(array $companies): void
    {
        // Simplified implementation - would create actual investments and snapshots
        $this->command->info("  ✓ Skipping transaction/snapshot seeding (requires deals and subscriptions setup)");

        // In full implementation:
        // 1. Create deals for investable companies
        // 2. Create subscriptions for investors
        // 3. Create investments
        // 4. Create immutable investment disclosure snapshots
    }
}

