// V-PHASE3-1730-085
<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Referral;
use App\Models\BonusTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate::Queue\InteractsWithQueue;
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
            return; // No pending referral found
        }
        
        $referrer = $referral->referrer;

        // 1. Mark referral as completed
        $referral->update(['status' => 'completed', 'completed_at' => now()]);

        // 2. Award one-time bonus to referrer
        $bonusAmount = setting('referral_bonus_amount', 500); // Configurable
        BonusTransaction::create([
            'user_id' => $referrer->id,
            'subscription_id' => $referrer->subscription->id, // Assumes referrer has one
            'type' => 'referral',
            'amount' => $bonusAmount,
            'description' => "Referral bonus for {$this->referredUser->username}",
        ]);
        
        // TODO: Credit this to referrer's wallet

        // 3. Recalculate referrer's multiplier
        $this->updateMultiplier($referrer);
        
        Log::info("Referral completed for {$this->referredUser->username}. Referrer: {$referrer->username}");
    }

    private function updateMultiplier(User $referrer)
    {
        $count = $referrer->referrals()->where('status', 'completed')->count();
        
        // Get tiers from settings (Configurable Logic Builder)
        $tiers = setting('referral_tiers', [
            ['count' => 0, 'multiplier' => 1.0],
            ['count' => 3, 'multiplier' => 1.5],
            ['count' => 5, 'multiplier' => 2.0],
            ['count' => 10, 'multiplier' => 2.5],
            ['count' => 20, 'multiplier' => 3.0],
        ]);

        $newMultiplier = 1.0;
        foreach ($tiers as $tier) {
            if ($count >= $tier['count']) {
                $newMultiplier = $tier['multiplier'];
            }
        }
        
        $referrer->subscription->update(['bonus_multiplier' => $newMultiplier]);
    }
}