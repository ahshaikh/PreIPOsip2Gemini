<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-DYNAMIC-DISCLAIMERS
 */

namespace App\Services\Legal;

use App\Models\Plan;

class DisclaimerService
{
    /**
     * Get the mandatory disclaimer based on the plan's risk profile.
     * [AUDIT FIX]: Centralizes legal language to ensure compliance parity.
     */
    public function getForPlan(Plan $plan): string
    {
        $base = "Investment in securities market are subject to market risks.";
        
        $specific = match($plan->asset_class) {
            'equity' => " Equity investments carry high risk and involve capital loss potential.",
            'debt'   => " Debt instruments are subject to interest rate and credit risks.",
            'startup' => " Early-stage investing is highly speculative with illiquidity risk.",
            default  => ""
        };

        return $base . $specific . " Read all related documents carefully before investing.";
    }
}