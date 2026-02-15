<?php
// V-PHASE3-1730-082 (Created) | V-FINAL-1730-455 (WalletService Refactor)
// V-PAYMENT-INTEGRITY-2026: Wallet credit moved to PaymentWebhookService (atomic)

namespace App\Jobs;

use App\Models\Payment;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessSuccessfulPaymentJob - Post-Payment Non-Critical Operations
 *
 * V-PAYMENT-INTEGRITY-2026:
 * - Wallet credit is now done ATOMICALLY in PaymentWebhookService::fulfillPayment()
 * - This job only handles NON-CRITICAL operations that can retry independently
 * - If this job fails, the payment and wallet credit are still valid
 *
 * NON-CRITICAL PATH:
 * - Bonus calculation and allocation
 * - Referral processing
 * - Lucky draw entries
 * - Email notifications
 *
 * BENEFIT: If bonus calculation has a bug, it doesn't roll back the
 * payment status or wallet credit. Financial integrity is preserved.
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
     * V-PAYMENT-INTEGRITY-2026:
     * - Wallet credit already done atomically in PaymentWebhookService
     * - This job handles only non-critical post-payment operations
     * - Uses idempotency to prevent duplicate bonus/referral processing
     */
    public function handle(\App\Services\IdempotencyService $idempotency): void
    {
        $idempotencyKey = "payment_post_processing:{$this->payment->id}";

        // Check if already processed
        if ($idempotency->isAlreadyExecuted($idempotencyKey, self::class)) {
            Log::info("Payment #{$this->payment->id} post-processing already done. Skipping.");
            return;
        }

        $idempotency->executeOnce($idempotencyKey, function () {
            Log::info("Starting post-payment processing for Payment #{$this->payment->id}");

            // 1. Calculate and Award Bonuses (Separate Job for isolation)
            ProcessPaymentBonusJob::dispatch($this->payment);

            // 2. Process Referrals (if first payment)
            $this->processReferralIfFirstPayment();

            // 3. Generate Lucky Draw Entries
            GenerateLuckyDrawEntryJob::dispatch($this->payment);

            // 4. Send Notifications
            SendPaymentConfirmationEmailJob::dispatch($this->payment);

            Log::info("Post-payment processing dispatched for Payment #{$this->payment->id}");

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
        Log::error("Post-payment processing failed for Payment #{$this->payment->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Note: Wallet credit is already done - only post-processing failed
        // These can be retried manually or will be picked up by retry system
    }
}
