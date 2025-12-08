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
     *
     * V-FIX-1208: Complete wallet accounting for payment flow
     * - Credit payment amount to wallet
     * - Credit bonus to wallet
     * - Debit wallet for share purchase
     * - Allocate shares
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

            // 1. Credit Payment Amount to Wallet
            $walletService->deposit(
                $user,
                $this->payment->amount,
                'payment_received',
                "Payment received for SIP installment #{$this->payment->id}",
                $this->payment
            );
            Log::info("Payment #{$this->payment->id}: Credited ₹{$this->payment->amount} to user wallet");

            // 2. Calculate Bonuses
            $totalBonus = $bonusService->calculateAndAwardBonuses($this->payment);

            // 3. Credit Wallet with Bonus
            if ($totalBonus > 0) {
                $bonusTxn = $user->bonuses()->where('payment_id', $this->payment->id)->first();
                $walletService->deposit(
                    $user,
                    $totalBonus,
                    'bonus_credit',
                    'SIP Bonus',
                    $bonusTxn
                );
                Log::info("Payment #{$this->payment->id}: Credited ₹{$totalBonus} bonus to user wallet");
            }

            // 4. Debit Wallet for Share Purchase
            $totalInvestmentValue = $this->payment->amount + $totalBonus;
            $walletService->withdraw(
                $user,
                $totalInvestmentValue,
                'share_purchase',
                "Share purchase from Payment #{$this->payment->id}",
                $this->payment,
                false // Immediate debit, not locked
            );
            Log::info("Payment #{$this->payment->id}: Debited ₹{$totalInvestmentValue} from user wallet for share purchase");

            // 5. Allocate Shares (Payment + Bonus)
            $allocationService->allocateShares($this->payment, $totalInvestmentValue);

            // 6. Process Referrals (if this is the first payment)
            if ($user->payments()->where('status', 'paid')->count() === 1) {
                ProcessReferralJob::dispatch($user);
            }

            // 7. Generate Lucky Draw Entries
            GenerateLuckyDrawEntryJob::dispatch($this->payment);

        }); // End DB Transaction

        // 8. Send Notifications (After DB commit)
        SendPaymentConfirmationEmailJob::dispatch($this->payment);

        Log::info("All post-payment actions completed for Payment {$this->payment->id}");
    }
}