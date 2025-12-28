<?php

namespace App\Console\Commands;

use App\Services\SystemHealthMonitoringService;
use Illuminate\Console\Command;

/**
 * MonitorSystemHealth Command - System Health Monitoring (H.26)
 *
 * PURPOSE:
 * - Check all system health metrics
 * - Display dashboard data
 * - Alert on critical issues
 *
 * USAGE:
 * ```bash
 * # Check all metrics
 * php artisan system:health

 * # Check specific category
 * php artisan system:health --category=financial
 *
 * # Get dashboard data
 * php artisan system:health --dashboard=financial_health
 *
 * # Check and alert if critical
 * php artisan system:health --alert-on-critical
 * ```
 *
 * SCHEDULE:
 * Add to app/Console/Kernel.php:
 * ```php
 * $schedule->command('system:health --alert-on-critical')
 *          ->everyFiveMinutes();
 * ```
 */
class MonitorSystemHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health
                            {--category= : Check specific category (financial, operational, system)}
                            {--dashboard= : Get dashboard data (financial_health, operations, system)}
                            {--alert-on-critical : Alert if critical issues found}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system health metrics (H.26) - financial, operational, and system health';

    /**
     * Execute the console command.
     */
    public function handle(SystemHealthMonitoringService $monitor): int
    {
        // Dashboard mode
        if ($this->option('dashboard')) {
            return $this->showDashboard($monitor, $this->option('dashboard'));
        }

        // Check metrics
        $this->info('===========================================');
        $this->info('System Health Monitoring (H.26)');
        $this->info('===========================================');
        $this->newLine();

        $health = $monitor->checkAllMetrics();

        // JSON output
        if ($this->option('json')) {
            $this->line(json_encode($health, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Overall health
        $this->displayOverallHealth($health);

        // Display metrics by category
        $category = $this->option('category');

        if ($category) {
            if (!isset($health['metrics'][$category])) {
                $this->error("Invalid category: {$category}");
                return Command::FAILURE;
            }

            $this->displayCategoryMetrics($category, $health['metrics'][$category]);
        } else {
            foreach ($health['metrics'] as $cat => $metrics) {
                $this->displayCategoryMetrics($cat, $metrics);
                $this->newLine();
            }
        }

        // Critical issues
        if (!empty($health['critical_issues'])) {
            $this->newLine();
            $this->displayCriticalIssues($health['critical_issues']);

            if ($this->option('alert-on-critical')) {
                $this->alert('CRITICAL ISSUES DETECTED! Check system immediately.');
                // TODO: Send notification to admins
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Display overall health
     */
    private function displayOverallHealth(array $health): void
    {
        $overallHealth = $health['overall_health'];

        $this->info('Overall Health: ' . strtoupper($overallHealth));

        if ($overallHealth === 'healthy') {
            $this->line('✓ All systems operational');
        } else {
            $this->error('✗ System health degraded');
        }

        $this->line('Checked at: ' . $health['checked_at']);
        $this->newLine();
    }

    /**
     * Display category metrics
     */
    private function displayCategoryMetrics(string $category, array $metrics): void
    {
        $this->info(strtoupper($category) . ' Health:');
        $this->line(str_repeat('─', 50));

        $rows = [];
        foreach ($metrics as $name => $metric) {
            $status = $metric['is_healthy'] ? '✓' : '✗';
            $severity = $metric['severity'];
            $value = $metric['current_value'];
            $unit = $metric['unit'] ?? '';

            $valueStr = is_numeric($value) ? number_format($value, 2) : $value;
            if ($unit) {
                $valueStr .= " {$unit}";
            }

            $rows[] = [
                $status,
                ucwords(str_replace('_', ' ', $name)),
                $valueStr,
                ucfirst($severity),
                substr($metric['message'], 0, 40),
            ];
        }

        $this->table(
            ['', 'Metric', 'Value', 'Severity', 'Message'],
            $rows
        );
    }

    /**
     * Display critical issues
     */
    private function displayCriticalIssues(array $issues): void
    {
        $this->error('===========================================');
        $this->error('CRITICAL ISSUES DETECTED');
        $this->error('===========================================');

        foreach ($issues as $issue) {
            $this->error("• {$issue['name']}: {$issue['message']}");
        }
    }

    /**
     * Show dashboard
     */
    private function showDashboard(SystemHealthMonitoringService $monitor, string $dashboardName): int
    {
        $dashboard = $monitor->getDashboardData($dashboardName);

        if ($this->option('json')) {
            $this->line(json_encode($dashboard, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->info('===========================================');
        $this->info($dashboard['name']);
        $this->info('===========================================');
        $this->newLine();

        $this->info('Overall Health: ' . strtoupper($dashboard['overall_health']));
        $this->newLine();

        // Display metrics
        if (isset($dashboard['metrics'])) {
            $this->displayCategoryMetrics($dashboardName, $dashboard['metrics']);
        }

        // Display alerts
        if (isset($dashboard['alerts']) && !empty($dashboard['alerts'])) {
            $this->newLine();
            $this->warn('Active Alerts:');

            $alertRows = [];
            foreach ($dashboard['alerts'] as $alert) {
                $alertRows[] = [
                    $alert['severity'],
                    $alert['alert_type'],
                    "{$alert['entity_type']}#{$alert['entity_id']}",
                    substr($alert['description'], 0, 40),
                ];
            }

            $this->table(
                ['Severity', 'Type', 'Entity', 'Description'],
                $alertRows
            );
        }

        return Command::SUCCESS;
    }
}
