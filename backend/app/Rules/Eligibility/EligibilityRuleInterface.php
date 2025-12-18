<?php

namespace App\Rules\Eligibility;

use App\Models\User;
use App\Models\Plan;

/**
 * Interface EligibilityRuleInterface
 * * [AUDIT FIX]: Standardizes how all eligibility rules are structured.
 */
interface EligibilityRuleInterface
{
    /**
     * Check if the user meets the specific rule criteria.
     *
     * @return bool
     */
    public function validate(User $user, Plan $plan): bool;

    /**
     * Return the error message if validation fails.
     *
     * @return string
     */
    public function getErrorMessage(): string;
}