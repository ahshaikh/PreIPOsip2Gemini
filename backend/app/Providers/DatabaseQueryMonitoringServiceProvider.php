<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;

class DatabaseQueryMonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only enable in non-production or when explicitly enabled
        if (!config('database.query_monitoring.enabled', false)) {
            return;
        }

        DB::listen(function (QueryExecuted $query) {
            $this->logSlowQuery($query);
            $this->detectNPlusOneQueries($query);
        });
    }

    /**
     * Log slow queries for performance analysis.
     */
    protected function logSlowQuery(QueryExecuted $query): void
    {
        $threshold = config('database.query_monitoring.slow_query_threshold', 1000); // milliseconds

        if ($query->time >= $threshold) {
            Log::channel('performance')->warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
                'connection' => $query->connectionName,
                'trace' => $this->getStackTrace(),
            ]);

            // Store in cache for dashboard
            $this->storeSlowQueryMetric($query);
        }
    }

    /**
     * Detect potential N+1 query issues.
     */
    protected function detectNPlusOneQueries(QueryExecuted $query): void
    {
        static $queryPatterns = [];
        static $requestStartTime = null;

        if ($requestStartTime === null) {
            $requestStartTime = microtime(true);
        }

        // Extract query pattern (SQL without bindings)
        $pattern = preg_replace('/\s+/', ' ', trim($query->sql));

        if (!isset($queryPatterns[$pattern])) {
            $queryPatterns[$pattern] = [
                'count' => 0,
                'total_time' => 0,
                'first_seen' => microtime(true),
            ];
        }

        $queryPatterns[$pattern]['count']++;
        $queryPatterns[$pattern]['total_time'] += $query->time;

        // Alert if same query pattern executed multiple times
        $nPlusOneThreshold = config('database.query_monitoring.n_plus_one_threshold', 10);

        if ($queryPatterns[$pattern]['count'] >= $nPlusOneThreshold) {
            Log::channel('performance')->warning('Potential N+1 query detected', [
                'pattern' => $pattern,
                'count' => $queryPatterns[$pattern]['count'],
                'total_time' => $queryPatterns[$pattern]['total_time'] . 'ms',
                'connection' => $query->connectionName,
                'trace' => $this->getStackTrace(),
            ]);

            // Reset counter to avoid spam
            $queryPatterns[$pattern]['count'] = 0;
        }
    }

    /**
     * Store slow query metrics for dashboard.
     */
    protected function storeSlowQueryMetric(QueryExecuted $query): void
    {
        $key = 'performance:slow_queries:' . date('Y-m-d-H');
        $data = [
            'sql' => $query->sql,
            'time' => $query->time,
            'timestamp' => now()->toIso8601String(),
            'connection' => $query->connectionName,
        ];

        // Store in Redis with 24-hour expiration
        try {
            \Cache::store('redis')->put(
                $key,
                array_merge(
                    \Cache::store('redis')->get($key, []),
                    [$data]
                ),
                now()->addHours(24)
            );
        } catch (\Exception $e) {
            // Fail silently if cache is unavailable
            Log::debug('Failed to store slow query metric', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get relevant stack trace for debugging.
     */
    protected function getStackTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        return array_map(function ($item) {
            return [
                'file' => $item['file'] ?? 'unknown',
                'line' => $item['line'] ?? 0,
                'function' => ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? ''),
            ];
        }, array_slice($trace, 5, 5)); // Skip first 5 framework calls
    }
}
