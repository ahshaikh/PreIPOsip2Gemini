<?php
// V-AUDIT-MODULE4-008 (Created) - Separate job for non-critical bonus processing
// V-WALLET-FIRST-2026: Bonus credited as cash, user decides how to use it
//
// @deprecated V-ORCHESTRATION-2026: This job is DEPRECATED.
// Bonus handling belongs to the bonus lifecycle and should not trigger full payment lifecycle execution.

namespace App\Jobs;

use App\Models\Payment;
use App\Services\BonusCalculatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated V-ORCHESTRATION-2026
 *
 * This job exists only to process legacy queued bonus tasks created
 * before the FinancialOrchestrator lifecycle consolidation.
 *
 * New payment flows MUST NOT dispatch this job.
 *
 * The correct architecture is:
 *
 * PaymentWebhookService
 *
 * FinancialOrchestrator::processSuccessfulPayment()
 *
 * BonusCalculatorService
 *
 * This job remains solely for backward compatibility with existing
 * queue items and should eventually be removed once all legacy jobs
 * have been drained.
 *
 * DO NOT modify this job to trigger payment lifecycle methods.
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
     *
     * V-WALLET-FIRST-2026:
     * - Calculate eligible bonuses
     * - Credit to wallet as cash (via BonusCalculatorService)
     * - User decides what to do with the bonus
     */
    public function handle(BonusCalculatorService $bonusService): void
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
            $payment = Payment::find($this->payment->id);
            if (!$payment) {
                Log::warning("Bonus job skipped: Payment {$this->payment->id} not found.");
                return;
            }

            $totalGrossBonus = $bonusService->calculateAndAwardBonuses($payment);

            if ($totalGrossBonus <= 0) {
                Log::info("No bonus calculated for Payment {$payment->id}.");
                return;
            }

            $netBonusTotal = $payment->user->bonuses()
                ->where('payment_id', $payment->id)
                ->get()
                ->sum(fn($bonus) => (float) $bonus->amount - (float) ($bonus->tds_deducted ?? 0));

            Log::info("Bonus lifecycle processed for Payment #{$payment->id} (Gross: ₹{$totalGrossBonus}, Net: ₹{$netBonusTotal})");

            // V-WALLET-FIRST-2026: Bonus remains as cash in wallet
            // User can:
            // - Buy shares via "Buy Shares" button
            // - Withdraw to bank account

        } catch (\Exception $e) {
            Log::error("Bonus processing failed for Payment {$this->payment->id}: " . $e->getMessage());
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
    }
}
