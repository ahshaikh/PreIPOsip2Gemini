<?php
// V-AUDIT-FIX-MODULE9 (New Listener)

namespace App\Listeners;

use App\Events\KycVerified;
use App\Models\Referral;
use App\Jobs\ProcessReferralJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessPendingReferralsOnKycVerify implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(KycVerified $event): void
    {
        $user = $event->user;
        Log::info("KYC Verified for User {$user->id}. Checking for pending referrals...");

        // 1. Find referrals where this user is the REFERRER and the status is pending
        // (Scenario: Referrer was unverified when the friend joined)
        $asReferrer = Referral::where('referrer_id', $user->id)
            ->where('status', 'pending')
            ->get();

        foreach ($asReferrer as $referral) {
            Log::info("Retrying pending referral #{$referral->id} (User is Referrer)");
            ProcessReferralJob::dispatch($referral->referred);
        }

        // 2. Find referrals where this user is the REFERRED (Referee) and status is pending
        // (Scenario: Referee joined but wasn't verified immediately)
        $asReferee = Referral::where('referred_id', $user->id)
            ->where('status', 'pending')
            ->get();

        foreach ($asReferee as $referral) {
            Log::info("Retrying pending referral #{$referral->id} (User is Referee)");
            ProcessReferralJob::dispatch($referral->referred);
        }
    }
}