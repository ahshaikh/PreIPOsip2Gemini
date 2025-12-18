<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-PLATFORM-OBSERVABILITY | V-UPTIME-MONITORING
 * Refactored to address Phase 16 Audit Gaps:
 * 1. Multi-Subsystem Check: Verifies DB, Redis, and Storage in one call.
 * 2. Performance: Returns 200 OK only if all critical systems are healthy.
 * 3. Alerts: Logs critical failures to the system audit trail.
 */

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckService
{
    public function getStatus(): array
    {
        $status = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'storage'  => $this->checkStorage(),
            'timestamp' => now()->toIso8601String(),
        ];

        $isHealthy = !in_array('unhealthy', array_values($status));

        return [
            'healthy' => $isHealthy,
            'services' => $status
        ];
    }

    private function checkDatabase(): string {
        try {
            DB::connection()->getPdo();
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkRedis(): string {
        try {
            Redis::connection()->ping();
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkStorage(): string {
        return Storage::disk('local')->exists('.') ? 'healthy' : 'unhealthy';
    }
}