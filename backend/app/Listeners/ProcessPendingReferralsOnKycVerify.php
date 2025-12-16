<?php
// V-AUDIT-MODULE9-001 (CRITICAL): Fixed directory typo - Moved from Listners to Listeners
// V-AUDIT-MODULE9-004 (MEDIUM): Added chunking for scalability with influencers (500+ pending referrals)

namespace App\Listeners;

use App\Events\KycVerified;
use App\Models\Referral;
use App\Jobs\ProcessReferralJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * V-AUDIT-MODULE9-001 (CRITICAL): Directory Typo Fix.
 *
 * Previous Issue:
 * - File was located in app/Listners/ (typo)
 * - Laravel's autoloader expects app/Listeners/
 * - This caused the KYC verification event to fail silently
 *
 * Fix: Moved to correct directory with proper namespace
 *
 * V-AUDIT-MODULE9-004 (MEDIUM): Scalability Fix for Influencers.
 *
 * Previous Issue:
 * - Used get() to load all pending referrals into memory
 * - For influencers with 500+ pending referrals, this triggered 500 synchronous job dispatches
 * - Caused memory spikes and request timeouts
 *
 * Fix: Use chunk() to process referrals in batches of 100
 */
class ProcessPendingReferralsOnKycVerify implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param KycVerified $event
     * @return void
     */
    public function handle(KycVerified $event): void
    {
        $user = $event->user;
        Log::info("KYC Verified for User {$user->id}. Checking for pending referrals...");

        // V-AUDIT-MODULE9-004: Use chunk() to prevent memory exhaustion with large referral counts
        // 1. Find referrals where this user is the REFERRER and the status is pending
        // (Scenario: Referrer was unverified when the friend joined)
        Referral::where('referrer_id', $user->id)
            ->where('status', 'pending')
            ->chunk(100, function ($referrals) {
                foreach ($referrals as $referral) {
                    Log::info("Retrying pending referral #{$referral->id} (User is Referrer)");
                    ProcessReferralJob::dispatch($referral->referred);
                }
            });

        // V-AUDIT-MODULE9-004: Use chunk() for referee referrals as well
        // 2. Find referrals where this user is the REFERRED (Referee) and status is pending
        // (Scenario: Referee joined but wasn't verified immediately)
        Referral::where('referred_id', $user->id)
            ->where('status', 'pending')
            ->chunk(100, function ($referrals) {
                foreach ($referrals as $referral) {
                    Log::info("Retrying pending referral #{$referral->id} (User is Referee)");
                    ProcessReferralJob::dispatch($referral->referred);
                }
            });

        Log::info("Completed processing pending referrals for User {$user->id}");
    }
}