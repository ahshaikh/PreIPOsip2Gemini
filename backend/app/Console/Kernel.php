<?php
// V-REMEDIATE-1730-165

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// --- 1. IMPORT THE NEW COMMAND ---
use App\Console\Commands\ProcessCelebrationBonuses;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // --- 2. ADD THE NEW SCHEDULE ---
        $schedule->command(ProcessCelebrationBonuses::class)->dailyAt('08:00');

        // TODO: Add schedules for other missing jobs like:
        // $schedule->command('app:run-monthly-lucky-draw')->monthlyOn(28, '23:00');
        // $schedule->command('app:calculate-quarterly-profit')->quarterly();
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