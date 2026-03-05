<?php
// V-AUDIT-MODULE4-008 (Created) - Separate job for non-critical bonus processing
// V-WALLET-FIRST-2026: Bonus credited as cash, user decides how to use it
//
// @deprecated V-ORCHESTRATION-2026: This job is DEPRECATED.
// Bonus calculation is now handled synchronously within FinancialOrchestrator::processSuccessfulPayment()
// as part of the single transaction boundary. This ensures bonuses are calculated with the
// wallet already locked, preventing race conditions.

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
 * ProcessPaymentBonusJob - Bonus Calculation and Wallet Credit
 *
 * @deprecated V-ORCHESTRATION-2026: Use FinancialOrchestrator::processSuccessfulPayment() instead.
 * Bonus calculation is now part of the single-transaction payment lifecycle. This job remains
 * for backward compatibility with queued items but should NOT be dispatched for new payments.
 *
 * V-WALLET-FIRST-2026:
 * - Calculate bonuses based on payment
 * - Credit bonus to user's wallet as CASH
 * - NO automatic share purchase - user decides
 *
 * User can then:
 * - Use bonus to buy shares (via "Buy Shares" button)
 * - Or withdraw bonus to bank account
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
            $user = $this->payment->user;

            // Calculate and Credit Bonuses to Wallet
            // BonusCalculatorService::calculateAndAwardBonuses() handles:
            // - Bonus eligibility calculation
            // - TDS deduction
            // - Wallet credit via depositTaxable()
            $totalGrossBonus = $bonusService->calculateAndAwardBonuses($this->payment);

            if ($totalGrossBonus <= 0) {
                Log::info("No bonus calculated for Payment {$this->payment->id}.");
                return;
            }

            // Calculate NET bonus (after TDS)
            $netBonusTotal = $user->bonuses()
                ->where('payment_id', $this->payment->id)
                ->sum(DB::raw('amount - tds_deducted'));

            Log::info("WALLET +₹{$netBonusTotal}: Bonus credited for Payment #{$this->payment->id} (Gross: ₹{$totalGrossBonus})");

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
