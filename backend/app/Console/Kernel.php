<?php
// V-REMEDIATE-1730-165 (Created) | V-FINAL-1730-280 | V-FINAL-1730-385 (SLA Job Added) | V-FINAL-1730-427 (Draw Job Added) | V-FINAL-1730-431 (Sitemap Added) | V-FINAL-1730-496 (Price Job Added) | V-AUDIT-FIX-SCHEDULER-SAFETY

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

        // Prune old webhook logs weekly
        $schedule->command('model:prune', ['--model' => 'App\\Models\\WebhookLog'])->weekly();
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