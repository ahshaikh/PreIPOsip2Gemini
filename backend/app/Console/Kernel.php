<?php
// V-REMEDIATE-1730-165 (Created) | V-FINAL-1730-280 | V-FINAL-1730-385 (SLA Job Added) | V-FINAL-1730-427 (Draw Job Added) | V-FINAL-1730-431 (Sitemap Added)

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcessCelebrationBonuses;
use App\Console\Commands\ProcessAutoDebits;
use App\Console\Commands\GenerateSitemap; // <-- 1. IMPORT
use App\Console\Commands\CheckTicketSLACommand;
use App\Console\Commands\ProcessMonthlyLuckyDraw;

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

        // --- 2. ADD NEW SCHEDULE (Run at 3 AM) ---
        $schedule->command(GenerateSitemap::class)->dailyAt('03:00');
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