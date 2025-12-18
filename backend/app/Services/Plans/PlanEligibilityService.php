<?php

namespace App\Services\Plans;

use App\Models\User;
use App\Models\Plan;
use App\Rules\Eligibility\KycVerifiedRule;
use App\Rules\Eligibility\MinimumAgeRule;
use Illuminate\Support\Facades\Cache;

/**
 * PlanEligibilityService
 * * [AUDIT FIX]: Consolidates logic to prevent "Logic Drift" between UI and Server.
 * * Implements tagging cache to speed up the Explore Plans page.
 */
class PlanEligibilityService
{
    protected array $rules = [];

    public function __construct()
    {
        // Register active rules
        $this->rules = [
            new KycVerifiedRule(),
            new MinimumAgeRule(),
            // Add more rules here (GeographyRule, IncomeRule, etc.)
        ];
    }

    /**
     * Evaluate eligibility for a user and plan.
     */
    public function check(User $user, Plan $plan): array
    {
        $cacheKey = "eligibility_u{$user->id}_p{$plan->id}";

        // [AUDIT FIX]: 60-second cache to prevent redundant DB lookups on dashboard
        return Cache::remember($cacheKey, 60, function () use ($user, $plan) {
            $failedRules = [];

            foreach ($this->rules as $rule) {
                if (!$rule->validate($user, $plan)) {
                    $failedRules[] = $rule->getErrorMessage();
                }
            }

            return [
                'eligible' => empty($failedRules),
                'reasons' => $failedRules,
                'status' => empty($failedRules) ? 'eligible' : 'ineligible'
            ];
        });
    }
}