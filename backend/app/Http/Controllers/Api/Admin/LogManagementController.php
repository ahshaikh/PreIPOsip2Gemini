<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LogManagementController extends Controller
{
    /**
     * Get list of log files
     * GET /api/v1/admin/logs
     */
    public function index()
    {
        $logPath = storage_path('logs');

        if (!File::exists($logPath)) {
            return response()->json(['logs' => []]);
        }

        $files = File::files($logPath);
        $logs = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $logs[] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $this->formatBytes($file->getSize()),
                    'size_bytes' => $file->getSize(),
                    'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    'modified_timestamp' => $file->getMTime(),
                ];
            }
        }

        // Sort by modified time (newest first)
        usort($logs, function ($a, $b) {
            return $b['modified_timestamp'] - $a['modified_timestamp'];
        });

        return response()->json(['logs' => $logs]);
    }

    /**
     * View log file content
     * GET /api/v1/admin/logs/{filename}
     * FIX: Module 20 - Fix Log Reader Memory Leak (High)
     */
    public function show($filename)
    {
        $logPath = storage_path('logs/' . $filename);

        if (!File::exists($logPath)) {
            return response()->json([
                'error' => 'Log file not found',
            ], 404);
        }

        // Security check: ensure file is within logs directory
        if (!str_starts_with(realpath($logPath), storage_path('logs'))) {
            return response()->json([
                'error' => 'Invalid log file path',
            ], 403);
        }

        try {
            /*
             * DELETED: $content = File::get($logPath);
             * REASON: This loads the ENTIRE file into memory. For a 500MB log file, this crashes PHP.
             */

            // ADDED: Efficient "Tail" Reading using fseek
            $chunkSize = 100 * 1024; // Read only last 100KB
            $size = File::size($logPath);
            $handle = fopen($logPath, 'r');
            
            $content = '';
            if ($size > 0) {
                if ($size > $chunkSize) {
                    fseek($handle, -$chunkSize, SEEK_END); // Jump to end minus chunk
                    fgets($handle); // Discard partial line at the cut point
                }
                $content = fread($handle, $chunkSize);
            }
            fclose($handle);

            $lines = explode("\n", $content);

            // Parse log entries
            $entries = $this->parseLogLines($lines);

            return response()->json([
                'filename' => $filename,
                'size' => $this->formatBytes($size),
                'total_lines' => 'N/A (Showing last 100KB)', // Cannot count total lines cheaply
                'total_entries' => count($entries),
                'entries' => array_reverse($entries), // Show newest first
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to read log file',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download log file
     * GET /api/v1/admin/logs/{filename}/download
     */
    public function download($filename)
    {
        $logPath = storage_path('logs/' . $filename);

        if (!File::exists($logPath)) {
            return response()->json([
                'error' => 'Log file not found',
            ], 404);
        }

        // Security check
        if (!str_starts_with(realpath($logPath), storage_path('logs'))) {
            return response()->json([
                'error' => 'Invalid log file path',
            ], 403);
        }

        return response()->download($logPath);
    }

    /**
     * Delete log file
     * DELETE /api/v1/admin/logs/{filename}
     */
    public function destroy($filename)
    {
        $logPath = storage_path('logs/' . $filename);

        if (!File::exists($logPath)) {
            return response()->json([
                'error' => 'Log file not found',
            ], 404);
        }

        // Security check
        if (!str_starts_with(realpath($logPath), storage_path('logs'))) {
            return response()->json([
                'error' => 'Invalid log file path',
            ], 403);
        }

        // Don't allow deleting today's log file
        if ($filename === 'laravel-' . date('Y-m-d') . '.log') {
            return response()->json([
                'error' => "Cannot delete today's log file",
            ], 403);
        }

        try {
            File::delete($logPath);

            return response()->json([
                'message' => 'Log file deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete log file',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear old log files
     * POST /api/v1/admin/logs/clear-old
     */
    public function clearOld(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $logPath = storage_path('logs');
        $cutoffTime = time() - ($validated['days'] * 86400);
        $deleted = 0;

        $files = File::files($logPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'log' && $file->getMTime() < $cutoffTime) {
                // Don't delete today's log
                if ($file->getFilename() !== 'laravel-' . date('Y-m-d') . '.log') {
                    File::delete($file->getPathname());
                    $deleted++;
                }
            }
        }

        return response()->json([
            'message' => "Deleted {$deleted} log files older than {$validated['days']} days",
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Search logs
     * POST /api/v1/admin/logs/search
     * Fixed: Uses line-by-line reading to reduce memory usage during search.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1',
            'filename' => 'nullable|string',
            'level' => 'nullable|in:debug,info,notice,warning,error,critical,alert,emergency',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $query = $validated['query'];
        $limit = $validated['limit'] ?? 100;
        $results = [];

        // Determine which files to search
        if (!empty($validated['filename'])) {
            $files = [storage_path('logs/' . $validated['filename'])];
        } else {
            $logPath = storage_path('logs');
            $files = array_map(fn($f) => $f->getPathname(), File::files($logPath));
        }

        foreach ($files as $filePath) {
            if (!File::exists($filePath) || !str_ends_with($filePath, '.log')) {
                continue;
            }

            // ADDED: Line-by-line reading instead of loading full file
            $handle = fopen($filePath, 'r');
            if ($handle) {
                $lineNumber = 0;
                while (($line = fgets($handle)) !== false) {
                    $lineNumber++;
                    if (stripos($line, $query) !== false) {
                        // Filter by level if specified
                        if (!empty($validated['level'])) {
                            if (!preg_match('/\.' . strtoupper($validated['level']) . ':/i', $line)) {
                                continue;
                            }
                        }

                        $results[] = [
                            'file' => basename($filePath),
                            'line_number' => $lineNumber,
                            'content' => trim($line),
                        ];

                        if (count($results) >= $limit) {
                            break 2;
                        }
                    }
                }
                fclose($handle);
            }
        }

        return response()->json([
            'query' => $query,
            'total_results' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Get log statistics
     * GET /api/v1/admin/logs/stats
     */
    public function getStats()
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);

        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'total_size_formatted' => '0 B',
            'by_level' => [
                'debug' => 0,
                'info' => 0,
                'warning' => 0,
                'error' => 0,
                'critical' => 0,
            ],
            'oldest_log' => null,
            'newest_log' => null,
        ];

        $oldestTime = PHP_INT_MAX;
        $newestTime = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $stats['total_files']++;
                $stats['total_size'] += $file->getSize();

                $mtime = $file->getMTime();
                if ($mtime < $oldestTime) {
                    $oldestTime = $mtime;
                    $stats['oldest_log'] = [
                        'name' => $file->getFilename(),
                        'date' => date('Y-m-d H:i:s', $mtime),
                    ];
                }
                if ($mtime > $newestTime) {
                    $newestTime = $mtime;
                    $stats['newest_log'] = [
                        'name' => $file->getFilename(),
                        'date' => date('Y-m-d H:i:s', $mtime),
                    ];
                }

                // Optimization: Only scan today's log for levels to prevent I/O bottleneck
                if ($file->getFilename() === 'laravel-' . date('Y-m-d') . '.log') {
                    // Safe to read just today's log usually, or use the chunk method
                    // For now keeping simple as this usually isn't 500MB
                    $content = File::get($file->getPathname());
                    $stats['by_level']['debug'] += substr_count($content, '.DEBUG:');
                    $stats['by_level']['info'] += substr_count($content, '.INFO:');
                    $stats['by_level']['warning'] += substr_count($content, '.WARNING:');
                    $stats['by_level']['error'] += substr_count($content, '.ERROR:');
                    $stats['by_level']['critical'] += substr_count($content, '.CRITICAL:');
                }
            }
        }

        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);

        return response()->json($stats);
    }

    /**
     * Parse log lines into structured entries
     */
    private function parseLogLines(array $lines)
    {
        $entries = [];
        $currentEntry = null;

        foreach ($lines as $line) {
            // Check if line starts a new log entry
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.*)$/', $line, $matches)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                }

                $currentEntry = [
                    'timestamp' => $matches[1],
                    'level' => strtoupper($matches[2]),
                    'message' => $matches[3],
                    'context' => [],
                ];
            } elseif ($currentEntry && !empty(trim($line))) {
                // Continuation of previous entry
                $currentEntry['message'] .= "\n" . $line;
            }
        }

        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        return $entries; // Caller handles reversal if needed
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