<?php

namespace App\Console\Commands;

use App\Services\ReconciliationService;
use Illuminate\Console\Command;

class RunReconciliation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reconciliation:run
                            {--force : Force run even if already run today}';

    /**
     * The console command description.
     */
    protected $description = 'Run daily financial reconciliation checks';

    protected ReconciliationService $reconciliationService;

    /**
     * Create a new command instance.
     */
    public function __construct(ReconciliationService $reconciliationService)
    {
        parent::__construct();
        $this->reconciliationService = $reconciliationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting daily reconciliation...');
        $this->newLine();

        // Check if already run today (unless --force)
        if (!$this->option('force')) {
            $lastRun = \DB::table('reconciliation_logs')
                ->where('run_date', now()->toDateString())
                ->exists();

            if ($lastRun) {
                $this->warn('Reconciliation already run today. Use --force to run again.');
                return self::FAILURE;
            }
        }

        $startTime = microtime(true);

        try {
            $result = $this->reconciliationService->runDailyReconciliation();

            $duration = round(microtime(true) - $startTime, 2);

            $this->displayResults($result, $duration);

            return $result['success'] ? self::SUCCESS : self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Reconciliation failed with exception:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Display reconciliation results
     */
    protected function displayResults(array $result, float $duration): void
    {
        if ($result['success']) {
            $this->info('✓ Reconciliation completed successfully!');
        } else {
            $this->error('✗ Reconciliation failed with errors!');
        }

        $this->newLine();

        // Display stats
        $this->info('Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Duration', $duration . 's'],
                ['Checks Performed', $result['stats']['checks_performed']],
                ['Wallets Checked', $result['stats']['wallets_checked']],
                ['Bulk Purchases Checked', $result['stats']['bulk_purchases_checked']],
                ['Errors', $result['stats']['error_count']],
                ['Warnings', $result['stats']['warning_count']],
            ]
        );

        // Display errors
        if (!empty($result['errors'])) {
            $this->newLine();
            $this->error('ERRORS:');
            foreach ($result['errors'] as $error) {
                $this->error('  [' . strtoupper($error['severity']) . '] ' . $error['message']);
                $this->line('    Type: ' . $error['type']);
            }
        }

        // Display warnings
        if (!empty($result['warnings'])) {
            $this->newLine();
            $this->warn('WARNINGS:');
            foreach ($result['warnings'] as $warning) {
                $this->warn('  [' . strtoupper($warning['severity']) . '] ' . $warning['message']);
            }
        }

        $this->newLine();

        if (!$result['success']) {
            $this->error('Action required: Check logs and resolve data integrity issues.');
        }
    }
}
