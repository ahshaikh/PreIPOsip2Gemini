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
     *
     * V-AUDIT-MODULE4-008 (LOW) - Separated Critical and Non-Critical Logic
     * V-FIX-PAYMENT-FLOW: Changed to only credit wallet, allowing manual share selection
     * [G.22 FIX]: Added idempotency protection to prevent double wallet credits
     *
     * CRITICAL PATH (Must succeed atomically):
     * - Credit payment amount to wallet
     *
     * USER INITIATED PATH (Happens when user selects shares to buy):
     * - Debit wallet for share purchase (happens in InvestmentController)
     * - Allocate shares (happens in InvestmentController)
     *
     * NON-CRITICAL PATH (Can retry independently):
     * - Bonus calculation and allocation (dispatched to separate job)
     * - Referral processing (dispatched)
     * - Lucky draw entries (dispatched)
     * - Email notifications (dispatched)
     *
     * BENEFIT: If bonus calculation has a bug, it doesn't roll back the critical
     * payment and share allocation. Financial integrity is preserved.
     *
     * PAYMENT FLOW:
     * 1. Payment made → Admin approves → Wallet credited (THIS JOB)
     * 2. User selects shares → Wallet debited → Shares allocated (InvestmentController)
     */
    public function handle(
        WalletService $walletService,
        \App\Services\IdempotencyService $idempotency
    ): void
    {
        $idempotencyKey = "payment_processing:{$this->payment->id}";

        // [G.22]: Check if already processed to prevent double wallet credit
        if ($idempotency->isAlreadyExecuted($idempotencyKey, self::class)) {
            Log::info("Payment #{$this->payment->id} already processed. Skipping to prevent double credit.");
            return;
        }

        // [G.22]: Execute with idempotency protection
        $idempotency->executeOnce($idempotencyKey, function () use ($walletService) {
            // CRITICAL TRANSACTION: Payment Processing - Credit Wallet Only
            // Share allocation happens when user manually selects shares
            DB::transaction(function () use ($walletService) {
                $user = $this->payment->user;

                // 1. Credit Payment Amount to Wallet
                // V-FIX-WALLET-NOT-REFLECTING: Bypass KYC compliance gate for admin-approved payments
                // Admin payment approval (manual or offline) is itself a compliance verification
                // KYC compliance gate would block deposits for users without completed KYC,
                // preventing wallet crediting even after admin approval
                $walletService->deposit(
                    $user,
                    $this->payment->amount,
                    'payment_received',
                    "Payment received for SIP installment #{$this->payment->id}",
                    $this->payment,
                    bypassComplianceCheck: true  // V-FIX-WALLET-NOT-REFLECTING: Critical fix
                );
                Log::info("Payment #{$this->payment->id}: Credited ₹{$this->payment->amount} to user wallet. User can now select shares to purchase.");

            }); // End Critical Transaction

            Log::info("Payment processing completed for Payment {$this->payment->id}. Funds available in wallet for share selection.");

        }, [
            'job_class' => self::class,
            'input_data' => [
                'payment_id' => $this->payment->id,
                'amount' => $this->payment->amount,
                'user_id' => $this->payment->user_id,
            ],
        ]);

        // NON-CRITICAL OPERATIONS: Dispatch to separate jobs for independent retry
        // If any of these fail, they won't affect the payment or share allocation above

        // 4. Calculate and Award Bonuses (Separate Job)
        ProcessPaymentBonusJob::dispatch($this->payment);

        // 5. Process Referrals (if this is the first payment)
        if ($this->payment->user->payments()->where('status', 'paid')->count() === 1) {
            ProcessReferralJob::dispatch($this->payment->user);
        }

        // 6. Generate Lucky Draw Entries
        GenerateLuckyDrawEntryJob::dispatch($this->payment);

        // 7. Send Notifications
        SendPaymentConfirmationEmailJob::dispatch($this->payment);

        Log::info("All post-payment actions dispatched for Payment {$this->payment->id}");
    }
}