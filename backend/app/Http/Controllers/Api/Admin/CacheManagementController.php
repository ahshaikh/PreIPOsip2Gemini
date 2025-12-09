<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;

class CacheManagementController extends Controller
{
    /**
     * Get cache statistics
     * GET /api/v1/admin/cache/stats
     */
    public function getStats()
    {
        $driver = config('cache.default');

        $stats = [
            'driver' => $driver,
            'enabled' => setting('cache_enabled', true),
            'prefix' => setting('cache_prefix', 'preipo_'),
            'ttl' => setting('cache_ttl', 3600),
        ];

        // Get Redis stats if using Redis
        if ($driver === 'redis') {
            try {
                $redis = Redis::connection();
                $info = $redis->info();

                $stats['redis'] = [
                    'version' => $info['redis_version'] ?? 'N/A',
                    'used_memory' => $this->formatBytes($info['used_memory'] ?? 0),
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_keys' => $redis->dbsize(),
                    'uptime_days' => round(($info['uptime_in_seconds'] ?? 0) / 86400, 2),
                ];
            } catch (\Exception $e) {
                $stats['redis'] = ['error' => $e->getMessage()];
            }
        }

        return response()->json($stats);
    }

    /**
     * Clear all cache
     * POST /api/v1/admin/cache/clear
     */
    public function clearAll()
    {
        try {
            Cache::flush();
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'message' => 'All cache cleared successfully',
                'cleared' => [
                    'application_cache',
                    'config_cache',
                    'route_cache',
                    'view_cache',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear specific cache by tag
     * POST /api/v1/admin/cache/clear-tag
     */
    public function clearByTag(Request $request)
    {
        $validated = $request->validate([
            'tag' => 'required|string',
        ]);

        try {
            Cache::tags($validated['tag'])->flush();

            return response()->json([
                'message' => "Cache cleared for tag: {$validated['tag']}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear tagged cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear specific cache by key
     * POST /api/v1/admin/cache/clear-key
     */
    public function clearByKey(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        try {
            $result = Cache::forget($validated['key']);

            if ($result) {
                return response()->json([
                    'message' => "Cache key '{$validated['key']}' cleared successfully",
                ]);
            } else {
                return response()->json([
                    'message' => "Cache key '{$validated['key']}' not found",
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache key',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear config cache
     * POST /api/v1/admin/cache/clear-config
     */
    public function clearConfig()
    {
        try {
            Artisan::call('config:clear');

            return response()->json([
                'message' => 'Configuration cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear config cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear route cache
     * POST /api/v1/admin/cache/clear-routes
     */
    public function clearRoutes()
    {
        try {
            Artisan::call('route:clear');

            return response()->json([
                'message' => 'Route cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear route cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear view cache
     * POST /api/v1/admin/cache/clear-views
     */
    public function clearViews()
    {
        try {
            Artisan::call('view:clear');

            return response()->json([
                'message' => 'View cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear view cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cache all routes (optimize)
     * POST /api/v1/admin/cache/cache-routes
     */
    public function cacheRoutes()
    {
        try {
            Artisan::call('route:cache');

            return response()->json([
                'message' => 'Routes cached successfully for better performance',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cache routes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cache all config (optimize)
     * POST /api/v1/admin/cache/cache-config
     */
    public function cacheConfig()
    {
        try {
            Artisan::call('config:cache');

            return response()->json([
                'message' => 'Configuration cached successfully for better performance',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cache config',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of available cache tags
     * GET /api/v1/admin/cache/tags
     */
    public function getTags()
    {
        // Common tags used in the application
        $tags = [
            'settings',
            'users',
            'payments',
            'subscriptions',
            'plans',
            'kyc',
            'bonuses',
            'withdrawals',
            'reports',
            'dashboard',
        ];

        return response()->json(['tags' => $tags]);
    }

    /**
     * Test cache performance
     * POST /api/v1/admin/cache/test
     */
    public function testPerformance()
    {
        $results = [];

        // Test write performance
        $writeStart = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            Cache::put("test_key_{$i}", "test_value_{$i}", 60);
        }
        $writeTime = (microtime(true) - $writeStart) * 1000;
        $results['write_100_keys_ms'] = round($writeTime, 2);

        // Test read performance
        $readStart = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            Cache::get("test_key_{$i}");
        }
        $readTime = (microtime(true) - $readStart) * 1000;
        $results['read_100_keys_ms'] = round($readTime, 2);

        // Clean up test keys
        for ($i = 0; $i < 100; $i++) {
            Cache::forget("test_key_{$i}");
        }

        $results['status'] = 'healthy';
        if ($writeTime > 1000 || $readTime > 1000) {
            $results['status'] = 'slow';
        }

        return response()->json($results);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
