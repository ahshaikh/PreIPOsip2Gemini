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
     *
     * CRITICAL PATH (Must succeed atomically):
     * - Credit payment amount to wallet
     * - Debit wallet for share purchase
     * - Allocate shares
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
     * V-FIX-1208: Complete wallet accounting for payment flow
     */
    public function handle(
        AllocationService $allocationService,
        WalletService $walletService
    ): void
    {
        // CRITICAL TRANSACTION: Payment Processing and Share Allocation
        // This must succeed atomically. Do NOT include bonus logic here.
        DB::transaction(function () use ($allocationService, $walletService) {
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

            // 2. Debit Wallet for Share Purchase (Payment amount only, bonus handled separately)
            $walletService->withdraw(
                $user,
                $this->payment->amount,
                'share_purchase',
                "Share purchase from Payment #{$this->payment->id}",
                $this->payment,
                false // Immediate debit, not locked
            );
            Log::info("Payment #{$this->payment->id}: Debited ₹{$this->payment->amount} from user wallet for share purchase");

            // 3. Allocate Shares (Payment amount only)
            $allocationService->allocateShares($this->payment, $this->payment->amount);

        }); // End Critical Transaction

        Log::info("Critical payment processing completed for Payment {$this->payment->id}");

        // NON-CRITICAL OPERATIONS: Dispatch to separate jobs for independent retry
        // If any of these fail, they won't affect the payment or share allocation above

        // 4. Calculate and Award Bonuses (Separate Job)
        ProcessPaymentBonusJob::dispatch($this->payment);

        // 5. Process Referrals (if this is the first payment)
        if ($user->payments()->where('status', 'paid')->count() === 1) {
            ProcessReferralJob::dispatch($user);
        }

        // 6. Generate Lucky Draw Entries
        GenerateLuckyDrawEntryJob::dispatch($this->payment);

        // 7. Send Notifications
        SendPaymentConfirmationEmailJob::dispatch($this->payment);

        Log::info("All post-payment actions dispatched for Payment {$this->payment->id}");
    }
}