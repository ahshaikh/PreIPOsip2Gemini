<?php
// V-FINAL-1730-345 (5-Tier Logic) | V-FINAL-1730-620 (JSON Decode Fix)

namespace App\Services;

use App\Models\User;
use App\Models\ReferralCampaign;
use Illuminate\Support\Facades\Log;

/**
 * ReferralService - 5-Tier Referral Multiplier System
 *
 * Manages the referral bonus multiplier system where users earn increased
 * bonuses based on the number of successful referrals they've made.
 *
 * ## 5-Tier Multiplier System
 *
 * | Tier | Referrals Required | Bonus Multiplier |
 * |------|-------------------|------------------|
 * | 1    | 0                 | 1.0x             |
 * | 2    | 3                 | 1.5x             |
 * | 3    | 5                 | 2.0x             |
 * | 4    | 10                | 2.5x             |
 * | 5    | 20                | 3.0x             |
 *
 * ## Multiplier Priority
 *
 * ```
 * 1. Active Campaign Override (if multiplier > 1.0)
 * 2. Plan-Specific Tier Configuration
 * 3. Default 5-Tier System
 * ```
 *
 * ## Campaign Overrides
 *
 * During promotional campaigns (ReferralCampaign), a global multiplier can
 * override the tier-based system. This is typically used for limited-time
 * bonus events (e.g., "Double Referral Bonus Weekend").
 *
 * ## Plan-Specific Tiers
 *
 * Each plan can define custom referral tiers via the `referral_tiers` config:
 * ```json
 * [
 *   {"count": 0, "multiplier": 1.0},
 *   {"count": 5, "multiplier": 2.0},
 *   {"count": 15, "multiplier": 4.0}
 * ]
 * ```
 *
 * ## Usage
 *
 * ```php
 * // Called after a successful referral completion
 * $referralService->updateReferrerMultiplier($referrerUser);
 * ```
 *
 * ## Impact on Bonuses
 *
 * The multiplier is applied in BonusCalculatorService to scale all
 * referral-related bonuses. For example, with 10 successful referrals
 * (2.5x multiplier), a base ₹100 referral bonus becomes ₹250.
 *
 * @package App\Services
 * @see \App\Models\ReferralCampaign
 * @see \App\Services\BonusCalculatorService
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
        
        // --- THE FIX ---
        // $config->value is a JSON string, we must decode it.
        $tiers = $config ? json_decode($config->value, true) : $defaultTiers;
        // ---------------

        // 3. Determine Highest Applicable Multiplier
        usort($tiers, fn($a, $b) => $b['count'] <=> $a['count']);

        $newMultiplier = 1.0;
        foreach ($tiers as $tier) {
            if ($count >= (int)$tier['count']) {
                $newMultiplier = (float)$tier['multiplier'];
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