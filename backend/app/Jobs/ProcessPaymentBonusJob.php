<?php
// V-AUDIT-MODULE4-008 (Created) - Separate job for non-critical bonus processing
// This job is dispatched after the critical payment and share allocation succeeds.
// If bonus calculation fails, it can be retried without affecting the core payment.

namespace App\Jobs;

use App\Models\Payment;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPaymentBonusJob - Non-Critical Bonus Calculation and Allocation
 *
 * This job handles bonus calculation and allocation separately from the critical
 * payment processing to ensure that bonus calculation bugs don't roll back payments.
 *
 * Workflow:
 * 1. Calculate bonuses based on payment
 * 2. Credit bonus to user's wallet
 * 3. Debit wallet for bonus share purchase
 * 4. Allocate bonus shares from inventory
 *
 * If any step fails, it can be retried independently without affecting the base payment.
 */
class ProcessPaymentBonusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry this job up to 5 times with exponential backoff
     * Non-critical operations can afford more retries
     */
    public $tries = 5;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(public Payment $payment)
    {
    }

    /**
     * Execute the bonus processing job.
     */
    public function handle(
        BonusCalculatorService $bonusService,
        AllocationService $allocationService,
        WalletService $walletService
    ): void
    {
        // Skip if payment is no longer valid
        if ($this->payment->status !== 'paid') {
            Log::warning("Bonus job skipped: Payment {$this->payment->id} is not in 'paid' status.");
            return;
        }

        // Check if bonus was already processed (idempotency)
        $existingBonus = $this->payment->user->bonuses()
            ->where('payment_id', $this->payment->id)
            ->exists();

        if ($existingBonus) {
            Log::info("Bonus already processed for Payment {$this->payment->id}. Skipping.");
            return;
        }

        try {
            // Wrap bonus operations in a transaction
            DB::transaction(function () use ($bonusService, $allocationService, $walletService) {
                $user = $this->payment->user;

                // 1. Calculate and Award Bonuses
                $totalBonus = $bonusService->calculateAndAwardBonuses($this->payment);

                // If no bonus, exit gracefully
                if ($totalBonus <= 0) {
                    Log::info("No bonus calculated for Payment {$this->payment->id}.");
                    return;
                }

                // 2. Credit Wallet with Bonus
                $bonusTxn = $user->bonuses()->where('payment_id', $this->payment->id)->first();
                $walletService->deposit(
                    $user,
                    $totalBonus,
                    'bonus_credit',
                    'SIP Bonus',
                    $bonusTxn
                );
                Log::info("Payment #{$this->payment->id}: Credited ₹{$totalBonus} bonus to user wallet");

                // 3. Debit Wallet for Bonus Share Purchase
                $walletService->withdraw(
                    $user,
                    $totalBonus,
                    'share_purchase',
                    "Bonus share purchase from Payment #{$this->payment->id}",
                    $this->payment,
                    false // Immediate debit
                );
                Log::info("Payment #{$this->payment->id}: Debited ₹{$totalBonus} from wallet for bonus share purchase");

                // 4. Allocate Bonus Shares
                $allocationService->allocateShares($this->payment, $totalBonus);

                Log::info("Bonus processing completed successfully for Payment {$this->payment->id}");
            });

        } catch (\Exception $e) {
            Log::error("Bonus processing failed for Payment {$this->payment->id}: " . $e->getMessage());
            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("Bonus processing permanently failed for Payment {$this->payment->id} after {$this->tries} attempts.", [
            'error' => $exception->getMessage(),
            'payment_id' => $this->payment->id,
            'user_id' => $this->payment->user_id,
        ]);

        // TODO: Send alert to admin or create a support ticket for manual review
    }
}
