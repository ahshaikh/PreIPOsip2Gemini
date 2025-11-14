<?php
// V-PHASE3-1730-082 (Created) | V-FINAL-1730-455 (WalletService Refactor)

namespace App\Jobs;

use App\Models\Payment;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use App\Services\WalletService; // <-- IMPORT
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSuccessfulPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Retry if it fails

    public function __construct(public Payment $payment)
    {
    }

    /**
     * Execute the job.
     * This is now ATOMIC. If allocation fails, the bonus is rolled back.
     */
    public function handle(
        BonusCalculatorService $bonusService, 
        AllocationService $allocationService,
        ReferralService $referralService,
        WalletService $walletService // <-- INJECT
    ): void
    {
        // FSD-SEC-011: Wrap in a transaction
        DB::transaction(function () use ($bonusService, $allocationService, $referralService, $walletService) {
            $user = $this->payment->user;

            // 1. Calculate Bonuses
            $totalBonus = $bonusService->calculateAndAwardBonuses($this->payment);

            // 2. Credit Wallet with Bonus
            if ($totalBonus > 0) {
                $bonusTxn = $user->bonuses()->where('payment_id', $this->payment->id)->first();
                $walletService->deposit(
                    $user, 
                    $totalBonus, 
                    'bonus_credit', 
                    'SIP Bonus', 
                    $bonusTxn
                );
            }

            // 3. Allocate Shares (Payment + Bonus)
            $totalInvestmentValue = $this->payment->amount + $totalBonus;
            $allocationService->allocateShares($this->payment, $totalInvestmentValue);
            
            // 4. Process Referrals (if this is the first payment)
            if ($user->payments()->where('status', 'paid')->count() === 1) {
                ProcessReferralJob::dispatch($user);
            }

            // 5. Generate Lucky Draw Entries
            GenerateLuckyDrawEntryJob::dispatch($this->payment);

        }); // End DB Transaction

        // 6. Send Notifications (After DB commit)
        SendPaymentConfirmationEmailJob::dispatch($this->payment);
        
        Log::info("All post-payment actions completed for Payment {$this->payment->id}");
    }
}