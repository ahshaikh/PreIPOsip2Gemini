<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyLifecycleService;

/**
 * PHASE 2 - POLICY: InvestmentPolicy
 *
 * PURPOSE:
 * Authorization policy for investment operations.
 * Enforces company lifecycle state restrictions on buying.
 *
 * CRITICAL RULE:
 * Buying is ONLY allowed when:
 * 1. Company lifecycle_state is 'live_investable' OR 'live_fully_disclosed'
 * 2. Company buying_enabled flag is true
 * 3. Company is NOT suspended
 *
 * INTEGRATION:
 * - Used by controllers via $this->authorize('invest', $company)
 * - Used by Blade/React via @can('invest', $company)
 * - Used by middleware via can:invest,company route protection
 *
 * SECURITY:
 * - Hard block at authorization layer (not just UI)
 * - Clear error messages for investors
 * - Logged denial attempts for fraud detection
 */
class InvestmentPolicy
{
    protected CompanyLifecycleService $lifecycleService;

    public function __construct(CompanyLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Determine if user can invest in company
     *
     * CRITICAL GUARD: This is the primary buying enforcement point
     *
     * @param User $user
     * @param Company $company
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function invest(User $user, Company $company)
    {
        // GUARD: Check if company can accept investments
        if (!$this->lifecycleService->canAcceptInvestments($company)) {
            $reason = $this->lifecycleService->getBuyingBlockedReason($company);

            \Log::warning('Investment attempt blocked - company not investable', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'lifecycle_state' => $company->lifecycle_state,
                'buying_enabled' => $company->buying_enabled,
                'reason' => $reason,
                'ip_address' => request()->ip(),
            ]);

            return \Illuminate\Auth\Access\Response::deny($reason ?? 'This company is not currently accepting investments');
        }

        // GUARD: User must have completed KYC
        if (!$user->is_kyc_verified) {
            \Log::warning('Investment attempt blocked - KYC not verified', [
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);

            return \Illuminate\Auth\Access\Response::deny('You must complete KYC verification before investing');
        }

        // GUARD: User account must be active
        if ($user->status !== 'active') {
            \Log::warning('Investment attempt blocked - account not active', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'user_status' => $user->status,
            ]);

            return \Illuminate\Auth\Access\Response::deny('Your account is not active. Please contact support.');
        }

        // GUARD: Check if user is blocked/banned
        if ($user->is_blocked) {
            \Log::critical('Investment attempt by blocked user', [
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);

            return \Illuminate\Auth\Access\Response::deny('Your account has been blocked. Please contact support.');
        }

        // All checks passed - allow investment
        \Log::info('Investment authorized', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'lifecycle_state' => $company->lifecycle_state,
        ]);

        return true;
    }

    /**
     * Determine if user can view company investment details
     *
     * NOTE: Different from invest - viewing is allowed even if buying is not
     *
     * @param User|null $user
     * @param Company $company
     * @return bool
     */
    public function viewInvestmentDetails(?User $user, Company $company): bool
    {
        // Public companies (Tier 1+) can be viewed by anyone
        if (in_array($company->lifecycle_state, ['live_limited', 'live_investable', 'live_fully_disclosed'])) {
            return true;
        }

        // Suspended companies show limited info with warning
        if ($company->lifecycle_state === 'suspended') {
            return true; // Allow viewing but show warning banner
        }

        // Draft companies only visible to admins and company owners
        if ($user && $user->role === 'admin') {
            return true;
        }

        // Company representatives can view their own draft
        if ($user && $user->company_id === $company->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can create new subscription to company
     *
     * Similar to invest but checks existing subscriptions
     *
     * @param User $user
     * @param Company $company
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function subscribe(User $user, Company $company)
    {
        // First check if investment is allowed at all
        $investCheck = $this->invest($user, $company);
        if ($investCheck !== true) {
            return $investCheck; // Return denial reason
        }

        // Additional subscription-specific checks could go here
        // For example: max subscriptions per user, plan eligibility, etc.

        return true;
    }

    /**
     * Determine if user can make one-time investment
     *
     * @param User $user
     * @param Company $company
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function buyShares(User $user, Company $company)
    {
        // Same as invest check
        return $this->invest($user, $company);
    }

    /**
     * Determine if user can add funds to existing subscription
     *
     * @param User $user
     * @param Company $company
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function topUp(User $user, Company $company)
    {
        // Check if company still accepting investments
        $investCheck = $this->invest($user, $company);
        if ($investCheck !== true) {
            return $investCheck;
        }

        // User must have existing subscription to top up
        $hasSubscription = $user->subscriptions()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->exists();

        if (!$hasSubscription) {
            return \Illuminate\Auth\Access\Response::deny('You do not have an active subscription to top up');
        }

        return true;
    }
}
