<?php

namespace App\Http\Traits;

use App\Models\Company;
use App\Services\CompanyLifecycleService;
use Illuminate\Http\JsonResponse;

/**
 * PHASE 2 - TRAIT: ValidatesInvestments
 *
 * PURPOSE:
 * Reusable controller methods for investment validation.
 * Provides consistent error responses across API.
 *
 * USAGE:
 * use ValidatesInvestments;
 *
 * public function invest(Company $company) {
 *     if ($error = $this->validateInvestmentEligibility($company)) {
 *         return $error;
 *     }
 *     // Proceed with investment...
 * }
 */
trait ValidatesInvestments
{
    /**
     * Validate if company can accept investments
     *
     * @param Company $company
     * @return JsonResponse|null Error response or null if valid
     */
    protected function validateInvestmentEligibility(Company $company): ?JsonResponse
    {
        $lifecycleService = app(CompanyLifecycleService::class);

        if (!$lifecycleService->canAcceptInvestments($company)) {
            $reason = $lifecycleService->getBuyingBlockedReason($company);

            \Log::warning('Investment validation failed', [
                'company_id' => $company->id,
                'user_id' => auth()->id(),
                'lifecycle_state' => $company->lifecycle_state,
                'buying_enabled' => $company->buying_enabled,
                'reason' => $reason,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Investment not allowed',
                'reason' => $reason,
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'lifecycle_state' => $company->lifecycle_state,
                    'can_accept_investments' => false,
                ],
            ], 403);
        }

        return null; // Valid
    }

    /**
     * Validate if user can invest (KYC, account status, etc.)
     *
     * @return JsonResponse|null Error response or null if valid
     */
    protected function validateUserInvestmentEligibility(): ?JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication required',
            ], 401);
        }

        // Check KYC verification
        if (!$user->is_kyc_verified) {
            \Log::warning('Investment blocked - KYC not verified', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'KYC verification required',
                'reason' => 'You must complete KYC verification before investing',
                'redirect' => '/kyc',
            ], 403);
        }

        // Check account status
        if ($user->status !== 'active') {
            \Log::warning('Investment blocked - account not active', [
                'user_id' => $user->id,
                'user_status' => $user->status,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Account not active',
                'reason' => 'Your account is not active. Please contact support.',
            ], 403);
        }

        // Check if blocked
        if ($user->is_blocked) {
            \Log::critical('Investment attempt by blocked user', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Account blocked',
                'reason' => 'Your account has been blocked. Please contact support.',
            ], 403);
        }

        return null; // Valid
    }

    /**
     * Validate both user and company eligibility
     *
     * @param Company $company
     * @return JsonResponse|null Error response or null if valid
     */
    protected function validateFullInvestmentEligibility(Company $company): ?JsonResponse
    {
        // Check user eligibility first
        if ($error = $this->validateUserInvestmentEligibility()) {
            return $error;
        }

        // Check company eligibility
        if ($error = $this->validateInvestmentEligibility($company)) {
            return $error;
        }

        return null; // Both valid
    }

    /**
     * Get investment eligibility status for frontend
     *
     * @param Company $company
     * @return array Status information
     */
    protected function getInvestmentEligibilityStatus(Company $company): array
    {
        $lifecycleService = app(CompanyLifecycleService::class);
        $user = auth()->user();

        $canInvest = $lifecycleService->canAcceptInvestments($company);
        $userEligible = $user && $user->is_kyc_verified && $user->status === 'active' && !$user->is_blocked;

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'lifecycle_state' => $company->lifecycle_state,
                'can_accept_investments' => $canInvest,
                'buying_enabled' => $company->buying_enabled,
                'is_suspended' => $company->lifecycle_state === 'suspended',
                'blocked_reason' => $canInvest ? null : $lifecycleService->getBuyingBlockedReason($company),
            ],
            'user' => [
                'can_invest' => $userEligible,
                'is_kyc_verified' => $user?->is_kyc_verified ?? false,
                'account_status' => $user?->status ?? 'guest',
                'is_blocked' => $user?->is_blocked ?? false,
            ],
            'overall' => [
                'can_proceed' => $canInvest && $userEligible,
                'reason' => $this->getBlockReason($company, $user),
            ],
        ];
    }

    /**
     * Get blocking reason for display
     *
     * @param Company $company
     * @param mixed $user
     * @return string|null
     */
    protected function getBlockReason(Company $company, $user): ?string
    {
        $lifecycleService = app(CompanyLifecycleService::class);

        // Check company first
        if (!$lifecycleService->canAcceptInvestments($company)) {
            return $lifecycleService->getBuyingBlockedReason($company);
        }

        // Check user
        if (!$user) {
            return 'Please login to invest';
        }

        if (!$user->is_kyc_verified) {
            return 'Complete KYC verification to invest';
        }

        if ($user->status !== 'active') {
            return 'Your account is not active';
        }

        if ($user->is_blocked) {
            return 'Your account has been blocked';
        }

        return null; // No blocking reason
    }
}
