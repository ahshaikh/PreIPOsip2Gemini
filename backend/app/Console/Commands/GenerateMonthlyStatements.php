<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StatementGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * FIX 14 (P3): Generate Monthly Statements Command
 *
 * Automatically generates monthly transaction statements for all active users
 * Can be scheduled to run on 1st of every month
 *
 * Usage:
 * php artisan statements:generate-monthly
 * php artisan statements:generate-monthly --year=2024 --month=1
 */
class GenerateMonthlyStatements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statements:generate-monthly
                            {--year= : The year for which to generate statements}
                            {--month= : The month for which to generate statements}
                            {--user= : Generate for specific user ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly transaction statements for all users';

    /**
     * Execute the console command.
     */
    public function handle(StatementGeneratorService $statementService)
    {
        $year = $this->option('year') ?? now()->subMonth()->year;
        $month = $this->option('month') ?? now()->subMonth()->month;
        $userId = $this->option('user');

        $this->info("Generating monthly statements for {$year}-{$month}...");

        // Get users to generate statements for
        $query = User::where('status', 'active');

        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('No users found.');
            return 0;
        }

        $this->info("Processing {$users->count()} users...");

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            try {
                $path = $statementService->generateMonthlyStatement($user, $year, $month);

                Log::info("Monthly statement generated for user {$user->id}", [
                    'user_id' => $user->id,
                    'year' => $year,
                    'month' => $month,
                    'path' => $path,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to generate statement for user {$user->id}", [
                    'user_id' => $user->id,
                    'year' => $year,
                    'month' => $month,
                    'error' => $e->getMessage(),
                ]);

                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully generated: {$successCount} statements");

        if ($errorCount > 0) {
            $this->error("✗ Failed: {$errorCount} statements");
        }

        return 0;
    }
}
