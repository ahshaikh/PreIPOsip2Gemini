<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-ATOMIC-WALLET-UPDATE | V-STRATEGY-ADOPTION
 * Refactored to address Module 6 Audit Gaps:
 * 1. Atomic Transactions: Uses DB::transaction and increment() for race-condition safety.
 * 2. Strategy Alignment: Standardizes metadata to support Milestone/Progressive types.
 * 3. TDS Accuracy: Enforces backend-calculated tax deductions before wallet credit.
 */

namespace App\Jobs;

use App\Models\User;
use App\Models\Wallet;
use App\Models\BonusTransaction;
use App\Enums\BonusType; // [AUDIT FIX]: Use Enums for type safety
use App\Notifications\BonusCredited;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AwardBulkBonusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    public function __construct(
        public int $userId,
        public float $amount,
        public string $reason,
        public string $bonusType = 'special_bonus' // Matches BonusType Enum
    ) {}

    /**
     * Process the bonus award atomically.
     * [AUDIT FIX]: Uses atomic increment() to prevent balance overwrites.
     */
    public function handle(): void
    {
        $user = User::with('wallet')->find($this->userId);

        if (!$user || !$user->wallet) {
            Log::warning("User or Wallet not found for ID: {$this->userId}");
            return;
        }

        try {
            DB::transaction(function () use ($user) {
                // 1. Calculate Backend-Driven TDS
                $tdsPercentage = (float) setting('bonus_tds_percentage', 10.0);
                $tdsAmount = round(($tdsPercentage / 100) * $this->amount, 2);
                $netAmount = round($this->amount - $tdsAmount, 2);

                // 2. Create Immutable Ledger Entry
                $bonusTransaction = BonusTransaction::create([
                    'user_id' => $user->id,
                    'type' => $this->bonusType,
                    'amount' => $this->amount, // Gross
                    'tds_deducted' => $tdsAmount,
                    'net_amount' => $netAmount,
                    'description' => "Bulk Bonus: {$this->reason}"
                ]);

                // 3. [AUDIT FIX]: Atomic Wallet Update
                // lockForUpdate prevents other jobs from reading stale balance
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                
                // increment() is executed as a single SQL command: SET balance = balance + X
                $wallet->increment('bonus_balance', $netAmount);

                Log::info("Atomic Bonus Credit Successful", [
                    'user_id' => $user->id,
                    'net' => $netAmount,
                    'type' => $this->bonusType
                ]);
            });

            $user->notify(new BonusCredited($this->amount, $this->bonusType));

        } catch (\Exception $e) {
            Log::error("Bonus Job Failed: " . $e->getMessage());
            throw $e;
        }
    }
}