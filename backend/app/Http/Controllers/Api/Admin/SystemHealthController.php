<?php
// V-FINAL-1730-225

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;

class SystemHealthController extends Controller
{
    public function index()
    {
        // 1. Database Check
        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            $dbTime = round((microtime(true) - $dbStart) * 1000, 2);
            $dbStatus = 'healthy';
        } catch (\Exception $e) {
            $dbStatus = 'error';
            $dbTime = 0;
        }

        // 2. Cache/Redis Check
        try {
            Cache::put('health_check', 'ok', 10);
            $cacheStatus = Cache::get('health_check') === 'ok' ? 'healthy' : 'error';
        } catch (\Exception $e) {
            $cacheStatus = 'error';
        }

        // 3. Queue Check
        // Note: 'default' is the default queue name
        try {
            $queueSize = Queue::size('default');
            $queueStatus = 'healthy';
        } catch (\Exception $e) {
            $queueSize = -1;
            $queueStatus = 'error';
        }

        // 4. Storage Check (Disk Space)
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsage = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

        return response()->json([
            'database' => [
                'status' => $dbStatus,
                'latency_ms' => $dbTime,
                'connection' => config('database.default')
            ],
            'cache' => [
                'status' => $cacheStatus,
                'driver' => config('cache.default')
            ],
            'queue' => [
                'status' => $queueStatus,
                'pending_jobs' => $queueSize,
                'driver' => config('queue.default')
            ],
            'storage' => [
                'status' => $diskUsage > 90 ? 'warning' : 'healthy',
                'usage_percent' => $diskUsage,
                'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2)
            ],
            'server_time' => now()->toDateTimeString(),
            'php_version' => phpversion()
        ]);
    }
}