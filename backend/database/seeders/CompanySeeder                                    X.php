<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Company Seeder
 *
 * Seeds sample Pre-IPO companies for the directory.
 * Companies are standalone entities used for informational purposes.
 *
 * Note: Products are NOT linked to companies via foreign key.
 * The relationship is conceptual/display only.
 */
class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure sectors exist first
        $techSector = Sector::where('slug', 'technology')->first();
        $healthSector = Sector::where('slug', 'healthcare')->first();
        $financeSector = Sector::where('slug', 'financial-services')->first();
        $eduSector = Sector::where('slug', 'education')->first();
        $energySector = Sector::where('slug', 'energy')->first();

        if (!$techSector || !$healthSector || !$financeSector || !$eduSector || !$energySector) {
            $this->command->warn('⚠ Sectors not found. Run SectorSeeder first.');
            return;
        }

        DB::transaction(function () use ($techSector, $healthSector, $financeSector, $eduSector, $energySector) {
            $companies = [
                [
                    'name' => 'TechCorp India',
                    'slug' => 'techcorp-india',
                    'sector' => 'Technology',
                    'sector_id' => $techSector->id,
                    'description' => 'Leading SaaS platform for enterprise automation and AI-driven workflows. Serving 250+ enterprise clients across India.',
                    'website' => 'https://techcorpindia.example.com',
                    'founded_year' => '2018',
                    'headquarters' => 'Bangalore, Karnataka',
                    'employees_count' => 250,
                    'status' => 'active',
                    'is_verified' => true,
                    'is_featured' => true,
                    'profile_completed' => 100,
                ],
                [
                    'name' => 'HealthPlus Solutions',
                    'slug' => 'healthplus-solutions',
                    'sector' => 'Healthcare',
                    'sector_id' => $healthSector->id,
                    'description' => 'AI-powered telemedicine platform connecting patients with 5,000+ verified healthcare providers across India.',
                    'website' => 'https://healthplus.example.com',
                    'founded_year' => '2019',
                    'headquarters' => 'Mumbai, Maharashtra',
                    'employees_count' => 180,
                    'status' => 'active',
                    'is_verified' => true,
                    'is_featured' => true,
                    'profile_completed' => 100,
                ],
                [
                    'name' => 'FinanceHub Technologies',
                    'slug' => 'financehub-technologies',
                    'sector' => 'Financial Services',
                    'sector_id' => $financeSector->id,
                    'description' => 'Digital lending platform providing instant personal and business loans. NBFC licensed with ₹500 Cr+ loan book.',
                    'website' => 'https://financehub.example.com',
                    'founded_year' => '2020',
                    'headquarters' => 'Gurugram, Haryana',
                    'employees_count' => 320,
                    'status' => 'active',
                    'is_verified' => true,
                    'is_featured' => true,
                    'profile_completed' => 100,
                ],
                [
                    'name' => 'EduTech Academy',
                    'slug' => 'edutech-academy',
                    'sector' => 'Education',
                    'sector_id' => $eduSector->id,
                    'description' => 'Online learning platform offering 200+ industry-certified courses with 500,000+ active learners.',
                    'website' => 'https://edutech.example.com',
                    'founded_year' => '2017',
                    'headquarters' => 'Pune, Maharashtra',
                    'employees_count' => 150,
                    'status' => 'active',
                    'is_verified' => true,
                    'is_featured' => false,
                    'profile_completed' => 95,
                ],
                [
                    'name' => 'GreenEnergy Innovations',
                    'slug' => 'greenenergy-innovations',
                    'sector' => 'Energy',
                    'sector_id' => $energySector->id,
                    'description' => 'Renewable energy solutions provider with 100 MW+ solar and wind power capacity. Government-approved projects.',
                    'website' => 'https://greenenergy.example.com',
                    'founded_year' => '2016',
                    'headquarters' => 'Chennai, Tamil Nadu',
                    'employees_count' => 200,
                    'status' => 'active',
                    'is_verified' => true,
                    'is_featured' => true,
                    'profile_completed' => 100,
                ],
            ];

            foreach ($companies as $companyData) {
                Company::updateOrCreate(
                    ['slug' => $companyData['slug']],
                    $companyData
                );
            }

            $this->command->info('✓ Companies seeded: ' . count($companies) . ' records');
        });
    }
}
