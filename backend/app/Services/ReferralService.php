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

    /**
     * V-AUDIT-MODULE9-005 (LOW): Calculate referral bonus amount and description.
     *
     * Abstraction Fix:
     * - Previous: Bonus calculation logic was hardcoded in ProcessReferralJob
     * - Problem: Tight coupling, difficult to test, logic duplicated if needed elsewhere
     * - Solution: Move calculation to ReferralService as a reusable method
     *
     * Benefits:
     * - Single source of truth for referral bonus calculations
     * - Easy to unit test bonus logic
     * - Can be reused in other contexts (admin bonus preview, reports, etc.)
     * - Clear separation of concerns (Job = orchestration, Service = business logic)
     *
     * @param User $referredUser The user who was referred
     * @param ReferralCampaign|null $campaign The campaign to use for bonus calculation
     * @return array ['amount' => float, 'description' => string]
     */
    public function calculateReferralBonus(User $referredUser, ?ReferralCampaign $campaign = null): array
    {
        // Base bonus from settings
        $baseBonus = (float) setting('referral_bonus_amount', 500);

        // Add campaign bonus if applicable
        $campaignBonus = $campaign?->bonus_amount ?? 0;
        $finalBonus = $baseBonus + $campaignBonus;

        // Build description
        $description = "Referral Bonus: {$referredUser->username}";
        if ($campaign) {
            $description .= " (Campaign: {$campaign->name})";
        }

        Log::debug("ReferralService: Calculated bonus for {$referredUser->username}", [
            'base_bonus' => $baseBonus,
            'campaign_bonus' => $campaignBonus,
            'final_bonus' => $finalBonus,
            'campaign' => $campaign?->name ?? 'None'
        ]);

        return [
            'amount' => $finalBonus,
            'description' => $description,
        ];
    }
}