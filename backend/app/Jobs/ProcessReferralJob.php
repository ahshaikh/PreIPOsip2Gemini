<?php
// V-FINAL-1730-273 (Campaign Logic Added)

namespace App\Jobs;

use App\Models\User;
use App\Models\Referral;
use App\Models\BonusTransaction;
use App\Models\ReferralCampaign; // <-- IMPORT
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessReferralJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $referredUser)
    {
    }

    public function handle(): void
    {
        $referral = Referral::where('referred_id', $this->referredUser->id)
                            ->where('status', 'pending')
                            ->first();

        if (!$referral) {
            return; 
        }
        
        $referrer = $referral->referrer->load('subscription.plan.configs');
        
        if (!$referrer->subscription) {
            Log::warning("Referrer {$referrer->id} has no subscription.");
            return;
        }

        // 1. Mark referral as completed
        $referral->update(['status' => 'completed', 'completed_at' => now()]);

        // --- CAMPAIGN LOGIC START ---
        // Check if a campaign is active right now
        $activeCampaign = ReferralCampaign::running()->first();
        
        $baseBonus = setting('referral_bonus_amount', 500);
        $finalBonus = $baseBonus;
        $description = "Referral bonus for {$this->referredUser->username}";

        // If campaign active, add extra bonus
        if ($activeCampaign) {
            $finalBonus += $activeCampaign->bonus_amount;
            $description .= " (Includes '{$activeCampaign->name}' Bonus)";
        }
        // --- CAMPAIGN LOGIC END ---

        // 2. Award Bonus
        BonusTransaction::create([
            'user_id' => $referrer->id,
            'subscription_id' => $referrer->subscription->id,
            'type' => 'referral',
            'amount' => $finalBonus,
            'description' => $description,
        ]);
        
        // Credit Wallet
        $referrer->wallet->increment('balance', $finalBonus);

        // 3. Update Multiplier
        $this->updateMultiplier($referrer, $activeCampaign);
        
        Log::info("Referral processed. Bonus: {$finalBonus}");
    }

    private function updateMultiplier(User $referrer, ?ReferralCampaign $campaign)
    {
        // If campaign is active, it overrides standard tiers
        if ($campaign && $campaign->multiplier > 1.0) {
            $referrer->subscription->update(['bonus_multiplier' => $campaign->multiplier]);
            Log::info("Referrer {$referrer->id} multiplier set to CAMPAIGN level: {$campaign->multiplier}x");
            return;
        }

        // Otherwise, use standard plan tiers
        $count = $referrer->referrals()->where('status', 'completed')->count();
        
        $config = $referrer->subscription->plan->configs
            ->where('config_key', 'referral_tiers')
            ->first();

        $defaultTiers = [
            ['count' => 0, 'multiplier' => 1.0],
            ['count' => 3, 'multiplier' => 1.5],
            ['count' => 5, 'multiplier' => 2.0],
        ];
        
        $tiers = $config ? $config->value : $defaultTiers;

        $newMultiplier = 1.0;
        foreach ($tiers as $tier) {
            if ($count >= $tier['count']) {
                $newMultiplier = $tier['multiplier'];
            }
        }
        
        $referrer->subscription->update(['bonus_multiplier' => $newMultiplier]);
    }
}