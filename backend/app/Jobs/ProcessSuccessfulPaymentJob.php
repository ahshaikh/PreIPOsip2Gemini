<?php
// V-PHASE3-1730-082 (Created) | V-FINAL-1730-455 (WalletService Refactor)
// V-WALLET-FIRST-2026: Wallet-first architecture - user controls investment decisions

namespace App\Jobs;

use App\Models\Payment;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use App\Services\WalletService;
use App\Enums\TransactionType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessSuccessfulPaymentJob - Payment Processing (Wallet-First Model)
 *
 * V-WALLET-FIRST-2026:
 * - Payment credits wallet, user decides when/where to invest
 * - NO automatic share allocation - user must click "Buy Shares"
 * - Bonus also credited as cash - user chooses how to use it
 *
 * FLOW:
 * 1. Payment received → Credit to wallet (+₹5000)
 * 2. Bonus calculated → Credit to wallet (+₹10)
 * 3. User browses available companies
 * 4. User clicks "Buy Shares" → Separate endpoint handles allocation
 *
 * WALLET SHOWS: +₹5010 (principal + bonus)
 * USER DECIDES: Invest in Company A, B, or withdraw to bank
 */
class ProcessSuccessfulPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60; // 60 seconds between retries

    /**
     * Delete the job if its models no longer exist.
     */
    public $deleteWhenMissingModels = true;

    public function __construct(public Payment $payment)
    {
    }

    /**
     * Execute the job.
     *
     * V-WALLET-FIRST-2026:
     * - Credit payment to wallet
     * - Calculate and credit bonus to wallet
     * - NO auto-investment - user decides when to buy shares
     */
    public function handle(\App\Services\IdempotencyService $idempotency): void
    {
        $idempotencyKey = "payment_processing:{$this->payment->id}";

        // Check if already processed
        if ($idempotency->isAlreadyExecuted($idempotencyKey, self::class)) {
            Log::info("Payment #{$this->payment->id} already processed. Skipping.");
            return;
        }

        $idempotency->executeOnce($idempotencyKey, function () {
            Log::info("Processing payment #{$this->payment->id}");

            $user = $this->payment->user;
            if (!$user) {
                throw new \RuntimeException("Payment #{$this->payment->id} has no associated user");
            }

            $amountPaise = $this->payment->getAmountPaiseStrict();
            $amountRupees = $amountPaise / 100;
            $walletService = app(WalletService::class);

            // 1. CREDIT PRINCIPAL TO WALLET
            // V-WALLET-FIRST-2026: User receives funds in wallet, decides what to do
            $walletService->deposit(
                $user,
                $amountPaise,
                TransactionType::DEPOSIT,
                "Payment received for SIP installment #{$this->payment->id}",
                $this->payment
            );
            Log::info("WALLET +₹{$amountRupees}: Payment #{$this->payment->id} credited to user #{$user->id}");

            // 2. CALCULATE AND CREDIT BONUSES
            // V-WALLET-FIRST-2026: Bonus credited as cash, user decides how to use it
            ProcessPaymentBonusJob::dispatch($this->payment);

            // 3. PROCESS REFERRALS (if first payment)
            $this->processReferralIfFirstPayment();

            // 4. GENERATE LUCKY DRAW ENTRIES
            GenerateLuckyDrawEntryJob::dispatch($this->payment);

            // 5. SEND NOTIFICATIONS
            SendPaymentConfirmationEmailJob::dispatch($this->payment);

            Log::info("Payment #{$this->payment->id} processed. User wallet credited ₹{$amountRupees}");

            // NOTE: No auto-investment. User will:
            // - Browse available companies
            // - Click "Buy Shares" on chosen company
            // - That triggers withdrawal + share allocation

        }, [
            'job_class' => self::class,
            'input_data' => [
                'payment_id' => $this->payment->id,
                'amount_paise' => $this->payment->amount_paise ?? (int) round($this->payment->amount * 100),
                'user_id' => $this->payment->user_id,
            ],
        ]);
    }

    /**
     * Process referral bonus if this is the user's first successful payment.
     */
    private function processReferralIfFirstPayment(): void
    {
        $user = $this->payment->user;

        if (!$user) {
            return;
        }

        // Check if this is the first payment
        $paidPaymentsCount = $user->payments()
            ->where('status', 'paid')
            ->count();

        if ($paidPaymentsCount === 1) {
            Log::info("First payment detected for user #{$user->id}, processing referral");
            ProcessReferralJob::dispatch($user);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Payment processing failed for Payment #{$this->payment->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
