<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\SystemHealthCheck;
use App\Models\PerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;

class SystemMonitorController extends Controller
{
    /**
     * Get system health dashboard
     * GET /api/v1/admin/system/health
     */
    public function healthDashboard()
    {
        $checks = $this->runHealthChecks();

        $overall = $this->calculateOverallHealth($checks);

        // Get latest checks from database
        $recent = SystemHealthCheck::orderBy('checked_at', 'desc')
            ->take(10)
            ->get()
            ->groupBy('check_name');

        return response()->json([
            'overall_status' => $overall,
            'current_checks' => $checks,
            'recent_checks' => $recent,
            'last_checked' => now()->toIso8601String(),
        ]);
    }

    /**
     * Run all health checks
     */
    private function runHealthChecks()
    {
        $checks = [];

        // Database Check
        $checks['database'] = $this->checkDatabase();

        // Cache Check
        $checks['cache'] = $this->checkCache();

        // Storage Check
        $checks['storage'] = $this->checkStorage();

        // Queue Check
        $checks['queue'] = $this->checkQueue();

        // Memory Check
        $checks['memory'] = $this->checkMemory();

        // Disk Space Check
        $checks['disk'] = $this->checkDisk();

        // Save checks to database
        foreach ($checks as $name => $check) {
            SystemHealthCheck::create([
                'check_name' => $name,
                'status' => $check['status'],
                'message' => $check['message'],
                'details' => $check['details'] ?? null,
                'response_time' => $check['response_time'] ?? null,
                'checked_at' => now(),
            ]);
        }

        return $checks;
    }

    private function checkDatabase()
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $count = DB::table('users')->count();
            $responseTime = (int) ((microtime(true) - $start) * 1000);

            return [
                'status' => 'healthy',
                'message' => 'Database connection active',
                'response_time' => $responseTime,
                'details' => ['total_users' => $count],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Database connection failed',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    private function checkCache()
    {
        $start = microtime(true);
        try {
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            $responseTime = (int) ((microtime(true) - $start) * 1000);

            return [
                'status' => $value === 'ok' ? 'healthy' : 'warning',
                'message' => $value === 'ok' ? 'Cache working' : 'Cache not working properly',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Cache not available',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    private function checkStorage()
    {
        try {
            $path = storage_path('app');
            $writable = is_writable($path);

            return [
                'status' => $writable ? 'healthy' : 'critical',
                'message' => $writable ? 'Storage is writable' : 'Storage is not writable',
                'details' => ['path' => $path],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Storage check failed',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    private function checkQueue()
    {
        try {
            $failed = DB::table('failed_jobs')->count();
            $pending = DB::table('jobs')->count();

            $status = 'healthy';
            if ($failed > 100) $status = 'warning';
            if ($failed > 500) $status = 'critical';

            return [
                'status' => $status,
                'message' => "Queue operational",
                'details' => [
                    'pending_jobs' => $pending,
                    'failed_jobs' => $failed,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Queue not available',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    private function checkMemory()
    {
        $used = memory_get_usage(true);
        $limit = ini_get('memory_limit');
        $limitBytes = $this->convertToBytes($limit);

        $percentage = ($used / $limitBytes) * 100;

        $status = 'healthy';
        if ($percentage > 70) $status = 'warning';
        if ($percentage > 90) $status = 'critical';

        return [
            'status' => $status,
            'message' => sprintf('Memory usage: %.1f%%', $percentage),
            'details' => [
                'used' => $this->formatBytes($used),
                'limit' => $limit,
                'percentage' => round($percentage, 1),
            ],
        ];
    }

    private function checkDisk()
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        $percentage = ($used / $total) * 100;

        $status = 'healthy';
        if ($percentage > 70) $status = 'warning';
        if ($percentage > 90) $status = 'critical';

        return [
            'status' => $status,
            'message' => sprintf('Disk usage: %.1f%%', $percentage),
            'details' => [
                'total' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'free' => $this->formatBytes($free),
                'percentage' => round($percentage, 1),
            ],
        ];
    }

    private function calculateOverallHealth($checks)
    {
        $statuses = array_column($checks, 'status');

        if (in_array('down', $statuses)) return 'down';
        if (in_array('critical', $statuses)) return 'critical';
        if (in_array('warning', $statuses)) return 'warning';

        return 'healthy';
    }

    private function convertToBytes($value)
    {
        $unit = strtoupper(substr($value, -1));
        $value = (int) $value;

        switch ($unit) {
            case 'G': return $value * 1024 * 1024 * 1024;
            case 'M': return $value * 1024 * 1024;
            case 'K': return $value * 1024;
            default: return $value;
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get error logs
     * GET /api/v1/admin/system/errors
     */
    public function getErrors(Request $request)
    {
        $query = ErrorLog::with('user:id,username', 'resolver:id,username')
            ->orderBy('created_at', 'desc');

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('resolved')) {
            $query->where('is_resolved', $request->resolved === 'true');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('exception', 'like', "%{$search}%")
                    ->orWhere('file', 'like', "%{$search}%");
            });
        }

        $errors = $query->paginate(50);

        $stats = [
            'total' => ErrorLog::count(),
            'unresolved' => ErrorLog::where('is_resolved', false)->count(),
            'today' => ErrorLog::whereDate('created_at', today())->count(),
            'by_level' => ErrorLog::select('level', DB::raw('count(*) as count'))
                ->groupBy('level')
                ->pluck('count', 'level'),
        ];

        return response()->json([
            'errors' => $errors,
            'stats' => $stats,
        ]);
    }

    /**
     * Mark error as resolved
     * PUT /api/v1/admin/system/errors/{error}/resolve
     */
    public function resolveError(Request $request, ErrorLog $error)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $error->update([
            'is_resolved' => true,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Error marked as resolved',
            'error' => $error->fresh(['resolver']),
        ]);
    }

    /**
     * Get queue statistics
     * GET /api/v1/admin/system/queue
     */
    public function getQueueStats()
    {
        $stats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'recent_failed' => DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(10)
                ->get(),
            'jobs_by_queue' => DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Retry failed job
     * POST /api/v1/admin/system/queue/retry/{id}
     */
    public function retryFailedJob($id)
    {
        try {
            Artisan::call('queue:retry', ['id' => $id]);

            return response()->json([
                'message' => 'Job queued for retry',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retry job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete failed job
     * DELETE /api/v1/admin/system/queue/failed/{id}
     */
    public function deleteFailedJob($id)
    {
        DB::table('failed_jobs')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Failed job deleted',
        ]);
    }

    /**
     * Clear all failed jobs
     * POST /api/v1/admin/system/queue/flush
     */
    public function flushFailedJobs()
    {
        $count = DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->truncate();

        return response()->json([
            'message' => "Cleared {$count} failed jobs",
        ]);
    }

    /**
     * Get performance metrics
     * GET /api/v1/admin/system/performance
     */
    public function getPerformanceMetrics(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = PerformanceMetric::where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'desc')
            ->get();

        $grouped = $metrics->groupBy('metric_type');

        $summary = [];
        foreach ($grouped as $type => $items) {
            $summary[$type] = [
                'avg' => $items->avg('value'),
                'min' => $items->min('value'),
                'max' => $items->max('value'),
                'count' => $items->count(),
                'unit' => $items->first()->unit ?? 'ms',
            ];
        }

        return response()->json([
            'summary' => $summary,
            'metrics' => $metrics,
            'period_hours' => $hours,
        ]);
    }

    /**
     * Record performance metric (for testing)
     * POST /api/v1/admin/system/performance
     */
    public function recordMetric(Request $request)
    {
        $validated = $request->validate([
            'metric_type' => 'required|string',
            'endpoint' => 'nullable|string',
            'value' => 'required|numeric',
            'unit' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $metric = PerformanceMetric::create([
            'metric_type' => $validated['metric_type'],
            'endpoint' => $validated['endpoint'] ?? null,
            'value' => $validated['value'],
            'unit' => $validated['unit'],
            'metadata' => $validated['metadata'] ?? null,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Metric recorded',
            'metric' => $metric,
        ]);
    }
}
