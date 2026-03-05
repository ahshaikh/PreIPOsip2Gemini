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
        \App\Services\FinancialOrchestrator $orchestrator,
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

        $referrer = $referral->referrer;

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
            function () use ($orchestrator) {
                // V-ORCHESTRATION-2026: Route via orchestrator for single transaction boundary
                $orchestrator->awardReferralBonusFromJob($this->referredUser);
            },
            [
                'job_class' => self::class,
                'input_data' => [
                    'referral_id' => $referral->id,
                    'referred_user_id' => $this->referredUser->id,
                ],
            ]
        );
    }
}