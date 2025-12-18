<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-PREDICTIVE-MONITORING | V-INFO-LEAK-PROTECTION
 * * ARCHITECTURAL FIX: 
 * Moves beyond binary (Up/Down) to threshold-based health warnings.
 * Protects system metadata from unauthorized discovery.
 */

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HardenedHealthService
{
    /**
     * Get health status with tiered detail levels.
     */
    public function getHealthReport(bool $isInternal = false): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'storage'  => $this->checkDiskSpace(),
        ];

        $isHealthy = !in_array('unhealthy', array_column($checks, 'status'));

        // [SECURITY FIX]: Obfuscate details for external monitors
        if (!$isInternal) {
            return [
                'status' => $isHealthy ? 'operational' : 'degraded',
                'timestamp' => now()->toIso8601String()
            ];
        }

        return [
            'status' => $isHealthy ? 'healthy' : 'warning',
            'components' => $checks,
            'meta' => [
                'php_version' => PHP_VERSION,
                'queue_backlog' => $this->getQueueDepth()
            ]
        ];
    }

    private function checkDiskSpace(): array
    {
        $free = disk_free_space(storage_path());
        $total = disk_total_space(storage_path());
        $usage = 100 - (($free / $total) * 100);

        // [ARCHITECTURAL FIX]: Predictive warning threshold
        return [
            'status' => $usage > 90 ? 'warning' : 'healthy',
            'usage_percent' => round($usage, 2)
        ];
    }

    private function checkDatabase(): array {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => 'Connection Failed'];
        }
    }

    private function checkRedis(): array {
        try {
            Redis::connection()->ping();
            return ['status' => 'healthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy'];
        }
    }

    private function getQueueDepth(): int {
        return Redis::llen('queues:default');
    }
}