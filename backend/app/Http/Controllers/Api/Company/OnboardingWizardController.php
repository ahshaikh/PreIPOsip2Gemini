<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyOnboardingProgress;
use App\Services\CompanyOnboardingService;
use Illuminate\Http\Request;

class OnboardingWizardController extends Controller
{
    protected $onboardingService;

    // FIX: Inject Service to handle business logic
    public function __construct(CompanyOnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * V-AUDIT-MODULE18-HIGH: Fixed N+1 Query Avalanche
     *
     * PROBLEM: getOnboardingSteps() calls 8 check methods, each performing a count()
     * query on relationships (teamMembers, financialReports, documents, etc.).
     * Every dashboard load triggered 8+ extra database queries.
     *
     * SOLUTION: Eager load all relationship counts BEFORE passing company to service.
     * Reduces 8+ queries to 1 single query with subqueries. Service can then use
     * preloaded counts via $company->relationship_count syntax (no additional queries).
     *
     * Get onboarding progress and steps
     */
    public function getProgress(Request $request)
    {
        $companyUser = $request->user();

        // V-AUDIT-MODULE18-HIGH: Eager load ALL relationship counts needed by onboarding checks
        // This single query with subqueries prevents N+1 avalanche in getOnboardingSteps()
        $company = Company::withCount([
            'teamMembers',
            'financialReports',
            'documents',
            'fundingRounds',
            'updates',
            'webinars' => function ($query) {
                $query->where('scheduled_at', '>', now()); // upcoming webinars only
            },
        ])->find($companyUser->company_id);

        // V-AUDIT-MODULE18-MEDIUM: Handle orphaned company users gracefully
        // Edge case: If CompanyUser exists but Company was deleted (transaction rollback)
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company profile not found. Please contact support.',
            ], 404);
        }

        // FIX: Use service to get/create progress
        $progress = $this->onboardingService->getOrCreateProgress($company);

        // FIX: Use service to get steps logic
        // Now uses preloaded counts - no additional queries triggered
        $steps = $this->onboardingService->getOnboardingSteps($company);

        return response()->json([
            'success' => true,
            'progress' => $progress,
            'steps' => $steps,
        ], 200);
    }

    /**
     * V-AUDIT-MODULE18-MEDIUM: Added null check for orphaned users
     *
     * Mark a step as completed
     */
    public function completeStep(Request $request)
    {
        $request->validate([
            'step_id' => 'required|string',
        ]);

        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        // V-AUDIT-MODULE18-MEDIUM: Handle orphaned company users
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company profile not found. Please contact support.',
            ], 404);
        }

        $progress = CompanyOnboardingProgress::where('company_id', $company->id)->first();

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding progress not found',
            ], 404);
        }

        // Validate the step exists via Service
        $steps = $this->onboardingService->getOnboardingSteps($company);
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
     * V-AUDIT-MODULE18-MEDIUM: Fixed Skip Onboarding Loophole
     *
     * PROBLEM: skipOnboarding() allowed marking is_completed = true without checking if
     * mandatory steps (like "Profile" or "Verification") were actually done. A company could
     * register, click "Skip", and bypass the entire vetting funnel, appearing as "Onboarding Complete".
     *
     * SOLUTION: Verify that MANDATORY steps are completed before allowing skip. Optional steps
     * (like webinars, updates) can be skipped, but critical profile and verification steps must be done.
     *
     * Skip onboarding
     */
    public function skipOnboarding(Request $request)
    {
        $companyUser = $request->user();

        // V-AUDIT-MODULE18-HIGH: Load company with counts to check step completion
        $company = Company::withCount([
            'teamMembers',
            'financialReports',
            'documents',
        ])->find($companyUser->company_id);

        // V-AUDIT-MODULE18-MEDIUM: Handle orphaned company users
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company profile not found. Please contact support.',
            ], 404);
        }

        // V-AUDIT-MODULE18-MEDIUM: Verify MANDATORY steps are completed before allowing skip
        // These are critical steps that cannot be bypassed for compliance and verification
        $mandatoryStepsComplete = true;
        $missingSteps = [];

        // MANDATORY: Profile Basic (name, description, sector)
        if (empty($company->name) || empty($company->description) || strlen($company->description) < 100 || empty($company->sector)) {
            $mandatoryStepsComplete = false;
            $missingSteps[] = 'Complete Basic Profile (name, description, sector)';
        }

        // MANDATORY: Profile Branding (logo)
        if (empty($company->logo)) {
            $mandatoryStepsComplete = false;
            $missingSteps[] = 'Upload Company Logo';
        }

        // MANDATORY: Verification (admin approval)
        if (!$company->is_verified) {
            $mandatoryStepsComplete = false;
            $missingSteps[] = 'Complete Verification (pending admin approval)';
        }

        // V-AUDIT-MODULE18-MEDIUM: Block skip if mandatory steps are incomplete
        if (!$mandatoryStepsComplete) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot skip onboarding. Please complete mandatory steps first.',
                'mandatory_steps_missing' => $missingSteps,
            ], 403);
        }

        // V-AUDIT-MODULE18-MEDIUM: If all mandatory steps are done, allow skip of optional steps
        $progress = CompanyOnboardingProgress::where('company_id', $company->id)->first();

        if ($progress) {
            $progress->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Onboarding skipped successfully. Mandatory steps are complete.',
        ], 200);
    }

    /**
     * V-AUDIT-MODULE18-MEDIUM: Added null check for orphaned users
     *
     * Get recommendations based on current state
     */
    public function getRecommendations(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        // V-AUDIT-MODULE18-MEDIUM: Handle orphaned company users
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company profile not found. Please contact support.',
            ], 404);
        }

        // FIX: Moved massive if/else block to Service
        $recommendations = $this->onboardingService->getRecommendations($company);

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
            'total' => count($recommendations),
        ], 200);
    }

    /*
     * REMOVED: private function getOnboardingSteps(Company $company)
     * REMOVED: private function checkProfileBasic, checkProfileBranding... etc
     * REASON: All moved to App\Services\CompanyOnboardingService
     */
}