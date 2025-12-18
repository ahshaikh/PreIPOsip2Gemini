<?php

namespace App\Rules\Eligibility;

use App\Models\User;
use App\Models\Plan;
use App\Enums\KycStatus;

class KycVerifiedRule implements EligibilityRuleInterface
{
    public function validate(User $user, Plan $plan): bool
    {
        // Check if the plan specifically requires KYC
        $config = $plan->eligibility_config;
        if (!($config['kyc_required'] ?? true)) {
            return true;
        }

        return $user->kyc_status === KycStatus::VERIFIED->value;
    }

    public function getErrorMessage(): string
    {
        return "Complete KYC verification to unlock this plan.";
    }
}