<?php
// V-PHASE3-1730-085 (Created) | V-FINAL-1730-378 | V-FINAL-1730-483 (KYC Check)
// V-PHASE4-LEDGER (Ledger Integration + TDS Compliance)
// V-FIX-CLOSURE-SCOPE (Resolved undefined $tdsService / $ledgerService)

namespace App\Jobs;

use App\Models\User;
use App\Models\Referral;
use App\Models\BonusTransaction;
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

        if (!$referral) {
            return;
        }

        $referrer = $referral->referrer->load('subscription', 'kyc');

        if (!$referrer->subscription) {
            Log::warning("Referrer {$referrer->id} has no subscription.");
            return;
        }

        // --- KYC CHECK ---
        if (setting('referral_kyc_required', true)) {
            if (
                !$referrer->kyc ||
                !$this->referredUser->kyc ||
                $referrer->kyc->status !== 'verified' ||
                $this->referredUser->kyc->status !== 'verified'
            ) {
                Log::info("Referral #{$referral->id} paused. Referrer or Referee KYC not verified.");
                return;
            }
        }
        // --- END KYC CHECK ---

        $idempotencyKey = "referral_processing:{$referral->id}";

        if ($idempotency->isAlreadyExecuted($idempotencyKey, self::class)) {
            Log::info("Referral #{$referral->id} already processed. Skipping.");
            return;
        }

        $idempotency->executeOnce(
            $idempotencyKey,
            function () use (
                $referral,
                $referrer,
                $referralService,
                $walletService,
                $tdsService,
                $ledgerService
            ) {

                $activeCampaign = ReferralCampaign::running()->first();

                DB::transaction(function () use (
                    $referral,
                    $referrer,
                    $referralService,
                    $walletService,
                    $tdsService,
                    $ledgerService,
                    $activeCampaign
                ) {

                    // Determine campaign
                    $campaignToUse = $referral->referral_campaign_id
                        ? ReferralCampaign::find($referral->referral_campaign_id)
                        : $activeCampaign;

                    // Mark referral completed
                    $updateData = [
                        'status' => 'completed',
                        'completed_at' => now(),
                    ];

                    if (!$referral->referral_campaign_id && $activeCampaign) {
                        $updateData['referral_campaign_id'] = $activeCampaign->id;
                    }

                    $referral->update($updateData);

                    // Calculate bonus
                    $bonusData = $referralService
                        ->calculateReferralBonus($this->referredUser, $campaignToUse);

                    $grossBonus = $bonusData['amount'];
                    $description = $bonusData['description'];

                    // Calculate TDS
                    $tdsResult = $tdsService->calculate($grossBonus, 'referral');

                    // Create BonusTransaction
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

                    // Ledger entry (accrual)
                    $ledgerService->recordBonusWithTds(
                        $bonus,
                        $tdsResult->grossAmount,
                        $tdsResult->tdsAmount
                    );

                    // Deposit to wallet
                    $walletService->deposit(
                        $referrer,
                        $tdsResult->netAmount,
                        'bonus_credit',
                        $tdsResult->getDescription($description),
                        $bonus
                    );

                    // Update multiplier tier
                    $referralService->updateReferrerMultiplier($referrer);

                    Log::info("Referral processed with TDS", [
                        'referrer_id' => $referrer->id,
                        'referred_id' => $this->referredUser->id,
                        'gross_amount' => $tdsResult->grossAmount,
                        'tds_amount' => $tdsResult->tdsAmount,
                        'net_amount' => $tdsResult->netAmount,
                    ]);
                });

                Log::info("Referral processed for {$this->referredUser->username}");
            },
            [
                'job_class' => self::class,
                'input_data' => [
                    'referral_id' => $referral->id,
                    'referrer_id' => $referrer->id,
                    'referred_user_id' => $this->referredUser->id,
                ],
            ]
        );
    }
}