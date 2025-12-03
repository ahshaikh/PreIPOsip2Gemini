<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyOnboardingProgress;
use Illuminate\Http\Request;

class OnboardingWizardController extends Controller
{
    /**
     * Get onboarding progress and steps
     */
    public function getProgress(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $progress = CompanyOnboardingProgress::firstOrCreate(
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

        $steps = $this->getOnboardingSteps($company);

        return response()->json([
            'success' => true,
            'progress' => $progress,
            'steps' => $steps,
        ], 200);
    }

    /**
     * Mark a step as completed
     */
    public function completeStep(Request $request)
    {
        $request->validate([
            'step_id' => 'required|string',
        ]);

        $companyUser = $request->user();
        $company = $companyUser->company;

        $progress = CompanyOnboardingProgress::where('company_id', $company->id)->first();

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding progress not found',
            ], 404);
        }

        // Validate the step exists
        $steps = $this->getOnboardingSteps($company);
        $stepExists = collect($steps)->contains(function ($step) use ($request) {
            return $step['id'] === $request->step_id;
        });

        if (!$stepExists) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid step ID',
            ], 400);
        }

        $progress->completeStep($request->step_id);

        return response()->json([
            'success' => true,
            'message' => 'Step marked as completed',
            'progress' => $progress->fresh(),
        ], 200);
    }

    /**
     * Skip onboarding
     */
    public function skipOnboarding(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $progress = CompanyOnboardingProgress::where('company_id', $company->id)->first();

        if ($progress) {
            $progress->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Onboarding skipped successfully',
        ], 200);
    }

    /**
     * Get recommendations based on current state
     */
    public function getRecommendations(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $recommendations = [];

        // Check company profile completeness
        if (!$company->description || strlen($company->description) < 100) {
            $recommendations[] = [
                'type' => 'profile',
                'priority' => 'high',
                'title' => 'Complete your company description',
                'description' => 'A detailed description helps investors understand your business better.',
                'action' => '/company/profile',
            ];
        }

        // Check if logo is uploaded
        if (!$company->logo) {
            $recommendations[] = [
                'type' => 'branding',
                'priority' => 'medium',
                'title' => 'Upload your company logo',
                'description' => 'A professional logo builds trust with investors.',
                'action' => '/company/profile',
            ];
        }

        // Check if team members are added
        $teamCount = $company->teamMembers()->count();
        if ($teamCount === 0) {
            $recommendations[] = [
                'type' => 'team',
                'priority' => 'high',
                'title' => 'Add your team members',
                'description' => 'Showcase your leadership team to build credibility.',
                'action' => '/company/team',
            ];
        }

        // Check if financial reports are uploaded
        $reportCount = $company->financialReports()->count();
        if ($reportCount === 0) {
            $recommendations[] = [
                'type' => 'financials',
                'priority' => 'high',
                'title' => 'Upload financial reports',
                'description' => 'Financial transparency is crucial for investor confidence.',
                'action' => '/company/financial-reports',
            ];
        }

        // Check if documents are uploaded
        $docCount = $company->documents()->count();
        if ($docCount === 0) {
            $recommendations[] = [
                'type' => 'documents',
                'priority' => 'medium',
                'title' => 'Upload company documents',
                'description' => 'Share important documents like pitch decks and business plans.',
                'action' => '/company/documents',
            ];
        }

        // Check if funding rounds are added
        $fundingCount = $company->fundingRounds()->count();
        if ($fundingCount === 0) {
            $recommendations[] = [
                'type' => 'funding',
                'priority' => 'medium',
                'title' => 'Add funding round information',
                'description' => 'Show your fundraising history and current valuation.',
                'action' => '/company/funding',
            ];
        }

        // Check if company updates are posted
        $updateCount = $company->updates()->count();
        if ($updateCount === 0) {
            $recommendations[] = [
                'type' => 'updates',
                'priority' => 'low',
                'title' => 'Post your first company update',
                'description' => 'Keep investors informed about your progress and milestones.',
                'action' => '/company/updates',
            ];
        }

        // Check if webinars are scheduled
        $webinarCount = $company->webinars()->upcoming()->count();
        if ($webinarCount === 0) {
            $recommendations[] = [
                'type' => 'engagement',
                'priority' => 'low',
                'title' => 'Schedule an investor webinar',
                'description' => 'Engage with potential investors through live sessions.',
                'action' => '/company/webinars',
            ];
        }

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
            'total' => count($recommendations),
        ], 200);
    }

    /**
     * Define onboarding steps
     */
    private function getOnboardingSteps(Company $company)
    {
        $steps = [
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

        return $steps;
    }

    /**
     * Check if basic profile is complete
     */
    private function checkProfileBasic(Company $company)
    {
        return !empty($company->name) &&
               !empty($company->description) &&
               strlen($company->description) >= 100 &&
               !empty($company->sector) &&
               !empty($company->email);
    }

    /**
     * Check if branding is complete
     */
    private function checkProfileBranding(Company $company)
    {
        return !empty($company->logo);
    }

    /**
     * Check if team members are added
     */
    private function checkTeamMembers(Company $company)
    {
        return $company->teamMembers()->count() >= 1;
    }

    /**
     * Check if financial reports are uploaded
     */
    private function checkFinancialReports(Company $company)
    {
        return $company->financialReports()->count() >= 1;
    }

    /**
     * Check if documents are uploaded
     */
    private function checkDocuments(Company $company)
    {
        return $company->documents()->count() >= 1;
    }

    /**
     * Check if funding rounds are added
     */
    private function checkFundingRounds(Company $company)
    {
        return $company->fundingRounds()->count() >= 1;
    }

    /**
     * Check if company updates are posted
     */
    private function checkCompanyUpdates(Company $company)
    {
        return $company->updates()->count() >= 1;
    }

    /**
     * Check verification status
     */
    private function checkVerification(Company $company)
    {
        return $company->is_verified === true;
    }
}
