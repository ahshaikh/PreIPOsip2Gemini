<?php
// V-FINAL-1730-345 (5-Tier Logic) | V-FINAL-1730-620 (JSON Decode Fix) | V-AUDIT-FIX-MODULE9

namespace App\Services;

use App\Models\User;
use App\Models\ReferralCampaign;
use Illuminate\Support\Facades\Log;

/**
 * ReferralService - 5-Tier Referral Multiplier System
 */
class ReferralService
{
    /**
     * Recalculate and update the referral multiplier for a user.
     */
    public function updateReferrerMultiplier(User $referrer)
    {
        $referrer->load('subscription.plan.configs');
        
        if (!$referrer->subscription) {
            Log::warning("ReferralService: User {$referrer->id} has no subscription.");
            return;
        }

        // 1. Check for Active Campaign Overrides
        $activeCampaign = ReferralCampaign::running()->first();
        
        if ($activeCampaign && $activeCampaign->multiplier > 1.0) {
            $referrer->subscription->update(['bonus_multiplier' => $activeCampaign->multiplier]);
            return;
        }

        // 2. Calculate Standard Tier
        $count = $referrer->referrals()->where('status', 'completed')->count();
        
        $config = $referrer->subscription->plan->configs
            ->where('config_key', 'referral_tiers')
            ->first();

        $defaultTiers = [
            ['count' => 0, 'multiplier' => 1.0],
            ['count' => 3, 'multiplier' => 1.5],
            ['count' => 5, 'multiplier' => 2.0],
            ['count' => 10, 'multiplier' => 2.5],
            ['count' => 20, 'multiplier' => 3.0],
        ];
        
        // --- MODULE 9 FIX: Robust JSON Parsing ---
        $tiers = $defaultTiers;
        
        if ($config && !empty($config->value)) {
            $decoded = json_decode($config->value, true);
            // Check if decode was successful AND is an array
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tiers = $decoded;
            } else {
                Log::warning("ReferralService: Invalid JSON in referral_tiers config for Plan {$referrer->subscription->plan->id}");
            }
        }
        // ----------------------------------------

        // 3. Determine Highest Applicable Multiplier
        usort($tiers, fn($a, $b) => $b['count'] <=> $a['count']);

        $newMultiplier = 1.0;
        foreach ($tiers as $tier) {
            if ($count >= (int)($tier['count'] ?? 0)) {
                $newMultiplier = (float)($tier['multiplier'] ?? 1.0);
                break;
            }
        }
        
        // 4. Update Database
        if ($referrer->subscription->bonus_multiplier != $newMultiplier) {
            $referrer->subscription->update(['bonus_multiplier' => $newMultiplier]);
            Log::info("ReferralService: User {$referrer->id} multiplier updated to {$newMultiplier}x (Referral Count: {$count})");
        }
    }
}