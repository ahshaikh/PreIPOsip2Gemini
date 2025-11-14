<?php
// V-FINAL-1730-378 (Created) | V-FINAL-1730-456 (WalletService Refactor)

namespace App\Jobs;

use App\Models\User;
use App\Models\Referral;
use App\Models\BonusTransaction;
use App\Models\ReferralCampaign;
use App\Services\ReferralService;
use App\Services\WalletService; // <-- IMPORT
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessReferralJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $referredUser)
    {
    }

    public function handle(ReferralService $referralService, WalletService $walletService): void
    {
        $referral = Referral::where('referred_id', $this->referredUser->id)
                            ->where('status', 'pending')
                            ->first();

        if (!$referral) return;
        
        $referrer = $referral->referrer->load('subscription');
        if (!$referrer->subscription) return;

        $activeCampaign = ReferralCampaign::running()->first();

        DB::transaction(function () use ($referral, $referrer, $referralService, $walletService, $activeCampaign) {
            
            $referral->update([
                'status' => 'completed',
                'completed_at' => now(),
                'referral_campaign_id' => $activeCampaign?->id
            ]);

            $baseBonus = setting('referral_bonus_amount', 500);
            $finalBonus = $baseBonus + ($activeCampaign?->bonus_amount ?? 0);
            $description = "Referral Bonus: {$this->referredUser->username}";
            if ($activeCampaign) $description .= " (Campaign: {$activeCampaign->name})";

            // 3. Create Bonus Transaction
            $bonus = BonusTransaction::create([
                'user_id' => $referrer->id,
                'subscription_id' => $referrer->subscription->id,
                'type' => 'referral',
                'amount' => $finalBonus,
                'description' => $description,
            ]);
            
            // 4. Credit Wallet (Using Service)
            $walletService->deposit(
                $referrer,
                $finalBonus,
                'bonus_credit',
                $description,
                $bonus
            );

            // 5. Update Multiplier Tier
            $referralService->updateReferrerMultiplier($referrer);
        });
        
        Log::info("Referral processed for {$this->referredUser->username}");
    }
}