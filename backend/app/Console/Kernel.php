<?php
// V-REMEDIATE-1730-165 (Created) | V-FINAL-1730-280 | V-FINAL-1730-385 (SLA Job Added) | V-FINAL-1730-427 (Draw Job Added) | V-FINAL-1730-431 (Sitemap Added) | V-FINAL-1730-496 (Price Job Added) | V-AUDIT-FIX-SCHEDULER-SAFETY | V-DISPUTE-RISK-2026-008 (Dispute Stats Cache)

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcessCelebrationBonuses;
use App\Console\Commands\ProcessAutoDebits;
use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\CheckTicketSLACommand;
use App\Console\Commands\ProcessMonthlyLuckyDraw;
use App\Console\Commands\UpdateProductPrices;
use App\Console\Commands\ProcessPendingWebhooks;
// [AUDIT FIX] Import Profit Share Command if it exists, or assume generic class structure
use App\Console\Commands\ProcessProfitShareDistribution;
use App\Console\Commands\ReleaseExpiredFundLocks; // FIX 18
use App\Console\Commands\ReconcileBalances; // FIX 32
use App\Console\Commands\WalletReconcileCommand; // V-PRECISION-2026
use App\Console\Commands\WarmDisputeStatsCache; // V-DISPUTE-RISK-2026
use App\Console\Commands\AggregateDailyDisputeSnapshots; // V-DISPUTE-RISK-2026
use App\Services\DisclosureFreshnessService;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // [AUDIT FIX] Critical Financial Job: Auto Debits
        // - withoutOverlapping(): Prevents double charging if job takes > 24h (unlikely but safe)
        // - onOneServer(): Prevents double charging in load balanced setups
        $schedule->command(ProcessAutoDebits::class)
                 ->dailyAt('09:00')
                 ->withoutOverlapping()
                 ->onOneServer();

        // [AUDIT FIX] Critical Financial Job: Profit Sharing
        // Added safety locks similar to AutoDebits
        if (class_exists(ProcessProfitShareDistribution::class)) {
            $schedule->command(ProcessProfitShareDistribution::class)
                     ->monthlyOn(1, '00:00')
                     ->withoutOverlapping()
                     ->onOneServer();
        }

        $schedule->command(ProcessCelebrationBonuses::class)->dailyAt('08:00');
        
        $schedule->command(CheckTicketSLACommand::class)->hourly();
        
        $schedule->command(ProcessMonthlyLuckyDraw::class)->dailyAt('04:00');
        
        $schedule->command(GenerateSitemap::class)->dailyAt('03:00');

        $schedule->command(UpdateProductPrices::class)->everyFourHours();

        // Process pending webhook retries every 5 minutes
        // withoutOverlapping is good here too to prevent queue stuffing
        $schedule->command(ProcessPendingWebhooks::class)
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // V-AUDIT-MODULE4-007 (LOW) - Changed from weekly to daily for better performance
        // Prune old webhook logs daily (logs older than 90 days as configured in WebhookLog model)
        // Running daily prevents large bulk deletes and distributes the database load
        $schedule->command('model:prune', ['--model' => 'App\\Models\\WebhookLog'])
                 ->daily()
                 ->withoutOverlapping();

        // FIX 18: Release expired fund locks hourly
        // Ensures funds locked for withdrawals are released if approval window expires
        $schedule->command(ReleaseExpiredFundLocks::class)
                 ->hourly()
                 ->withoutOverlapping();

        // FIX 32: Balance reconciliation checks daily at 2 AM (low traffic)
        // Verifies wallet balances, fund locks, and admin ledger integrity
        // Alerts admins if critical discrepancies found
        $schedule->command(ReconcileBalances::class, ['--alert'])
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->onOneServer();

        // V-PRECISION-2026: Wallet-Ledger Reconciliation daily at 2:30 AM
        // Compares wallet.balance_paise against double-entry ledger SUM
        // Returns non-zero exit code on discrepancy (triggers CI/monitoring alerts)
        // Read-only operation - safe for production
        $schedule->command(WalletReconcileCommand::class)
                 ->dailyAt('02:30')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->emailOutputOnFailure(config('mail.admin_email'));

        // FRESHNESS MODEL: Daily disclosure freshness refresh at 3 AM
        // Recomputes freshness_state for all approved disclosures
        // Records pillar vitality snapshots for audit trail
        // FROZEN VOCABULARY: current|aging|stale|unstable
        $schedule->call(function () {
            app(DisclosureFreshnessService::class)->refreshAllFreshnessStates();
        })
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->onOneServer()
            ->name('disclosure-freshness-refresh')
            ->description('Refresh disclosure freshness states and record vitality snapshots');

        // V-DISPUTE-RISK-2026-008: Warm dispute stats cache hourly
        // Pre-computes dispute/chargeback statistics for admin dashboard
        // Ensures fresh data is always available without blocking requests
        $schedule->command(WarmDisputeStatsCache::class)
                 ->hourly()
                 ->withoutOverlapping()
                 ->onOneServer();

        // V-DISPUTE-RISK-2026-009: Aggregate daily dispute snapshots at 2 AM
        // Creates immutable daily records for trend analysis and reporting
        // Must run after midnight to capture full previous day data
        $schedule->command(AggregateDailyDisputeSnapshots::class)
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}