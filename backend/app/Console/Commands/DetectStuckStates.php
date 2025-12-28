<?php

namespace App\Console\Commands;

use App\Services\StuckStateDetectorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * DetectStuckStates Command - Detect and Escalate Stuck States (G.24)
 *
 * PURPOSE:
 * - Detect stuck payments, investments, bonuses, workflows
 * - Auto-resolve where possible
 * - Escalate to manual review queue
 * - Notify admins of critical stuck states
 *
 * USAGE:
 * ```bash
 * # Detect stuck states (no resolution)
 * php artisan stuck-states:detect
 *
 * # Detect and auto-resolve
 * php artisan stuck-states:detect --auto-resolve
 *
 * # Dry run (show what would be done)
 * php artisan stuck-states:detect --auto-resolve --dry-run
 *
 * # Show manual review queue
 * php artisan stuck-states:detect --show-queue
 * ```
 *
 * SCHEDULE:
 * Add to app/Console/Kernel.php:
 * ```php
 * $schedule->command('stuck-states:detect --auto-resolve')
 *          ->everyFifteenMinutes()
 *          ->withoutOverlapping();
 * ```
 */
class DetectStuckStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stuck-states:detect
                            {--auto-resolve : Automatically resolve stuck states where possible}
                            {--dry-run : Show what would be done without actually doing it}
                            {--show-queue : Show manual review queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and escalate stuck states (G.24) - payments, investments, bonuses, workflows';

    /**
     * Execute the console command.
     */
    public function handle(StuckStateDetectorService $detector): int
    {
        $this->info('===========================================');
        $this->info('Stuck State Detection (G.24)');
        $this->info('===========================================');
        $this->newLine();

        // Show manual review queue
        if ($this->option('show-queue')) {
            return $this->showManualReviewQueue($detector);
        }

        // Detect all stuck states
        $this->info('Detecting stuck states...');
        $stuckStates = $detector->detectAllStuckStates();

        // Display results
        $this->displayStuckStates($stuckStates);

        // Auto-resolve if requested
        if ($this->option('auto-resolve')) {
            $this->newLine();
            $this->info('Auto-resolving stuck states...');

            if ($this->option('dry-run')) {
                $this->warn('DRY RUN MODE - No changes will be made');
            } else {
                $results = $detector->autoResolveStuckStates();

                $this->info("✓ Resolved: {$results['resolved']}");
                $this->warn("⚠ Escalated to manual review: {$results['escalated']}");
                $this->error("✗ Failed: {$results['failed']}");

                if ($results['escalated'] > 0) {
                    $this->newLine();
                    $this->warn("Manual review required for {$results['escalated']} stuck states.");
                    $this->info("View queue: php artisan stuck-states:detect --show-queue");
                }
            }
        }

        // Display statistics
        $this->newLine();
        $this->displayStatistics($detector);

        return Command::SUCCESS;
    }

    /**
     * Display stuck states
     */
    private function displayStuckStates(array $stuckStates): void
    {
        $totalStuck = 0;

        // Payments
        $paymentsCount = count($stuckStates['payments']);
        $totalStuck += $paymentsCount;
        $this->info("Stuck Payments: {$paymentsCount}");

        if ($paymentsCount > 0 && $this->option('verbose')) {
            foreach ($stuckStates['payments'] as $stuck) {
                $this->line("  - Payment #{$stuck} (see database for details)");
            }
        }

        // Investments
        $investmentsCount = count($stuckStates['investments']);
        $totalStuck += $investmentsCount;
        $this->info("Stuck Investments: {$investmentsCount}");

        if ($investmentsCount > 0 && $this->option('verbose')) {
            foreach ($stuckStates['investments'] as $stuck) {
                $this->line("  - Investment #{$stuck} (see database for details)");
            }
        }

        // Bonuses
        $bonusesCount = count($stuckStates['bonuses']);
        $totalStuck += $bonusesCount;
        $this->info("Stuck Bonuses: {$bonusesCount}");

        if ($bonusesCount > 0 && $this->option('verbose')) {
            foreach ($stuckStates['bonuses'] as $stuck) {
                $this->line("  - Bonus #{$stuck} (see database for details)");
            }
        }

        // Workflows
        $workflowsCount = count($stuckStates['workflows']);
        $totalStuck += $workflowsCount;
        $this->info("Stuck Workflows: {$workflowsCount}");

        if ($workflowsCount > 0 && $this->option('verbose')) {
            foreach ($stuckStates['workflows'] as $stuck) {
                $this->line("  - Workflow #{$stuck} (see database for details)");
            }
        }

        $this->newLine();
        $this->info("Total Stuck States: {$totalStuck}");
    }

    /**
     * Display statistics
     */
    private function displayStatistics(StuckStateDetectorService $detector): void
    {
        $stats = $detector->getStatistics();

        $this->info('===========================================');
        $this->info('Statistics');
        $this->info('===========================================');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Stuck (Unresolved)', $stats['total_stuck']],
                ['Auto-Resolved', $stats['auto_resolved']],
                ['Manual Review Queue', $stats['manual_review_queue']],
            ]
        );

        if (!empty($stats['by_severity'])) {
            $this->newLine();
            $this->info('By Severity:');
            $this->table(
                ['Severity', 'Count'],
                collect($stats['by_severity'])->map(fn($count, $severity) => [$severity, $count])->toArray()
            );
        }

        if (!empty($stats['by_type'])) {
            $this->newLine();
            $this->info('By Type:');
            $this->table(
                ['Alert Type', 'Count'],
                collect($stats['by_type'])->map(fn($count, $type) => [$type, $count])->toArray()
            );
        }
    }

    /**
     * Show manual review queue
     */
    private function showManualReviewQueue(StuckStateDetectorService $detector): int
    {
        $queue = $detector->getManualReviewQueue();

        $this->info('===========================================');
        $this->info('Manual Review Queue');
        $this->info('===========================================');
        $this->newLine();

        if (empty($queue)) {
            $this->info('✓ No items requiring manual review');
            return Command::SUCCESS;
        }

        $this->table(
            ['ID', 'Type', 'Severity', 'Entity', 'Description', 'Stuck Since'],
            collect($queue)->map(function ($item) {
                return [
                    $item['id'],
                    $item['alert_type'],
                    $item['severity'],
                    "{$item['entity_type']}#{$item['entity_id']}",
                    substr($item['description'], 0, 50) . '...',
                    $item['stuck_duration'],
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info("Total items: " . count($queue));
        $this->warn("Review these in Admin Panel → Stuck States → Manual Review Queue");

        return Command::SUCCESS;
    }
}
