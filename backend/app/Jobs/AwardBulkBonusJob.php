<?php
// V-AUDIT-MODULE8-005 (Created): Background job for awarding bulk bonuses

namespace App\Jobs;

use App\Models\User;
use App\Models\BonusTransaction;
use App\Services\WalletService;
use App\Notifications\BonusCredited;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * V-AUDIT-MODULE8-005 (MEDIUM): Process bulk bonus awards in background.
 *
 * Scalability Fix:
 * - Previous: AdminBonusController::awardBulkBonus processed 5000+ users synchronously
 * - Problem: Request times out, blocks admin, no progress indication
 * - Solution: Dispatch individual jobs for each user, process in parallel
 *
 * Benefits:
 * - Admin gets immediate response (jobs queued successfully)
 * - Queue workers process awards in parallel
 * - Failed awards don't block others
 * - Progress trackable via queue monitoring
 */
class AwardBulkBonusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum execution time: 1 minute per user
     */
    public $timeout = 60;

    /**
     * Maximum retry attempts
     */
    public $tries = 3;

    public function __construct(
        public int $userId,
        public float $amount,
        public string $reason
    ) {}

    /**
     * Process the bulk bonus award for a single user.
     *
     * @param WalletService $walletService
     * @return void
     */
    public function handle(WalletService $walletService): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning("User {$this->userId} not found for bulk bonus award");
            return;
        }

        try {
            DB::transaction(function () use ($user, $walletService) {
                // V-AUDIT-MODULE8-001: Calculate TDS for bulk bonuses
                $tdsPercentage = (float) setting('bonus_tds_percentage', 10.0);
                $tdsAmount = round(($tdsPercentage / 100) * $this->amount, 2);
                $netAmount = round($this->amount - $tdsAmount, 2);

                // 1. Create Record
                $bonusTransaction = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscriptions()->latest()->first()?->id,
                    'payment_id' => null,
                    'type' => 'special_bonus',
                    'amount' => $this->amount, // Gross amount
                    'tds_deducted' => $tdsAmount, // V-AUDIT-MODULE8-001: TDS
                    'multiplier_applied' => 1.0,
                    'base_amount' => $this->amount,
                    'description' => "Bulk Bonus: {$this->reason}"
                ]);

                // 2. Credit wallet with net amount (after TDS)
                $walletService->deposit(
                    $user,
                    $netAmount,
                    'bonus_credit',
                    "Bulk Bonus: {$this->reason}" . ($tdsAmount > 0 ? " (TDS ₹{$tdsAmount} deducted)" : ""),
                    $bonusTransaction
                );

                Log::info("Bulk bonus awarded: Gross=₹{$this->amount}, TDS=₹{$tdsAmount}, Net=₹{$netAmount}", [
                    'user_id' => $user->id,
                    'bonus_id' => $bonusTransaction->id
                ]);
            });

            // Send notification to user
            $user->notify(new BonusCredited($this->amount, 'Special'));

        } catch (\Exception $e) {
            Log::error("Failed to award bulk bonus to User {$this->userId}: " . $e->getMessage());
            // Re-throw to mark job as failed (will be retried based on $tries)
            throw $e;
        }
    }

    /**
     * Handle job failure after max retries.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("AwardBulkBonusJob failed permanently for User {$this->userId}", [
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'error' => $exception->getMessage()
        ]);

        // Optionally: Send alert to admin, store in failed_jobs table (automatic), etc.
    }
}
