<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-ATOMIC-WALLET-UPDATE | V-STRATEGY-ADOPTION
 * V-PHASE4-LEDGER (Ledger Integration + TDS Compliance)
 *
 * Refactored to address Module 6 Audit Gaps:
 * 1. Atomic Transactions: Uses DB::transaction for race-condition safety.
 * 2. Strategy Alignment: Standardizes metadata to support Milestone/Progressive types.
 * 3. TDS Accuracy: Enforces backend-calculated tax deductions before wallet credit.
 *
 * PHASE 4 LEDGER INTEGRATION:
 * Uses two-step flow for proper bonus accounting:
 *   Step 1: recordBonusWithTds() - DEBIT MARKETING_EXPENSE, CREDIT BONUS_LIABILITY, CREDIT TDS_PAYABLE
 *   Step 2: deposit('bonus_credit') - DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
 */

namespace App\Jobs;

use App\Models\User;
use App\Models\Wallet;
use App\Models\BonusTransaction;
use App\Enums\BonusType; // [AUDIT FIX]: Use Enums for type safety
use App\Notifications\BonusCredited;
use App\Services\WalletService;
use App\Services\TdsCalculationService;
use App\Services\DoubleEntryLedgerService;
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
     * Process the bonus award with proper TDS and ledger integration.
     */
    public function handle(
        \App\Services\FinancialOrchestrator $orchestrator
    ): void {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning("User not found for ID: {$this->userId}");
            return;
        }

        try {
            // V-ORCHESTRATION-2026: Route via orchestrator for single transaction boundary
            $orchestrator->awardBulkBonus(
                $user,
                $this->amount,
                $this->reason,
                $this->bonusType
            );

            $user->notify(new BonusCredited($this->amount, $this->bonusType));

        } catch (\Exception $e) {
            Log::error("Bonus Job Failed: " . $e->getMessage());
            throw $e;
        }
    }
}