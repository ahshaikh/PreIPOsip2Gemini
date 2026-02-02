<?php
// V-FINAL-1730-348 (Created) | V-FINAL-1730-457 (WalletService Refactor) | V-AUDIT-MODULE8-002 (Chunk Processing) | V-AUDIT-MODULE8-003 (Idempotency)
// V-PHASE4-LEDGER (Ledger Integration + TDS Compliance)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\CelebrationEvent;
use App\Services\WalletService;
use App\Services\TdsCalculationService;
use App\Services\DoubleEntryLedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCelebrationBonuses extends Command
{
    protected $signature = 'app:process-celebration-bonuses';
    protected $description = 'Awards birthday, anniversary, and festival bonuses.';

    protected TdsCalculationService $tdsService;
    protected DoubleEntryLedgerService $ledgerService;

    public function __construct(
        TdsCalculationService $tdsService,
        DoubleEntryLedgerService $ledgerService
    ) {
        parent::__construct();
        $this->tdsService = $tdsService;
        $this->ledgerService = $ledgerService;
    }

    public function handle(WalletService $walletService)
    {
        if (!setting('celebration_bonus_enabled', true)) return;
        $today = Carbon::today();

        $this->processBirthdayBonuses($today, $walletService);
        $this->processAnniversaryBonuses($today, $walletService);
        $this->processFestivalBonuses($today, $walletService);

        $this->info('Celebration bonuses processed successfully.');
        return 0;
    }

    /**
     * V-AUDIT-MODULE8-003 (HIGH): Process birthday bonuses with idempotency check.
     *
     * Idempotency Fix:
     * - Checks if bonus already awarded today to prevent duplicates
     * - Prevents double-awarding if cron runs twice (server restart, etc.)
     */
    private function processBirthdayBonuses(Carbon $today, WalletService $walletService)
    {
        $birthdays = UserProfile::whereMonth('dob', $today->month)
                            ->whereDay('dob', $today->day)
                            ->whereHas('user.subscription', fn($q) => $q->where('status', 'active'))
                            ->with('user.subscription.plan.configs', 'user.wallet')
                            ->get();

        foreach ($birthdays as $profile) {
            // V-AUDIT-MODULE8-003: Idempotency check - prevent duplicate birthday bonus
            $alreadyAwarded = BonusTransaction::where('user_id', $profile->user_id)
                ->where('type', 'celebration')
                ->whereDate('created_at', $today)
                ->where('description', 'like', '%Birthday%')
                ->exists();

            if ($alreadyAwarded) {
                Log::info("Birthday bonus already awarded today for User {$profile->user_id}, skipping");
                continue;
            }

            $config = $profile->user->subscription->plan->getConfig('celebration_bonus_config');
            $amount = $config['birthday_amount'] ?? 50;
            $this->awardBonus($profile->user, $amount, 'celebration', "Happy Birthday!", $walletService);
        }
    }

    /**
     * V-AUDIT-MODULE8-002 (HIGH): Process anniversary bonuses with chunking for memory efficiency.
     *
     * Performance Fix:
     * - Uses chunk() instead of get() to process records in batches
     * - Prevents memory exhaustion with 50,000+ active subscriptions
     * - Includes idempotency check
     */
    private function processAnniversaryBonuses(Carbon $today, WalletService $walletService)
    {
        // V-AUDIT-MODULE8-002: Use chunk() to prevent OOM with large datasets
        Subscription::where('status', 'active')
                    ->whereMonth('start_date', $today->month)
                    ->whereDay('start_date', $today->day)
                    ->whereYear('start_date', '<', $today->year)
                    ->with('user.wallet', 'plan.configs')
                    ->chunk(500, function ($subscriptions) use ($today, $walletService) {
                        foreach ($subscriptions as $sub) {
                            // V-AUDIT-MODULE8-003: Idempotency check
                            $alreadyAwarded = BonusTransaction::where('user_id', $sub->user_id)
                                ->where('type', 'celebration')
                                ->whereDate('created_at', $today)
                                ->where('description', 'like', '%Anniversary%')
                                ->exists();

                            if ($alreadyAwarded) {
                                Log::info("Anniversary bonus already awarded today for User {$sub->user_id}, skipping");
                                continue;
                            }

                            $yearsActive = $sub->start_date->diffInYears($today);
                            $config = $sub->plan->getConfig('celebration_bonus_config');
                            $baseAmount = $config['anniversary_amount'] ?? 100;
                            $amount = $baseAmount * $yearsActive;
                            $this->awardBonus($sub->user, $amount, 'celebration', "Happy {$yearsActive} Year Anniversary!", $walletService);
                        }
                    });
    }

    /**
     * V-AUDIT-MODULE8-002 (HIGH): Process festival bonuses with chunking for memory efficiency.
     *
     * Performance Fix:
     * - Uses chunk() to process subscriptions in batches of 500
     * - Prevents OOM crash with large user base
     * - Includes idempotency check
     */
    private function processFestivalBonuses(Carbon $today, WalletService $walletService)
    {
        $events = CelebrationEvent::activeToday($today)->get();
        if ($events->isEmpty()) return;

        foreach ($events as $event) {
            // V-AUDIT-MODULE8-002: Use chunk() instead of get() to prevent memory exhaustion
            Subscription::where('status', 'active')
                        ->with('user.wallet', 'plan')
                        ->chunk(500, function ($subscriptions) use ($event, $today, $walletService) {
                            foreach ($subscriptions as $sub) {
                                $planKey = $sub->plan->slug ?? 'plan_a';
                                $amount = $event->bonus_amount_by_plan[$planKey] ?? 0;

                                if ($amount <= 0) continue;

                                // V-AUDIT-MODULE8-003: Idempotency check for festival bonuses
                                $alreadyAwarded = BonusTransaction::where('user_id', $sub->user_id)
                                    ->where('type', 'celebration')
                                    ->whereDate('created_at', $today)
                                    ->where('description', 'like', "%{$event->name}%")
                                    ->exists();

                                if ($alreadyAwarded) {
                                    Log::info("Festival bonus for {$event->name} already awarded today for User {$sub->user_id}, skipping");
                                    continue;
                                }

                                $this->awardBonus($sub->user, $amount, 'celebration', "Happy {$event->name}!", $walletService);
                            }
                        });
        }
    }

    /**
     * V-AUDIT-MODULE8-001: Award bonus with TDS deduction for tax compliance.
     *
     * PHASE 4 LEDGER INTEGRATION:
     * Uses two-step flow for proper bonus accounting:
     *   Step 1: recordBonusWithTds() - DEBIT MARKETING_EXPENSE, CREDIT BONUS_LIABILITY, CREDIT TDS_PAYABLE
     *   Step 2: deposit('bonus_credit') - DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
     */
    private function awardBonus($user, $amount, $type, $msg, WalletService $walletService)
    {
        if (!$user || !$user->subscription || !$user->wallet) return;

        DB::transaction(function() use ($user, $amount, $type, $msg, $walletService) {
            // V-AUDIT-MODULE8-001: Calculate TDS using centralized service
            $tdsResult = $this->tdsService->calculate($amount, 'celebration');

            $bonus = BonusTransaction::create([
                'user_id' => $user->id,
                'subscription_id' => $user->subscription->id,
                'type' => $type,
                'amount' => $tdsResult->grossAmount, // Gross amount
                'tds_deducted' => $tdsResult->tdsAmount, // TDS for compliance
                'base_amount' => $amount,
                'multiplier_applied' => 1.0,
                'description' => $msg,
            ]);

            // PHASE 4: Record bonus accrual in ledger FIRST
            // DEBIT MARKETING_EXPENSE (gross), CREDIT BONUS_LIABILITY (net), CREDIT TDS_PAYABLE (tds)
            $this->ledgerService->recordBonusWithTds(
                $bonus,
                $tdsResult->grossAmount,
                $tdsResult->tdsAmount
            );

            // V-AUDIT-MODULE8-001: Transfer to wallet (net amount after TDS)
            // This triggers recordBonusToWallet(): DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
            $walletService->deposit(
                $user,
                $tdsResult->netAmount,
                'bonus_credit',
                $tdsResult->getDescription($msg),
                $bonus
            );

            Log::info("Celebration bonus with ledger integration", [
                'user_id' => $user->id,
                'type' => $type,
                'gross' => $tdsResult->grossAmount,
                'tds' => $tdsResult->tdsAmount,
                'net' => $tdsResult->netAmount,
                'bonus_id' => $bonus->id
            ]);
        });
    }
}
