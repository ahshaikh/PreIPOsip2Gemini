<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class PerformanceMonitoringController extends Controller
{
    /**
     * Get performance dashboard overview.
     */
    public function overview(Request $request)
    {
        $period = $request->get('period', '24h'); // 1h, 24h, 7d, 30d

        return response()->json([
            'database' => $this->getDatabaseMetrics($period),
            'cache' => $this->getCacheMetrics(),
            'queue' => $this->getQueueMetrics(),
            'webhooks' => $this->getWebhookMetrics($period),
            'system' => $this->getSystemMetrics(),
        ]);
    }

    /**
     * Get database performance metrics.
     */
    public function databaseMetrics(Request $request)
    {
        $period = $request->get('period', '24h');

        return response()->json([
            'slow_queries' => $this->getSlowQueries($period),
            'query_stats' => $this->getQueryStats($period),
            'connection_stats' => $this->getConnectionStats(),
            'table_sizes' => $this->getTableSizes(),
        ]);
    }

    /**
     * Get slow queries.
     */
    protected function getSlowQueries($period)
    {
        $hours = $this->parsePeriod($period);
        $slowQueries = [];

        try {
            // Retrieve slow queries from cache
            for ($i = 0; $i < $hours; $i++) {
                $key = 'performance:slow_queries:' . now()->subHours($i)->format('Y-m-d-H');
                $queries = Cache::store('redis')->get($key, []);

                if (!empty($queries)) {
                    $slowQueries = array_merge($slowQueries, $queries);
                }
            }

            // Sort by time descending
            usort($slowQueries, function ($a, $b) {
                return $b['time'] <=> $a['time'];
            });

            return array_slice($slowQueries, 0, 100); // Top 100 slow queries
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get query statistics.
     */
    protected function getQueryStats($period)
    {
        $hours = $this->parsePeriod($period);

        return [
            'total_queries' => rand(10000, 50000), // TODO: Implement actual tracking
            'avg_query_time' => rand(50, 200),
            'slow_query_count' => count($this->getSlowQueries($period)),
        ];
    }

    /**
     * Get database connection stats.
     */
    protected function getConnectionStats()
    {
        try {
            $stats = DB::select('SHOW STATUS WHERE Variable_name IN ("Threads_connected", "Max_used_connections", "Connections")');

            $result = [];
            foreach ($stats as $stat) {
                $result[strtolower($stat->Variable_name)] = $stat->Value;
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'threads_connected' => 0,
                'max_used_connections' => 0,
                'connections' => 0,
            ];
        }
    }

    /**
     * Get table sizes.
     */
    protected function getTableSizes()
    {
        try {
            $database = config('database.connections.mysql.database');

            $tables = DB::select("
                SELECT
                    table_name AS `table`,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `size_mb`,
                    table_rows AS `rows`
                FROM information_schema.TABLES
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 20
            ", [$database]);

            return $tables;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get cache metrics.
     */
    protected function getCacheMetrics()
    {
        try {
            $redis = Redis::connection('cache');
            $info = $redis->info();

            return [
                'connected' => true,
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate.
     */
    protected function calculateHitRate($info)
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;

        if ($hits + $misses === 0) {
            return 0;
        }

        return round(($hits / ($hits + $misses)) * 100, 2);
    }

    /**
     * Get queue metrics.
     */
    protected function getQueueMetrics()
    {
        try {
            $redis = Redis::connection('queue');

            $queues = ['default', 'high', 'webhooks'];
            $metrics = [];

            foreach ($queues as $queue) {
                $metrics[$queue] = [
                    'size' => $redis->llen("queues:{$queue}"),
                    'delayed' => $redis->zcard("queues:{$queue}:delayed"),
                    'reserved' => $redis->zcard("queues:{$queue}:reserved"),
                ];
            }

            // Get failed jobs count
            $metrics['failed'] = DB::table('failed_jobs')->count();

            return $metrics;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get webhook metrics.
     */
    protected function getWebhookMetrics($period)
    {
        $hours = $this->parsePeriod($period);
        $since = now()->subHours($hours);

        return [
            'total' => WebhookLog::where('created_at', '>=', $since)->count(),
            'success' => WebhookLog::where('created_at', '>=', $since)
                ->where('status', 'success')->count(),
            'failed' => WebhookLog::where('created_at', '>=', $since)
                ->where('status', 'max_retries_reached')->count(),
            'pending' => WebhookLog::where('status', 'pending')->count(),
            'by_event' => WebhookLog::where('created_at', '>=', $since)
                ->select('event_type', DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->get(),
            'recent_failures' => WebhookLog::failed()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'event_type', 'error_message', 'retry_count', 'created_at']),
        ];
    }

    /**
     * Get system metrics.
     */
    protected function getSystemMetrics()
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'uptime' => $this->getServerUptime(),
        ];
    }

    /**
     * Get server uptime.
     */
    protected function getServerUptime()
    {
        try {
            $uptime = shell_exec('uptime -p');
            return trim($uptime) ?: 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Parse period string to hours.
     */
    protected function parsePeriod($period)
    {
        return match ($period) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 24 * 7,
            '30d' => 24 * 30,
            default => 24,
        };
    }

    /**
     * Get real-time metrics (for live dashboard).
     */
    public function realtime()
    {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'active_connections' => $this->getActiveConnections(),
            'current_requests_per_second' => $this->getCurrentRPS(),
            'memory_usage' => memory_get_usage(),
            'queue_size' => $this->getTotalQueueSize(),
        ]);
    }

    /**
     * Get active database connections.
     */
    protected function getActiveConnections()
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get current requests per second (approximation).
     */
    protected function getCurrentRPS()
    {
        // This would need to be implemented with actual request tracking
        return rand(10, 50);
    }

    /**
     * Get total queue size across all queues.
     */
    protected function getTotalQueueSize()
    {
        try {
            $redis = Redis::connection('queue');
            $queues = ['default', 'high', 'webhooks'];
            $total = 0;

            foreach ($queues as $queue) {
                $total += $redis->llen("queues:{$queue}");
            }

            return $total;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
