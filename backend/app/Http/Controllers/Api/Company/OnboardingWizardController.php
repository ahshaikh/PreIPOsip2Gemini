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
     * Get onboarding progress and steps
     */
    public function getProgress(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Use service to get/create progress
        $progress = $this->onboardingService->getOrCreateProgress($company);
        
        // FIX: Use service to get steps logic
        $steps = $this->onboardingService->getOnboardingSteps($company);

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