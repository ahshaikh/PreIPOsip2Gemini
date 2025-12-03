<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyTeamMember;
use App\Models\CompanyFundingRound;
use App\Models\CompanyUpdate;
use App\Models\CompanyFinancialReport;
use App\Models\CompanyDocument;
use App\Models\CompanyAnalytics;
use App\Models\InvestorInterest;
use App\Models\CompanyQna;
use App\Models\CompanyWebinar;
use App\Models\WebinarRegistration;
use App\Models\CompanyOnboardingProgress;
use Carbon\Carbon;

class CompanyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Starting Company User Seeder...');

        // Define company data
        $companiesData = [
            [
                'name' => 'TechVision AI',
                'sector' => 'Artificial Intelligence',
                'description' => 'TechVision AI is a leading artificial intelligence company focused on developing cutting-edge machine learning solutions for enterprise clients. Our platform enables businesses to harness the power of AI for predictive analytics, automation, and intelligent decision-making. With a team of world-class researchers and engineers, we\'re pushing the boundaries of what\'s possible in AI technology.',
                'founded_year' => 2019,
                'website' => 'https://techvisionai.com',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'employees_count' => 250,
                'latest_valuation' => 50000000000, // 500 Cr
            ],
            [
                'name' => 'FinTech Solutions',
                'sector' => 'Financial Technology',
                'description' => 'FinTech Solutions is revolutionizing digital payments and banking in India. Our platform provides seamless UPI integration, digital wallets, and lending solutions to millions of users. We combine innovative technology with regulatory compliance to create secure and user-friendly financial products.',
                'founded_year' => 2018,
                'website' => 'https://fintechsolutions.in',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'employees_count' => 450,
                'latest_valuation' => 100000000000, // 1000 Cr
            ],
            [
                'name' => 'HealthCare Plus',
                'sector' => 'Healthcare & Telemedicine',
                'description' => 'HealthCare Plus is India\'s premier telemedicine and healthcare management platform. We connect patients with qualified doctors through our mobile and web applications, providing instant medical consultations, prescription management, and health monitoring services. Our mission is to make quality healthcare accessible to everyone.',
                'founded_year' => 2020,
                'website' => 'https://healthcareplus.in',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'employees_count' => 180,
                'latest_valuation' => 30000000000, // 300 Cr
            ],
            [
                'name' => 'EduTech Learning',
                'sector' => 'Education Technology',
                'description' => 'EduTech Learning is transforming education through interactive online learning platforms. We offer comprehensive courses in technology, business, and creative skills, with personalized learning paths powered by AI. Our platform has helped over 2 million students upskill and advance their careers.',
                'founded_year' => 2017,
                'website' => 'https://edutechlearning.com',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'employees_count' => 320,
                'latest_valuation' => 75000000000, // 750 Cr
            ],
            [
                'name' => 'Green Energy Systems',
                'sector' => 'Renewable Energy',
                'description' => 'Green Energy Systems is pioneering sustainable energy solutions for residential and commercial clients. We design, install, and maintain solar power systems, energy storage solutions, and smart grid technologies. Our commitment to sustainability drives everything we do.',
                'founded_year' => 2016,
                'website' => 'https://greenenergysystems.in',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'employees_count' => 280,
                'latest_valuation' => 45000000000, // 450 Cr
            ],
        ];

        foreach ($companiesData as $index => $companyData) {
            $this->command->info("\n📊 Creating company: {$companyData['name']}");

            // Create Company
            $company = Company::create([
                'name' => $companyData['name'],
                'slug' => Str::slug($companyData['name']),
                'description' => $companyData['description'],
                'sector' => $companyData['sector'],
                'founded_year' => $companyData['founded_year'],
                'website' => $companyData['website'],
                'email' => strtolower(str_replace(' ', '', $companyData['name'])) . '@example.com',
                'phone' => '+91 ' . rand(7000000000, 9999999999),
                'address' => rand(1, 999) . ' Tech Park, ' . $companyData['city'],
                'city' => $companyData['city'],
                'state' => $companyData['state'],
                'country' => 'India',
                'employees_count' => $companyData['employees_count'],
                'latest_valuation' => $companyData['latest_valuation'],
                'status' => 'active',
                'is_verified' => true,
            ]);

            $this->command->info("✅ Company created with ID: {$company->id}");

            // Create Company User (Admin)
            $companyUser = CompanyUser::create([
                'company_id' => $company->id,
                'name' => 'Admin ' . explode(' ', $companyData['name'])[0],
                'email' => 'admin@' . strtolower(str_replace(' ', '', $companyData['name'])) . '.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $this->command->info("✅ Company User created: {$companyUser->email}");

            // Create Team Members
            $this->createTeamMembers($company);

            // Create Funding Rounds
            $this->createFundingRounds($company);

            // Create Company Updates
            $this->createCompanyUpdates($company);

            // Create Financial Reports
            $this->createFinancialReports($company);

            // Create Documents
            $this->createDocuments($company);

            // Create Analytics Data
            $this->createAnalytics($company);

            // Create Investor Interests
            $this->createInvestorInterests($company);

            // Create Q&A
            $this->createQnA($company);

            // Create Webinars
            $this->createWebinars($company);

            // Create Onboarding Progress
            $this->createOnboardingProgress($company);
        }

        $this->command->info("\n🎉 Company User Seeder completed successfully!");
        $this->command->info("📝 Login credentials for all companies:");
        $this->command->info("   Email: admin@{companyname}.com (e.g., admin@techvisionai.com)");
        $this->command->info("   Password: password123");
    }

    private function createTeamMembers(Company $company)
    {
        $positions = [
            ['CEO & Founder', 'Visionary leader with 15+ years in ' . $company->sector],
            ['CTO', 'Technology expert driving innovation and product development'],
            ['CFO', 'Financial strategist with proven track record in scaling startups'],
            ['VP of Sales', 'Sales leader with extensive network in the industry'],
            ['Head of Product', 'Product visionary shaping the company\'s roadmap'],
        ];

        foreach ($positions as $position) {
            CompanyTeamMember::create([
                'company_id' => $company->id,
                'name' => fake()->name(),
                'designation' => $position[0],
                'bio' => $position[1],
                'linkedin_url' => 'https://linkedin.com/in/' . Str::slug(fake()->name()),
            ]);
        }

        $this->command->info("  ✅ Created 5 team members");
    }

    private function createFundingRounds(Company $company)
    {
        $rounds = [
            ['Seed Round', 50000000, '2020-03-15', 'Sequoia Capital India, Accel Partners'],
            ['Series A', 150000000, '2021-06-20', 'Tiger Global, Lightspeed Venture Partners'],
            ['Series B', 500000000, '2022-11-10', 'SoftBank Vision Fund, Sequoia Capital'],
            ['Series C', 1000000000, '2024-02-28', 'General Atlantic, Tiger Global'],
        ];

        $startYear = 2020;
        foreach ($rounds as $index => $round) {
            CompanyFundingRound::create([
                'company_id' => $company->id,
                'round_name' => $round[0],
                'amount_raised' => $round[1],
                'valuation' => $round[1] * (10 + $index),
                'round_date' => $round[2],
                'investors' => explode(', ', $round[3]), // Convert to array
                'description' => "Funding round {$round[0]} for " . $company->name,
            ]);
        }

        $this->command->info("  ✅ Created 4 funding rounds");
    }

    private function createCompanyUpdates(Company $company)
    {
        $updates = [
            [
                'title' => 'Record Quarter: ' . $company->name . ' Achieves 150% Revenue Growth',
                'content' => "We're thrilled to announce that {$company->name} has achieved record-breaking performance this quarter with 150% YoY revenue growth. This milestone reflects our team's dedication and our customers' trust in our platform.",
            ],
            [
                'title' => 'Expanding to 10 New Cities Across India',
                'content' => "We're excited to share that {$company->name} is expanding operations to 10 new cities, bringing our innovative solutions to millions more customers. This expansion is part of our vision to be accessible nationwide.",
            ],
            [
                'title' => 'New Strategic Partnership Announcement',
                'content' => "We've partnered with leading enterprises to enhance our service offerings and deliver even more value to our customers. This collaboration will accelerate innovation and market reach.",
            ],
        ];

        foreach ($updates as $index => $update) {
            CompanyUpdate::create([
                'company_id' => $company->id,
                'title' => $update['title'],
                'content' => $update['content'],
                'type' => ['milestone', 'announcement', 'partnership'][rand(0, 2)],
                'status' => 'published',
                'published_at' => now()->subDays(rand(10, 90)),
            ]);
        }

        $this->command->info("  ✅ Created 3 company updates");
    }

    private function createFinancialReports(Company $company)
    {
        // Get the company user for uploaded_by
        $companyUser = CompanyUser::where('company_id', $company->id)->first();

        $years = [2022, 2023, 2024];
        $types = ['Annual Report', 'Quarterly Report'];

        foreach ($years as $year) {
            if ($year == 2024) {
                // Only Q1 and Q2 for 2024
                for ($quarter = 1; $quarter <= 2; $quarter++) {
                    $quarterName = "Q{$quarter}";
                    CompanyFinancialReport::create([
                        'company_id' => $company->id,
                        'uploaded_by' => $companyUser->id,
                        'report_type' => 'financial_statement',
                        'title' => "{$quarterName} {$year} Financial Report",
                        'year' => $year,
                        'quarter' => $quarterName,
                        'status' => 'published',
                        'file_path' => "/storage/reports/{$company->slug}-q{$quarter}-{$year}.pdf",
                        'file_name' => "{$company->slug}-q{$quarter}-{$year}.pdf",
                    ]);
                }
            } else {
                // Annual report for previous years
                CompanyFinancialReport::create([
                    'company_id' => $company->id,
                    'uploaded_by' => $companyUser->id,
                    'report_type' => 'annual_report',
                    'title' => "{$year} Annual Report",
                    'year' => $year,
                    'quarter' => 'Annual',
                    'status' => 'published',
                    'file_path' => "/storage/reports/{$company->slug}-annual-{$year}.pdf",
                    'file_name' => "{$company->slug}-annual-{$year}.pdf",
                ]);
            }
        }

        $this->command->info("  ✅ Created financial reports");
    }

    private function createDocuments(Company $company)
    {
        // Get the company user for uploaded_by
        $companyUser = CompanyUser::where('company_id', $company->id)->first();

        $documents = [
            ['Investor Pitch Deck', 'pitch_deck', 'Comprehensive pitch deck showcasing our vision and growth'],
            ['Business Plan 2024', 'other', 'Detailed business plan and roadmap for 2024'],
            ['Product Overview', 'investor_presentation', 'Overview of our product features and benefits'],
        ];

        foreach ($documents as $doc) {
            $fileName = Str::slug($doc[0]) . ".pdf";
            CompanyDocument::create([
                'company_id' => $company->id,
                'uploaded_by' => $companyUser->id,
                'title' => $doc[0],
                'document_type' => $doc[1],
                'description' => $doc[2],
                'file_path' => "/storage/documents/{$company->slug}-{$fileName}",
                'file_name' => $fileName,
                'is_public' => true,
                'status' => 'active',
            ]);
        }

        $this->command->info("  ✅ Created 3 documents");
    }

    private function createAnalytics(Company $company)
    {
        // Create analytics for last 60 days
        for ($i = 60; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();

            CompanyAnalytics::create([
                'company_id' => $company->id,
                'date' => $date,
                'profile_views' => rand(50, 500),
                'document_downloads' => rand(10, 100),
                'financial_report_downloads' => rand(5, 50),
                'deal_views' => rand(20, 200),
                'investor_interest_clicks' => rand(5, 30),
            ]);
        }

        $this->command->info("  ✅ Created 60 days of analytics data");
    }

    private function createInvestorInterests(Company $company)
    {
        $statuses = ['pending', 'contacted', 'qualified', 'not_interested'];
        $interestLevels = ['low', 'medium', 'high'];

        for ($i = 1; $i <= 15; $i++) {
            InvestorInterest::create([
                'company_id' => $company->id,
                'investor_name' => fake()->name(),
                'investor_email' => fake()->unique()->safeEmail(),
                'investor_phone' => '+91 ' . rand(7000000000, 9999999999),
                'interest_level' => $interestLevels[array_rand($interestLevels)],
                'investment_range_min' => rand(1, 5) * 1000000,
                'investment_range_max' => rand(10, 50) * 1000000,
                'message' => "I'm interested in learning more about investment opportunities with {$company->name}. Please share details.",
                'status' => $statuses[array_rand($statuses)],
                'admin_notes' => rand(0, 1) ? 'Follow-up scheduled for next week' : null,
                'created_at' => now()->subDays(rand(1, 60)),
            ]);
        }

        $this->command->info("  ✅ Created 15 investor interests");
    }

    private function createQnA(Company $company)
    {
        $questions = [
            'What is your current monthly recurring revenue (MRR)?',
            'How do you plan to use the funds raised?',
            'What are your customer acquisition costs?',
            'What is your competitive advantage in the market?',
            'What are the key risks facing your business?',
            'Can you explain your revenue model?',
            'What is your customer retention rate?',
            'How large is your total addressable market?',
        ];

        foreach ($questions as $index => $question) {
            $isAnswered = rand(0, 1);

            CompanyQna::create([
                'company_id' => $company->id,
                'question' => $question,
                'answer' => $isAnswered ? "Thank you for your question. " . fake()->paragraph(3) : null,
                'answered_at' => $isAnswered ? now()->subDays(rand(1, 30)) : null,
                'is_public' => $isAnswered && rand(0, 1),
                'is_featured' => $isAnswered && rand(0, 1) == 1,
                'helpful_count' => $isAnswered ? rand(0, 25) : 0,
                'status' => $isAnswered ? 'answered' : 'pending',
                'created_at' => now()->subDays(rand(1, 45)),
            ]);
        }

        $this->command->info("  ✅ Created 8 Q&A entries");
    }

    private function createWebinars(Company $company)
    {
        $webinarTypes = ['webinar', 'investor_call', 'ama', 'product_demo'];
        $titles = [
            'Q4 2024 Investor Update & Growth Strategy',
            'Product Roadmap 2025: What\'s Next',
            'Ask Me Anything with the CEO',
            'Live Product Demo & Platform Overview',
        ];

        foreach ($titles as $index => $title) {
            $isPast = $index < 2;
            $scheduledAt = $isPast
                ? now()->subDays(rand(10, 60))
                : now()->addDays(rand(7, 45));

            $webinar = CompanyWebinar::create([
                'company_id' => $company->id,
                'title' => $title,
                'description' => "Join us for an exclusive session where we'll discuss {$company->name}'s latest updates and answer your questions.",
                'type' => $webinarTypes[$index],
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => rand(45, 90),
                'meeting_link' => 'https://zoom.us/j/' . rand(100000000, 999999999),
                'meeting_id' => rand(100, 999) . '-' . rand(100, 999) . '-' . rand(100, 999),
                'meeting_password' => Str::random(8),
                'max_participants' => rand(100, 500),
                'registered_count' => $isPast ? rand(80, 150) : rand(20, 80),
                'status' => $isPast ? 'completed' : 'scheduled',
                'recording_available' => $isPast && rand(0, 1),
                'recording_url' => $isPast && rand(0, 1) ? 'https://vimeo.com/' . rand(100000000, 999999999) : null,
            ]);

            // Create registrations for past webinars
            if ($isPast) {
                for ($j = 1; $j <= rand(50, 100); $j++) {
                    WebinarRegistration::create([
                        'webinar_id' => $webinar->id,
                        'attendee_name' => fake()->name(),
                        'attendee_email' => fake()->unique()->safeEmail(),
                        'attended' => rand(0, 1),
                    ]);
                }
            }
        }

        $this->command->info("  ✅ Created 4 webinars with registrations");
    }

    private function createOnboardingProgress(Company $company)
    {
        $completedSteps = [
            'profile_basic',
            'profile_branding',
            'team_members',
            'financial_reports',
            'documents',
            'funding_rounds',
            'company_updates',
            'verification',
        ];

        CompanyOnboardingProgress::create([
            'company_id' => $company->id,
            'completed_steps' => $completedSteps,
            'current_step' => 8,
            'total_steps' => 8,
            'completion_percentage' => 100,
            'is_completed' => true,
            'started_at' => now()->subMonths(2),
            'completed_at' => now()->subMonth(),
        ]);

        $this->command->info("  ✅ Created onboarding progress (100% complete)");
    }
}
