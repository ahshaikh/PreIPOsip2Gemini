<?php
// V-FINAL-1730-280

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcessCelebrationBonuses;
use App\Console\Commands\ProcessAutoDebits;
use App\Console\Commands\GenerateSitemap; // <-- IMPORT

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Existing schedules
        $schedule->command(ProcessCelebrationBonuses::class)->dailyAt('08:00');
        $schedule->command('app:process-auto-debits')->dailyAt('09:00');
        
        // --- NEW: Sitemap Generation ---
        $schedule->command(GenerateSitemap::class)->dailyAt('03:00');
        // -------------------------------
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