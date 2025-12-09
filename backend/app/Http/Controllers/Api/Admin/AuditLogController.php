<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * Get audit logs (admin actions)
     * GET /api/v1/admin/audit-logs
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('admin:id,username,email')
            ->orderBy('created_at', 'desc');

        // Filter by admin
        if ($request->has('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filter by module
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);

        // Get statistics
        $stats = [
            'total_actions' => AuditLog::count(),
            'today_actions' => AuditLog::whereDate('created_at', today())->count(),
            'unique_admins' => AuditLog::distinct('admin_id')->count(),
            'by_module' => AuditLog::select('module', DB::raw('count(*) as count'))
                ->groupBy('module')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'module'),
            'by_action' => AuditLog::select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'action'),
        ];

        return response()->json([
            'logs' => $logs,
            'stats' => $stats,
        ]);
    }

    /**
     * Get specific audit log with full details
     * GET /api/v1/admin/audit-logs/{log}
     */
    public function show(AuditLog $log)
    {
        $log->load('admin:id,username,email');

        // Get the target model if it exists
        $target = null;
        if ($log->target_type && $log->target_id) {
            try {
                $target = $log->target;
            } catch (\Exception $e) {
                // Target may have been deleted
            }
        }

        return response()->json([
            'log' => $log,
            'target' => $target,
            'changes' => $this->formatChanges($log),
        ]);
    }

    /**
     * Get change history for a specific entity
     * GET /api/v1/admin/audit-logs/history/{type}/{id}
     */
    public function getHistory($type, $id)
    {
        $logs = AuditLog::where('target_type', $type)
            ->where('target_id', $id)
            ->with('admin:id,username,email')
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'admin' => $log->admin->username ?? 'Unknown',
                'admin_id' => $log->admin_id,
                'action' => $log->action,
                'description' => $log->description,
                'changes' => $this->formatChanges($log),
                'timestamp' => $log->created_at,
            ];
        });

        return response()->json([
            'target_type' => $type,
            'target_id' => $id,
            'history' => $formatted,
            'total_changes' => $logs->count(),
        ]);
    }

    /**
     * Get activity timeline (combines audit logs and activity logs)
     * GET /api/v1/admin/activity-timeline
     */
    public function getTimeline(Request $request)
    {
        $hours = $request->input('hours', 24);

        // Get audit logs (admin actions)
        $auditLogs = AuditLog::where('created_at', '>=', now()->subHours($hours))
            ->with('admin:id,username,email')
            ->get()
            ->map(function ($log) {
                return [
                    'type' => 'admin_action',
                    'timestamp' => $log->created_at,
                    'actor' => $log->admin->username ?? 'Unknown',
                    'actor_type' => 'admin',
                    'action' => $log->action,
                    'module' => $log->module,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                ];
            });

        // Get activity logs (user actions)
        $activityLogs = ActivityLog::where('created_at', '>=', now()->subHours($hours))
            ->with('user:id,username,email')
            ->get()
            ->map(function ($log) {
                return [
                    'type' => 'user_action',
                    'timestamp' => $log->created_at,
                    'actor' => $log->user->username ?? 'Unknown',
                    'actor_type' => 'user',
                    'action' => $log->action,
                    'module' => null,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                ];
            });

        // Merge and sort by timestamp
        $timeline = $auditLogs->concat($activityLogs)
            ->sortByDesc('timestamp')
            ->values();

        return response()->json([
            'timeline' => $timeline,
            'period_hours' => $hours,
            'total_events' => $timeline->count(),
        ]);
    }

    /**
     * Get admin action statistics
     * GET /api/v1/admin/audit-logs/stats
     */
    public function getStats(Request $request)
    {
        $days = $request->input('days', 30);

        $stats = [
            'total_actions' => AuditLog::where('created_at', '>=', now()->subDays($days))->count(),

            'by_admin' => AuditLog::where('created_at', '>=', now()->subDays($days))
                ->select('admin_id', DB::raw('count(*) as count'))
                ->with('admin:id,username')
                ->groupBy('admin_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'admin' => $item->admin->username ?? 'Unknown',
                        'count' => $item->count,
                    ];
                }),

            'by_module' => AuditLog::where('created_at', '>=', now()->subDays($days))
                ->select('module', DB::raw('count(*) as count'))
                ->groupBy('module')
                ->orderBy('count', 'desc')
                ->get(),

            'by_action' => AuditLog::where('created_at', '>=', now()->subDays($days))
                ->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(15)
                ->get(),

            'daily_trend' => AuditLog::where('created_at', '>=', now()->subDays($days))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(),

            'hourly_distribution' => AuditLog::where('created_at', '>=', now()->subDays($days))
                ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                ->groupBy('hour')
                ->orderBy('hour', 'asc')
                ->get(),
        ];

        return response()->json([
            'stats' => $stats,
            'period_days' => $days,
        ]);
    }

    /**
     * Compare before/after values for a specific change
     * GET /api/v1/admin/audit-logs/{log}/compare
     */
    public function compareChanges(AuditLog $log)
    {
        if (!$log->old_values && !$log->new_values) {
            return response()->json([
                'message' => 'No before/after data available for this log',
            ], 404);
        }

        $changes = $this->getDetailedChanges($log->old_values ?? [], $log->new_values ?? []);

        return response()->json([
            'log_id' => $log->id,
            'action' => $log->action,
            'module' => $log->module,
            'admin' => $log->admin->username ?? 'Unknown',
            'timestamp' => $log->created_at,
            'changes' => $changes,
        ]);
    }

    /**
     * Export audit logs
     * GET /api/v1/admin/audit-logs/export
     */
    public function export(Request $request)
    {
        $query = AuditLog::with('admin:id,username,email')
            ->orderBy('created_at', 'desc');

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        $logs = $query->limit(10000)->get();

        $csv = "ID,Timestamp,Admin,Action,Module,Target,Description,IP Address\n";
        foreach ($logs as $log) {
            $csv .= implode(',', [
                $log->id,
                $log->created_at->toDateTimeString(),
                $log->admin->username ?? 'Unknown',
                $log->action,
                $log->module,
                $log->target_type ? "{$log->target_type}:{$log->target_id}" : 'N/A',
                '"' . str_replace('"', '""', $log->description ?? '') . '"',
                $log->ip_address ?? 'N/A',
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="audit_logs_' . now()->format('Y-m-d') . '.csv"');
    }

    /**
     * Format changes for display
     */
    private function formatChanges(AuditLog $log)
    {
        if (!$log->old_values && !$log->new_values) {
            return null;
        }

        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Get detailed changes with data types
     */
    private function getDetailedChanges(array $old, array $new)
    {
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'old_type' => gettype($oldValue),
                    'new_type' => gettype($newValue),
                    'changed' => true,
                ];
            } else {
                $changes[] = [
                    'field' => $key,
                    'value' => $oldValue,
                    'type' => gettype($oldValue),
                    'changed' => false,
                ];
            }
        }

        return $changes;
    }
}
