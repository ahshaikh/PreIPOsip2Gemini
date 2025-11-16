<?php
// V-REMEDIATE-1730-165 (Created) | V-FINAL-1730-280 | V-FINAL-1730-385 (SLA Job Added) | V-FINAL-1730-427 (Draw Job Added) | V-FINAL-1730-431 (Sitemap Added) | V-FINAL-1730-496 (Price Job Added)

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcessCelebrationBonuses;
use App\Console\Commands\ProcessAutoDebits;
use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\CheckTicketSLACommand;
use App\Console\Commands\ProcessMonthlyLuckyDraw;
use App\Console\Commands\UpdateProductPrices; // <-- 1. IMPORT

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(ProcessCelebrationBonuses::class)->dailyAt('08:00');
        $schedule->command(ProcessAutoDebits::class)->dailyAt('09:00');
        $schedule->command(CheckTicketSLACommand::class)->hourly();
        $schedule->command(ProcessMonthlyLuckyDraw::class)->dailyAt('04:00');
        $schedule->command(GenerateSitemap::class)->dailyAt('03:00');

        // --- 2. ADD NEW SCHEDULE (Run every 4 hours) ---
        $schedule->command(UpdateProductPrices::class)->everyFourHours();
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