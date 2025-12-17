<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyOnboardingProgress;

class CompanyOnboardingService
{
    /**
     * Initialize or retrieve progress record.
     */
    public function getOrCreateProgress(Company $company)
    {
        return CompanyOnboardingProgress::firstOrCreate(
            ['company_id' => $company->id],
            [
                'completed_steps' => [],
                'current_step' => 1,
                'total_steps' => 8,
                'completion_percentage' => 0,
                'started_at' => now(),
                'is_completed' => false,
            ]
        );
    }

    /**
     * Define and retrieve onboarding steps with status.
     * FIX: Module 13 - Optimize Onboarding Logic
     */
    public function getOnboardingSteps(Company $company)
    {
        return [
            [
                'id' => 'profile_basic',
                'title' => 'Complete Basic Profile',
                'description' => 'Add your company name, description, sector, and contact information',
                'status' => $this->checkProfileBasic($company),
                'action' => '/company/profile',
                'order' => 1,
            ],
            [
                'id' => 'profile_branding',
                'title' => 'Upload Logo and Cover',
                'description' => 'Add your company logo and cover image for better branding',
                'status' => $this->checkProfileBranding($company),
                'action' => '/company/profile',
                'order' => 2,
            ],
            [
                'id' => 'team_members',
                'title' => 'Add Team Members',
                'description' => 'Showcase your leadership team and key personnel',
                'status' => $this->checkTeamMembers($company),
                'action' => '/company/team',
                'order' => 3,
            ],
            [
                'id' => 'financial_reports',
                'title' => 'Upload Financial Reports',
                'description' => 'Share your financial statements and projections',
                'status' => $this->checkFinancialReports($company),
                'action' => '/company/financial-reports',
                'order' => 4,
            ],
            [
                'id' => 'documents',
                'title' => 'Upload Documents',
                'description' => 'Add pitch deck, business plan, and other documents',
                'status' => $this->checkDocuments($company),
                'action' => '/company/documents',
                'order' => 5,
            ],
            [
                'id' => 'funding_rounds',
                'title' => 'Add Funding Information',
                'description' => 'Share your funding history and current valuation',
                'status' => $this->checkFundingRounds($company),
                'action' => '/company/funding',
                'order' => 6,
            ],
            [
                'id' => 'company_updates',
                'title' => 'Post First Update',
                'description' => 'Share news, milestones, or announcements with investors',
                'status' => $this->checkCompanyUpdates($company),
                'action' => '/company/updates',
                'order' => 7,
            ],
            [
                'id' => 'verification',
                'title' => 'Complete Verification',
                'description' => 'Submit for admin verification to go live',
                'status' => $this->checkVerification($company),
                'action' => '/company/profile',
                'order' => 8,
            ],
        ];
    }

    /**
     * V-AUDIT-MODULE18-HIGH: Updated to use eager-loaded counts
     *
     * Generate dynamic recommendations based on company state.
     */
    public function getRecommendations(Company $company)
    {
        $recommendations = [];

        if (!$company->description || strlen($company->description) < 100) {
            $recommendations[] = [
                'type' => 'profile', 'priority' => 'high', 'title' => 'Complete your company description',
                'description' => 'A detailed description helps investors understand your business better.', 'action' => '/company/profile',
            ];
        }

        if (!$company->logo) {
            $recommendations[] = [
                'type' => 'branding', 'priority' => 'medium', 'title' => 'Upload your company logo',
                'description' => 'A professional logo builds trust with investors.', 'action' => '/company/profile',
            ];
        }

        // V-AUDIT-MODULE18-HIGH: Use eager-loaded counts (no additional queries)
        if (($company->team_members_count ?? $company->teamMembers()->count()) === 0) {
            $recommendations[] = [
                'type' => 'team', 'priority' => 'high', 'title' => 'Add your team members',
                'description' => 'Showcase your leadership team to build credibility.', 'action' => '/company/team',
            ];
        }

        if (($company->financial_reports_count ?? $company->financialReports()->count()) === 0) {
            $recommendations[] = [
                'type' => 'financials', 'priority' => 'high', 'title' => 'Upload financial reports',
                'description' => 'Financial transparency is crucial for investor confidence.', 'action' => '/company/financial-reports',
            ];
        }

        if (($company->documents_count ?? $company->documents()->count()) === 0) {
            $recommendations[] = [
                'type' => 'documents', 'priority' => 'medium', 'title' => 'Upload company documents',
                'description' => 'Share important documents like pitch decks and business plans.', 'action' => '/company/documents',
            ];
        }

        if (($company->funding_rounds_count ?? $company->fundingRounds()->count()) === 0) {
            $recommendations[] = [
                'type' => 'funding', 'priority' => 'medium', 'title' => 'Add funding round information',
                'description' => 'Show your fundraising history and current valuation.', 'action' => '/company/funding',
            ];
        }

        if (($company->updates_count ?? $company->updates()->count()) === 0) {
            $recommendations[] = [
                'type' => 'updates', 'priority' => 'low', 'title' => 'Post your first company update',
                'description' => 'Keep investors informed about your progress and milestones.', 'action' => '/company/updates',
            ];
        }

        // V-AUDIT-MODULE18-HIGH: For webinars, use preloaded webinars_count if available
        // Note: If controller didn't eager load with upcoming filter, this will query
        if (($company->webinars_count ?? $company->webinars()->upcoming()->count()) === 0) {
            $recommendations[] = [
                'type' => 'engagement', 'priority' => 'low', 'title' => 'Schedule an investor webinar',
                'description' => 'Engage with potential investors through live sessions.', 'action' => '/company/webinars',
            ];
        }

        return $recommendations;
    }

    // --- Private Checkers ---
    // V-AUDIT-MODULE18-HIGH: Updated to use eager-loaded counts

    private function checkProfileBasic(Company $company)
    {
        return !empty($company->name) &&
               !empty($company->description) &&
               strlen($company->description) >= 100 &&
               !empty($company->sector);
               // Note: Company model doesn't have email directly (it's on CompanyUser)
    }

    private function checkProfileBranding(Company $company)
    {
        return !empty($company->logo);
    }

    private function checkTeamMembers(Company $company)
    {
        // V-AUDIT-MODULE18-HIGH: Use eager-loaded count instead of query
        // If withCount was used, $company->team_members_count is already available (no query)
        // Fallback to relationship count() if not eager-loaded (backward compatibility)
        return ($company->team_members_count ?? $company->teamMembers()->count()) >= 1;
    }

    private function checkFinancialReports(Company $company)
    {
        // V-AUDIT-MODULE18-HIGH: Use eager-loaded count (no additional query)
        return ($company->financial_reports_count ?? $company->financialReports()->count()) >= 1;
    }

    private function checkDocuments(Company $company)
    {
        // V-AUDIT-MODULE18-HIGH: Use eager-loaded count (no additional query)
        return ($company->documents_count ?? $company->documents()->count()) >= 1;
    }

    private function checkFundingRounds(Company $company)
    {
        // V-AUDIT-MODULE18-HIGH: Use eager-loaded count (no additional query)
        return ($company->funding_rounds_count ?? $company->fundingRounds()->count()) >= 1;
    }

    private function checkCompanyUpdates(Company $company)
    {
        // V-AUDIT-MODULE18-HIGH: Use eager-loaded count (no additional query)
        return ($company->updates_count ?? $company->updates()->count()) >= 1;
    }

    private function checkVerification(Company $company)
    {
        return $company->is_verified === true;
    }
}