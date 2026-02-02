<?php
// V-PHASE3-1730-085 (Created) | V-FINAL-1730-378 | V-FINAL-1730-483 (KYC Check)
// V-PHASE4-LEDGER (Ledger Integration + TDS Compliance)

namespace App\Jobs;

use App\Models\User;
use App\Models\Referral;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\ReferralCampaign;
use App\Services\ReferralService;
use App\Services\WalletService;
use App\Services\TdsCalculationService;
use App\Services\DoubleEntryLedgerService;
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

    /**
     * Execute the job.
     *
     * [G.22 FIX]: Added idempotency protection to prevent double referral bonuses
     *
     * PHASE 4 LEDGER INTEGRATION:
     * Uses two-step flow for proper bonus accounting:
     *   Step 1: recordBonusWithTds() - DEBIT MARKETING_EXPENSE, CREDIT BONUS_LIABILITY, CREDIT TDS_PAYABLE
     *   Step 2: deposit('bonus_credit') - DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
     */
    public function handle(
        ReferralService $referralService,
        WalletService $walletService,
        TdsCalculationService $tdsService,
        DoubleEntryLedgerService $ledgerService,
        \App\Services\IdempotencyService $idempotency
    ): void {
        // Check if referral module is enabled
        if (!setting('referral_enabled', true)) {
            Log::info("Referral processing skipped: referral module is disabled");
            return;
        }

        $referral = Referral::where('referred_id', $this->referredUser->id)
                            ->where('status', 'pending')
                            ->first();

        if (!$referral) return;

        $referrer = $referral->referrer->load('subscription', 'kyc');

        if (!$referrer->subscription) {
            Log::warning("Referrer {$referrer->id} has no subscription.");
            return;
        }

        // --- KYC CHECK (FSD-SEC-012) ---
        if (setting('referral_kyc_required', true)) {
            if ($referrer->kyc->status !== 'verified' || $this->referredUser->kyc->status !== 'verified') {
                Log::info("Referral #{$referral->id} paused. Referrer or Referee KYC not verified.");
                // We don't fail, we just wait. The job can be re-dispatched when KYC is approved.
                return;
            }
        }
        // --- END KYC CHECK ---

        $idempotencyKey = "referral_processing:{$referral->id}";

        // [G.22]: Check if already processed to prevent double bonus credit
        if ($idempotency->isAlreadyExecuted($idempotencyKey, self::class)) {
            Log::info("Referral #{$referral->id} already processed. Skipping to prevent double bonus credit.");
            return;
        }

        // [G.22]: Execute with idempotency protection
        $idempotency->executeOnce($idempotencyKey, function () use ($referral, $referrer, $referralService, $walletService) {
            // V-AUDIT-MODULE9-002 (HIGH): Check if campaign was already locked at signup.
            // If referral_campaign_id is null, it means this is an old referral created before the fix.
            // In that case, use the currently active campaign as a fallback.
            $activeCampaign = ReferralCampaign::running()->first();

            DB::transaction(function () use ($referral, $referrer, $referralService, $walletService, $activeCampaign) {

                // V-AUDIT-MODULE9-002: Determine which campaign to use for bonus calculation
                // Priority: Use campaign locked at signup (if exists), fallback to current campaign
                $campaignToUse = null;

                if ($referral->referral_campaign_id) {
                    // Campaign was locked at signup - use that one (even if expired now)
                    $campaignToUse = ReferralCampaign::find($referral->referral_campaign_id);
                    Log::info("Using campaign locked at signup: {$campaignToUse?->name}");
                } else {
                    // Old referral without locked campaign - use current active campaign
                    $campaignToUse = $activeCampaign;
                    Log::info("No locked campaign, using current active campaign: {$campaignToUse?->name}");
                }

                // 1. Mark referral as completed
                // V-AUDIT-MODULE9-002: Only update campaign_id if it wasn't already set at signup
                $updateData = [
                    'status' => 'completed',
                    'completed_at' => now(),
                ];

                // Only set campaign_id if it wasn't locked at signup
                if (!$referral->referral_campaign_id && $activeCampaign) {
                    $updateData['referral_campaign_id'] = $activeCampaign->id;
                }

                $referral->update($updateData);

                // 2. Calculate One-Time Cash Bonus
                // V-AUDIT-MODULE9-005 (LOW): Delegate bonus calculation to ReferralService
                // This improves testability and follows separation of concerns principle
                $bonusData = $referralService->calculateReferralBonus($this->referredUser, $campaignToUse);
                $grossBonus = $bonusData['amount'];
                $description = $bonusData['description'];

                // 3. Calculate TDS on referral bonus
                $tdsResult = $tdsService->calculate($grossBonus, 'referral');

                // 4. Create Bonus Transaction (with TDS tracking)
                $bonus = BonusTransaction::create([
                    'user_id' => $referrer->id,
                    'subscription_id' => $referrer->subscription->id,
                    'type' => 'referral',
                    'amount' => $tdsResult->grossAmount,
                    'tds_deducted' => $tdsResult->tdsAmount,
                    'base_amount' => $grossBonus,
                    'multiplier_applied' => 1.0,
                    'description' => $description,
                ]);

                // 5. PHASE 4: Record bonus accrual in ledger FIRST
                // DEBIT MARKETING_EXPENSE (gross), CREDIT BONUS_LIABILITY (net), CREDIT TDS_PAYABLE (tds)
                $ledgerService->recordBonusWithTds(
                    $bonus,
                    $tdsResult->grossAmount,
                    $tdsResult->tdsAmount
                );

                // 6. Transfer to wallet
                // This triggers recordBonusToWallet(): DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
                $walletService->deposit(
                    $referrer,
                    $tdsResult->netAmount,
                    'bonus_credit',
                    $tdsResult->getDescription($description),
                    $bonus
                );

                // 7. Update Multiplier Tier (Using Service)
                $referralService->updateReferrerMultiplier($referrer);

                Log::info("Referral processed with TDS", [
                    'referrer_id' => $referrer->id,
                    'referred_id' => $this->referredUser->id,
                    'campaign' => $campaignToUse?->name ?? 'None',
                    'gross_amount' => $tdsResult->grossAmount,
                    'tds_amount' => $tdsResult->tdsAmount,
                    'net_amount' => $tdsResult->netAmount,
                ]);
            });

            Log::info("Referral processed for {$this->referredUser->username}");

        }, [
            'job_class' => self::class,
            'input_data' => [
                'referral_id' => $referral->id,
                'referrer_id' => $referrer->id,
                'referred_user_id' => $this->referredUser->id,
            ],
        ]);
    }
}