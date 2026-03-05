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

    public function handle()
    {
        if (!setting('celebration_bonus_enabled', true)) return;
        $today = Carbon::today();

        $this->processBirthdayBonuses($today);
        $this->processAnniversaryBonuses($today);
        $this->processFestivalBonuses($today);

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
    private function processBirthdayBonuses(Carbon $today)
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
            $this->awardBonus($profile->user, $amount, 'celebration', "Happy Birthday!");
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
    private function processAnniversaryBonuses(Carbon $today)
    {
        // V-AUDIT-MODULE8-002: Use chunk() to prevent OOM with large datasets
        Subscription::where('status', 'active')
                    ->whereMonth('start_date', $today->month)
                    ->whereDay('start_date', $today->day)
                    ->whereYear('start_date', '<', $today->year)
                    ->with('user.wallet', 'plan.configs')
                    ->chunk(500, function ($subscriptions) use ($today) {
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
                            $this->awardBonus($sub->user, $amount, 'celebration', "Happy {$yearsActive} Year Anniversary!");
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
    private function processFestivalBonuses(Carbon $today)
    {
        $events = CelebrationEvent::activeToday($today)->get();
        if ($events->isEmpty()) return;

        foreach ($events as $event) {
            // V-AUDIT-MODULE8-002: Use chunk() instead of get() to prevent memory exhaustion
            Subscription::where('status', 'active')
                        ->with('user.wallet', 'plan')
                        ->chunk(500, function ($subscriptions) use ($event, $today) {
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

                                $this->awardBonus($sub->user, $amount, 'celebration', "Happy {$event->name}!");
                            }
                        });
        }
    }

    /**
     * V-AUDIT-MODULE8-001: Award bonus with TDS deduction for tax compliance via orchestrator.
     *
     * V-ORCHESTRATION-2026: Route via orchestrator for single transaction boundary.
     */
    private function awardBonus($user, $amount, $type, $msg)
    {
        if (!$user || !$user->subscription || !$user->wallet) return;

        try {
            // V-ORCHESTRATION-2026: Route via orchestrator for single transaction boundary
            app(\App\Services\FinancialOrchestrator::class)->awardBulkBonus(
                $user,
                $amount,
                $msg,
                $type
            );

            Log::info("Celebration bonus via orchestrator", [
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'msg' => $msg
            ]);
        } catch (\Exception $e) {
            Log::error("Celebration bonus failed for User {$user->id}: " . $e->getMessage());
        }
    }
}
