<?php
// V-PHASE2-ELIGIBILITY-1208 (Created)

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * [P1 FIX]: Canonical Plan Eligibility Service
 *
 * PURPOSE: Determines if a user is eligible to SUBSCRIBE to a plan.
 *
 * CONSOLIDATION HISTORY:
 * - Removed: app/Services/Plans/PlanEligibilityService.php (duplicate, unused)
 * - Removed: app/Rules/Eligibility/* (only used by removed duplicate)
 * - Kept: This service (actively used in SubscriptionController)
 * - Kept: app/Http/Middleware/CheckPlanEligibility.php (different purpose - product access control)
 *
 * IMPORTANT: This is the ONLY service for plan subscription eligibility.
 * Do NOT create duplicate services. Extend this one if new rules are needed.
 *
 * INVARIANT: Single source of truth for plan eligibility logic.
 */
class PlanEligibilityService
{
    /**
     * Check if user is eligible to subscribe to a plan.
     * Returns array with [eligible: bool, errors: array]
     */
    public function checkEligibility(User $user, Plan $plan): array
    {
        $errors = [];

        // Get eligibility config from plan_configs
        $eligibilityConfig = $plan->getConfig('eligibility_config', []);

        if (empty($eligibilityConfig)) {
            // No eligibility rules configured, user is eligible by default
            return ['eligible' => true, 'errors' => []];
        }

        // 1. Check Age Restrictions
        $ageErrors = $this->checkAgeRestrictions($user, $eligibilityConfig);
        $errors = array_merge($errors, $ageErrors);

        // 2. Check KYC Requirements
        $kycErrors = $this->checkKycRequirements($user, $eligibilityConfig);
        $errors = array_merge($errors, $kycErrors);

        // 3. Check Document Requirements
        $docErrors = $this->checkDocumentRequirements($user, $eligibilityConfig);
        $errors = array_merge($errors, $docErrors);

        // 4. Check Country Restrictions
        $countryErrors = $this->checkCountryRestrictions($user, $eligibilityConfig);
        $errors = array_merge($errors, $countryErrors);

        // 5. Check Income Requirements
        $incomeErrors = $this->checkIncomeRequirements($user, $eligibilityConfig);
        $errors = array_merge($errors, $incomeErrors);

        return [
            'eligible' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check age restrictions.
     */
    protected function checkAgeRestrictions(User $user, array $config): array
    {
        $errors = [];

        if (!isset($config['min_age']) && !isset($config['max_age'])) {
            return $errors;
        }

        // Calculate user age from date_of_birth
        if (!$user->date_of_birth) {
            $errors[] = 'Date of birth not provided. Please complete your profile.';
            return $errors;
        }

        $age = \Carbon\Carbon::parse($user->date_of_birth)->age;

        if (isset($config['min_age']) && $age < $config['min_age']) {
            $errors[] = "You must be at least {$config['min_age']} years old to subscribe to this plan. Your age: {$age}.";
        }

        if (isset($config['max_age']) && $age > $config['max_age']) {
            $errors[] = "Maximum age for this plan is {$config['max_age']} years. Your age: {$age}.";
        }

        return $errors;
    }

    /**
     * Check KYC verification requirements.
     */
    protected function checkKycRequirements(User $user, array $config): array
    {
        $errors = [];

        if (isset($config['kyc_required']) && $config['kyc_required'] === true) {
            if ($user->kyc_status !== 'verified') {
                $errors[] = 'KYC verification is required for this plan. Please complete your KYC verification.';
            }
        }

        return $errors;
    }

    /**
     * Check document requirements (PAN, Bank Account).
     */
    protected function checkDocumentRequirements(User $user, array $config): array
    {
        $errors = [];

        // Check PAN requirement
        if (isset($config['require_pan']) && $config['require_pan'] === true) {
            if (empty($user->pan_number) || $user->pan_verified !== true) {
                $errors[] = 'Valid PAN card is required for this plan. Please provide and verify your PAN.';
            }
        }

        // Check Bank Account requirement
        if (isset($config['require_bank_account']) && $config['require_bank_account'] === true) {
            // Check if user has at least one verified bank account
            $hasBankAccount = $user->bankAccounts()->where('is_verified', true)->exists();
            if (!$hasBankAccount) {
                $errors[] = 'Verified bank account is required for this plan. Please link and verify your bank account.';
            }
        }

        return $errors;
    }

    /**
     * Check country restrictions (whitelist/blacklist).
     */
    protected function checkCountryRestrictions(User $user, array $config): array
    {
        $errors = [];

        // Get user's country and normalize
        $userCountry = strtoupper($user->country ?? '');

        if (empty($userCountry)) {
            // If no country configured and rules exist, require it
            if (!empty($config['countries_allowed']) || !empty($config['countries_blocked'])) {
                $errors[] = 'Country information is required. Please complete your profile.';
            }
            return $errors;
        }
        
        // Check whitelist
        if (isset($config['countries_allowed']) && is_array($config['countries_allowed']) && !empty($config['countries_allowed'])) {
            if (!in_array($userCountry, $config['countries_allowed'])) {
                $allowedList = implode(', ', $config['countries_allowed']);
                $errors[] = "This plan is only available in: {$allowedList}. Your country: {$userCountry}.";
            }
        }

        // Check blacklist
        if (isset($config['countries_blocked']) && is_array($config['countries_blocked']) && !empty($config['countries_blocked'])) {
            if (in_array($userCountry, $config['countries_blocked'])) {
                $errors[] = "This plan is not available in your country ({$userCountry}).";
            }
        }

        return $errors;
    }

    /**
     * Check income requirements.
     */
    protected function checkIncomeRequirements(User $user, array $config): array
    {
        $errors = [];

        // Check minimum monthly income
        if (isset($config['min_monthly_income']) && $config['min_monthly_income'] > 0) {
            if (!$user->monthly_income || $user->monthly_income < $config['min_monthly_income']) {
                $required = number_format($config['min_monthly_income'], 2);
                $errors[] = "Minimum monthly income of ₹{$required} is required for this plan.";
            }
        }

        // Check employment requirement
        if (isset($config['employment_required']) && $config['employment_required'] === true) {
            if (empty($user->employment_status) || $user->employment_status === 'unemployed') {
                $errors[] = 'Employment is required for this plan. Please update your employment status.';
            }
        }

        return $errors;
    }

    /**
     * Get a user-friendly summary of eligibility rules for a plan.
     */
    public function getEligibilitySummary(Plan $plan): array
    {
        $config = $plan->getConfig('eligibility_config', []);

        if (empty($config)) {
            return ['message' => 'No special eligibility requirements for this plan.'];
        }

        $requirements = [];

        // Age requirements
        if (isset($config['min_age']) || isset($config['max_age'])) {
            $ageText = '';
            if (isset($config['min_age']) && isset($config['max_age'])) {
                $ageText = "Age: {$config['min_age']}-{$config['max_age']} years";
            } elseif (isset($config['min_age'])) {
                $ageText = "Minimum age: {$config['min_age']} years";
            } elseif (isset($config['max_age'])) {
                $ageText = "Maximum age: {$config['max_age']} years";
            }
            $requirements[] = $ageText;
        }

        // KYC requirements
        if (isset($config['kyc_required']) && $config['kyc_required'] === true) {
            $requirements[] = 'KYC verification required';
        }

        // Document requirements
        if (isset($config['require_pan']) && $config['require_pan'] === true) {
            $requirements[] = 'Valid PAN card required';
        }
        if (isset($config['require_bank_account']) && $config['require_bank_account'] === true) {
            $requirements[] = 'Verified bank account required';
        }

        // Geography requirements
        if (isset($config['countries_allowed']) && !empty($config['countries_allowed'])) {
            $countries = implode(', ', $config['countries_allowed']);
            $requirements[] = "Available only in: {$countries}";
        }
        if (isset($config['countries_blocked']) && !empty($config['countries_blocked'])) {
            $countries = implode(', ', $config['countries_blocked']);
            $requirements[] = "Not available in: {$countries}";
        }

        // Income requirements
        if (isset($config['min_monthly_income']) && $config['min_monthly_income'] > 0) {
            $income = number_format($config['min_monthly_income'], 2);
            $requirements[] = "Minimum monthly income: ₹{$income}";
        }
        if (isset($config['employment_required']) && $config['employment_required'] === true) {
            $requirements[] = 'Employment required';
        }

        return [
            'requirements' => $requirements,
            'count' => count($requirements)
        ];
    }
}
