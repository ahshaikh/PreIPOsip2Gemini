<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackDatabaseQueries
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('database.query_monitoring.enabled', false)) {
            return $next($request);
        }

        // Enable query logging
        DB::enableQueryLog();
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalTime = array_sum(array_column($queries, 'time'));

        // Log request performance metrics
        if ($queryCount > 0) {
            $metrics = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $queryCount,
                'query_time' => round($totalTime, 2) . 'ms',
                'request_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
                'memory_used' => $this->formatBytes($endMemory - $startMemory),
                'peak_memory' => $this->formatBytes(memory_get_peak_usage()),
            ];

            // Warn if excessive queries
            $excessiveThreshold = config('database.query_monitoring.excessive_queries_threshold', 50);
            if ($queryCount > $excessiveThreshold) {
                Log::channel('performance')->warning('Excessive database queries', $metrics);
            } else {
                Log::channel('performance')->info('Request performance', $metrics);
            }

            // Add metrics to response headers in debug mode
            if (config('app.debug')) {
                $response->headers->set('X-Database-Queries', $queryCount);
                $response->headers->set('X-Database-Time', round($totalTime, 2) . 'ms');
                $response->headers->set('X-Request-Time', round(($endTime - $startTime) * 1000, 2) . 'ms');
            }
        }

        return $response;
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
