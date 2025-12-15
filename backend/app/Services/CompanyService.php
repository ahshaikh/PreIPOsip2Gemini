<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyService
{
    /**
     * Handle the complete registration flow for a new company and user.
     * FIX: Module 13 - Refactor Registration to Service
     * * @param array $data
     * @return array ['company' => Company, 'user' => CompanyUser]
     */
    public function registerCompany(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Company
            // Note: Slug generation is handled by Company::booted()
            $company = Company::create([
                'name' => $data['company_name'],
                'sector' => $data['sector'],
                'website' => $data['website'] ?? null,
                'status' => 'inactive', // Default to inactive until verified
                'is_verified' => false,
                'profile_completed' => false,
                'profile_completion_percentage' => 10, // Base score
            ]);

            // 2. Create Admin User for Company
            $companyUser = CompanyUser::create([
                'company_id' => $company->id,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'contact_person_name' => $data['contact_person_name'],
                'contact_person_designation' => $data['contact_person_designation'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => 'pending', // Pending admin approval
                'is_verified' => false,
            ]);

            return ['company' => $company, 'user' => $companyUser];
        });
    }

    /**
     * Calculate and update profile completion percentage.
     * FIX: Module 13 - Extracted scoring logic from Controller
     * * @param Company $company
     * @return void
     */
    public function updateProfileCompletion(Company $company)
    {
        $fields = [
            'name' => 5,
            'description' => 10,
            'logo' => 10,
            'website' => 5,
            'sector' => 5,
            'founded_year' => 5,
            'headquarters' => 5,
            'ceo_name' => 5,
            'latest_valuation' => 10,
            'funding_stage' => 5,
            'total_funding' => 5,
            'linkedin_url' => 3,
            'twitter_url' => 2,
            'facebook_url' => 2,
        ];

        $completionPercentage = 0;

        // Base fields check
        foreach ($fields as $field => $weight) {
            if (!empty($company->$field)) {
                $completionPercentage += $weight;
            }
        }

        // Relationship checks (Bonuses)
        if ($company->teamMembers()->exists()) {
            $completionPercentage += 10;
        }

        if ($company->financialReports()->exists()) {
            $completionPercentage += 10;
        }

        if ($company->fundingRounds()->exists()) {
            $completionPercentage += 5;
        }

        if ($company->documents()->exists()) {
            $completionPercentage += 3;
        }

        // Cap at 100
        $completionPercentage = min($completionPercentage, 100);

        $company->update([
            'profile_completion_percentage' => $completionPercentage,
            'profile_completed' => $completionPercentage >= 80,
        ]);
    }
}