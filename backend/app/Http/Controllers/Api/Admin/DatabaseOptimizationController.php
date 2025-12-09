<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

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
            ];
        }

        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);
        $stats['total_rows_formatted'] = number_format($stats['total_rows']);

        return response()->json($stats);
    }

    /**
     * Optimize all tables
     * POST /api/v1/admin/database/optimize
     */
    public function optimize()
    {
        try {
            $database = env('DB_DATABASE');
            $tables = DB::select("SHOW TABLES");
            $key = "Tables_in_{$database}";

            $optimized = [];
            foreach ($tables as $table) {
                $tableName = $table->$key;
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $optimized[] = $tableName;
            }

            return response()->json([
                'message' => 'Database optimized successfully',
                'optimized_tables' => count($optimized),
                'tables' => $optimized,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to optimize database',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize specific table
     * POST /api/v1/admin/database/optimize/{table}
     */
    public function optimizeTable($table)
    {
        try {
            // Security: validate table name
            $database = env('DB_DATABASE');
            $exists = DB::selectOne("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = ? AND table_name = ?
            ", [$database, $table]);

            if (!$exists) {
                return response()->json([
                    'error' => 'Table not found',
                ], 404);
            }

            DB::statement("OPTIMIZE TABLE `{$table}`");

            return response()->json([
                'message' => "Table {$table} optimized successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to optimize table',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze tables
     * POST /api/v1/admin/database/analyze
     */
    public function analyze()
    {
        try {
            $database = env('DB_DATABASE');
            $tables = DB::select("SHOW TABLES");
            $key = "Tables_in_{$database}";

            $analyzed = [];
            foreach ($tables as $table) {
                $tableName = $table->$key;
                DB::statement("ANALYZE TABLE `{$tableName}`");
                $analyzed[] = $tableName;
            }

            return response()->json([
                'message' => 'Database analyzed successfully',
                'analyzed_tables' => count($analyzed),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to analyze database',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get slow query log
     * GET /api/v1/admin/database/slow-queries
     */
    public function getSlowQueries()
    {
        try {
            $slowLogEnabled = DB::selectOne("SHOW VARIABLES LIKE 'slow_query_log'");
            $longQueryTime = DB::selectOne("SHOW VARIABLES LIKE 'long_query_time'");

            return response()->json([
                'slow_query_log_enabled' => $slowLogEnabled->Value === 'ON',
                'long_query_time' => $longQueryTime->Value,
                'message' => 'Slow query log can be monitored in MySQL logs',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get slow query information',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get index information
     * GET /api/v1/admin/database/indexes/{table}
     */
    public function getIndexes($table)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");

            return response()->json([
                'table' => $table,
                'indexes' => $indexes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get index information',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Repair table
     * POST /api/v1/admin/database/repair/{table}
     */
    public function repairTable($table)
    {
        try {
            // Security: validate table name
            $database = env('DB_DATABASE');
            $exists = DB::selectOne("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = ? AND table_name = ?
            ", [$database, $table]);

            if (!$exists) {
                return response()->json([
                    'error' => 'Table not found',
                ], 404);
            }

            DB::statement("REPAIR TABLE `{$table}`");

            return response()->json([
                'message' => "Table {$table} repaired successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to repair table',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check table
     * POST /api/v1/admin/database/check/{table}
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
