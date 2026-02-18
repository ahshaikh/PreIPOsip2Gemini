<?php

/**
 * V-DISPUTE-RISK-2026-008: Warm Dispute Stats Cache Command
 *
 * Pre-warms the dispute statistics cache for dashboard performance.
 * Scheduled to run hourly to ensure fresh data is always available.
 */

namespace App\Console\Commands;

use App\Services\DisputeStatsCache;
use Illuminate\Console\Command;

class WarmDisputeStatsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispute:warm-cache
                            {--clear : Clear cache before warming}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm the dispute statistics cache for admin dashboard';

    /**
     * Execute the console command.
     */
    public function handle(DisputeStatsCache $cacheService): int
    {
        $this->info('Warming dispute stats cache...');

        $startTime = microtime(true);

        if ($this->option('clear')) {
            $this->info('Clearing existing cache...');
            $cacheService->clearCache();
        }

        try {
            $cacheService->warmCache();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("Dispute stats cache warmed successfully in {$duration}ms");

            // Output summary
            $stats = $cacheService->getAllStats();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Disputes', $stats['overview']['total_disputes'] ?? 0],
                    ['Active Disputes', $stats['overview']['active_disputes'] ?? 0],
                    ['Total Chargebacks', $stats['chargebacks']['total_confirmed'] ?? 0],
                    ['Blocked Users', $stats['risk_distribution']['blocked_users'] ?? 0],
                    ['High Risk Users', $stats['risk_distribution']['high_risk_users'] ?? 0],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to warm cache: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
