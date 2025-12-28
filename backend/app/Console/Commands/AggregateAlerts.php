<?php

namespace App\Console\Commands;

use App\Services\AlertRootCauseAnalyzer;
use Illuminate\Console\Command;

/**
 * AggregateAlerts Command - Alert Aggregation (Addressing Audit Feedback)
 *
 * PURPOSE:
 * - Group similar alerts by root cause
 * - Prevent alert fatigue by showing systemic issues instead of symptoms
 * - Help admins focus on fixing root causes, not individual alerts
 *
 * USAGE:
 * ```bash
 * # Identify root causes for all unresolved alerts
 * php artisan alerts:aggregate
 *
 * # Show aggregated view (grouped by root cause)
 * php artisan alerts:aggregate --summary
 *
 * # Mark root cause as resolved
 * php artisan alerts:aggregate --resolve={root_cause_id} --notes="..."
 * ```
 *
 * SCHEDULE (recommended):
 * ```php
 * // Run every 15 minutes to identify emerging patterns
 * $schedule->command('alerts:aggregate')->everyFifteenMinutes();
 * ```
 */
class AggregateAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:aggregate
                            {--summary : Show aggregated summary instead of identifying new root causes}
                            {--resolve= : Mark root cause as resolved}
                            {--notes= : Resolution notes for marking root cause resolved}
                            {--last= : Show alerts from last N days (default: 1)}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate alerts by root cause to prevent alert fatigue';

    /**
     * Execute the console command.
     */
    public function handle(AlertRootCauseAnalyzer $analyzer): int
    {
        // Resolve mode
        if ($this->option('resolve')) {
            return $this->resolveRootCause($analyzer);
        }

        // Summary mode
        if ($this->option('summary')) {
            return $this->showSummary($analyzer);
        }

        // Identify new root causes mode (default)
        return $this->identifyRootCauses($analyzer);
    }

    /**
     * Identify root causes for unresolved alerts
     *
     * @param AlertRootCauseAnalyzer $analyzer
     * @return int
     */
    private function identifyRootCauses(AlertRootCauseAnalyzer $analyzer): int
    {
        $this->info('===========================================');
        $this->info('Alert Root Cause Analysis');
        $this->info('===========================================');
        $this->newLine();

        $this->info('Analyzing unresolved alerts for patterns...');

        $result = $analyzer->identifyRootCauses();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        if (empty($result['root_causes'])) {
            $this->info('✓ No systemic issues detected');
            $this->line("Ungrouped alerts: {$result['ungrouped_alerts']}");
            return Command::SUCCESS;
        }

        $this->warn("Found {count($result['root_causes'])} systemic issues:");
        $this->newLine();

        foreach ($result['root_causes'] as $rootCause) {
            $this->displayRootCause($rootCause);
        }

        $this->newLine();
        $this->line("Ungrouped alerts: {$result['ungrouped_alerts']}");

        return Command::SUCCESS;
    }

    /**
     * Show aggregated summary
     *
     * @param AlertRootCauseAnalyzer $analyzer
     * @return int
     */
    private function showSummary(AlertRootCauseAnalyzer $analyzer): int
    {
        $this->info('===========================================');
        $this->info('Alert Aggregation Summary');
        $this->info('===========================================');
        $this->newLine();

        $summary = $analyzer->getAggregatedAlerts();

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        if (empty($summary['root_causes'])) {
            $this->info('✓ No active root causes');
            return Command::SUCCESS;
        }

        $this->table(
            ['ID', 'Type', 'Severity', 'Alerts', 'Monetary Impact', 'Users', 'Duration'],
            collect($summary['root_causes'])->map(function ($rc) {
                return [
                    $rc['id'],
                    $rc['type'],
                    strtoupper($rc['severity']),
                    $rc['affected_alerts'],
                    '₹' . number_format($rc['monetary_impact'], 2),
                    $rc['users_affected'],
                    $rc['duration'],
                ];
            })->toArray()
        );

        $this->newLine();
        $this->line("Ungrouped alerts: {$summary['ungrouped_alerts']}");
        $this->newLine();

        $this->info('To resolve a root cause:');
        $this->line('php artisan alerts:aggregate --resolve={id} --notes="Fixed by restarting service"');

        return Command::SUCCESS;
    }

    /**
     * Mark root cause as resolved
     *
     * @param AlertRootCauseAnalyzer $analyzer
     * @return int
     */
    private function resolveRootCause(AlertRootCauseAnalyzer $analyzer): int
    {
        $rootCauseId = $this->option('resolve');
        $notes = $this->option('notes');

        if (!$notes) {
            $this->error('Resolution notes required. Use --notes="..."');
            return Command::FAILURE;
        }

        // TODO: Get current admin user ID (for now, use 1)
        $adminId = 1;

        $analyzer->markRootCauseResolved($rootCauseId, $adminId, $notes);

        $this->info("✓ Root cause #{$rootCauseId} marked as resolved");
        $this->line("Resolution notes: {$notes}");

        return Command::SUCCESS;
    }

    /**
     * Display root cause details
     *
     * @param array $rootCause
     * @return void
     */
    private function displayRootCause(array $rootCause): void
    {
        $this->warn("ROOT CAUSE: {$rootCause['type']}");
        $this->line("  Affected: {$rootCause['affected_count']} alerts");

        if (isset($rootCause['monetary_impact'])) {
            $this->line("  Monetary impact: ₹" . number_format($rootCause['monetary_impact'], 2));
        }

        if (isset($rootCause['users_affected'])) {
            $this->line("  Users affected: {$rootCause['users_affected']}");
        }

        $this->newLine();
    }
}
