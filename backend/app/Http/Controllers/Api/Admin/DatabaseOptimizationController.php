<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
// ADDED: Job class for Async processing
use App\Jobs\DatabaseOptimizationJob; 

class DatabaseOptimizationController extends Controller
{
    /**
     * Get database statistics
     * GET /api/v1/admin/database/stats
     */
    public function getStats()
    {
        $database = env('DB_DATABASE');

        $tables = DB::select("
            SELECT
                table_name,
                table_rows,
                data_length,
                index_length,
                data_free
            FROM information_schema.tables
            WHERE table_schema = ?
            ORDER BY data_length + index_length DESC
        ", [$database]);

        $stats = [
            'database' => $database,
            'total_tables' => count($tables),
            'total_size' => 0,
            'total_rows' => 0,
            'tables' => [],
        ];

        foreach ($tables as $table) {
            $size = $table->data_length + $table->index_length;
            $stats['total_size'] += $size;
            $stats['total_rows'] += $table->table_rows;

            $stats['tables'][] = [
                'name' => $table->table_name,
                'rows' => number_format($table->table_rows),
                'size' => $this->formatBytes($size),
                'data_size' => $this->formatBytes($table->data_length),
                'index_size' => $this->formatBytes($table->index_length),
                'overhead' => $this->formatBytes($table->data_free),
                'overhead_bytes' => $table->data_free,
            ];
        }

        $stats['total_size'] = $this->formatBytes($stats['total_size']);

        return response()->json($stats);
    }

    /**
     * Optimize table
     * POST /api/v1/admin/database/optimize/{table}
     */
    public function optimize($table)
    {
        // FIX: Module 20 - Database Optimization Locking
        // Dispatch job instead of running synchronously
        
        try {
            // Check if table exists first
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return response()->json(['error' => 'Table not found'], 404);
            }

            // ADDED: Dispatch Job
            DatabaseOptimizationJob::dispatch($table, 'optimize');

            return response()->json([
                'message' => "Optimization job queued for table {$table}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to queue optimization',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Repair table
     * POST /api/v1/admin/database/repair/{table}
     */
    public function repair($table)
    {
        // FIX: Asynchronous dispatch
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return response()->json(['error' => 'Table not found'], 404);
            }

            // ADDED: Dispatch Job
            DatabaseOptimizationJob::dispatch($table, 'repair');

            return response()->json([
                'message' => "Repair job queued for table {$table}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to queue repair',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check table
     * POST /api/v1/admin/database/check/{table}
     * Note: CHECK TABLE is usually fast and read-only (mostly), keeping synchronous for immediate feedback
     * unless explicitly requested to be async. Given audit context "Maintenance (Optimize/Repair)", 
     * leaving CHECK as synchronous is acceptable UX for diagnostics.
     */
    public function checkTable($table)
    {
        try {
            $result = DB::select("CHECK TABLE `{$table}`");

            return response()->json([
                'table' => $table,
                'status' => $result[0]->Msg_text ?? 'Unknown',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check table',
                'message' => $e->getMessage(),
            ], 500);
        }
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