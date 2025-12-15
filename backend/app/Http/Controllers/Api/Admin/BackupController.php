<?php
// V-FINAL-1730-226 | V-SYSTEM-CONFIG-001 (Enhanced) | V-FIX-MODULE-20-BACKUP (Gemini)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Get backup configuration
     * GET /api/v1/admin/system/backup/config
     */
    public function getConfig()
    {
        $settings = Setting::where('group', 'backup')->get()->keyBy('key');
        
        return response()->json([
            'backup_enabled' => $settings->get('backup_enabled')?->value === 'true',
            'backup_schedule' => $settings->get('backup_schedule')?->value ?? 'daily',
            'backup_time' => $settings->get('backup_time')?->value ?? '02:00',
            'backup_retention_days' => (int) ($settings->get('backup_retention_days')?->value ?? 30),
            'backup_storage' => $settings->get('backup_storage')?->value ?? 'local',
            'backup_notification_email' => $settings->get('backup_notification_email')?->value ?? '',
            'backup_include_uploads' => $settings->get('backup_include_uploads')?->value === 'true',
            'backup_include_files' => $settings->get('backup_include_files')?->value === 'true',
            'backup_email_report' => $settings->get('backup_email_report')?->value === 'true',
        ]);
    }

    /**
     * Update backup configuration
     * PUT /api/v1/admin/system/backup/config
     */
    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'backup_enabled' => 'sometimes|boolean',
            'backup_schedule' => 'sometimes|in:daily,weekly,monthly',
            'backup_time' => 'sometimes|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'backup_retention_days' => 'sometimes|integer|min:1|max:365',
            'backup_storage' => 'sometimes|in:local,s3,ftp',
            'backup_notification_email' => 'sometimes|email|nullable',
            'backup_include_uploads' => 'sometimes|boolean',
            'backup_include_files' => 'sometimes|boolean',
            'backup_email_report' => 'sometimes|boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
                    'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string'),
                    'group' => 'backup'
                ]
            );
        }

        return response()->json(['message' => 'Backup configuration updated']);
    }

    /**
     * Get backup history
     * GET /api/v1/admin/system/backup/history
     */
    public function getHistory()
    {
        // Check storage disk for backup files
        $disk = Storage::disk('local');
        $backups = [];
        
        if ($disk->exists('backups')) {
            $files = $disk->files('backups');
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => $disk->size($file),
                    'created_at' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                    'path' => $file,
                ];
            }
        }

        // Sort by created_at descending
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return response()->json(['backups' => $backups]);
    }

    /**
     * Create manual backup
     * POST /api/v1/admin/system/backup/create
     * FIX: Module 20 - Rewrite Backup Logic (Critical)
     */
    public function createBackup(Request $request)
    {
        $validated = $request->validate([
            'include_files' => 'sometimes|boolean',
        ]);

        try {
            // Create backup directory if it doesn't exist
            Storage::disk('local')->makeDirectory('backups');
            
            $dbName = env('DB_DATABASE');
            $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = 'backups/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            /*
             * DELETED: The PHP looping logic (Critical Failure)
             * $tables = DB::select('SHOW TABLES');
             * foreach ($tables as $table) { 
             * $rows = DB::table($table->...)->get(); // <--- Loads entire table into RAM
             * }
             * REASON: This causes Fatal Error: Allowed memory size exhausted on large tables (e.g. activity_logs).
             */

            // ADDED: Use system mysqldump command
            // This runs outside PHP memory space and streams the backup efficiently.
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg(env('DB_USERNAME')),
                escapeshellarg(env('DB_PASSWORD')),
                escapeshellarg(env('DB_HOST')),
                escapeshellarg($dbName),
                escapeshellarg($fullPath)
            );
            
            // Execute the command
            $returnVar = null;
            $output = [];
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                 throw new \Exception("Backup failed with error code $returnVar. Check mysqldump availability.");
            }

            return response()->json([
                'message' => 'Backup created successfully',
                'filename' => $fileName,
                'path' => $filePath,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Backup failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download backup file
     * GET /api/v1/admin/system/backup/download/{filename}
     */
    public function downloadBackup($filename)
    {
        $filePath = 'backups/' . $filename;
        
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['error' => 'Backup file not found'], 404);
        }

        return Storage::disk('local')->download($filePath);
    }

    /**
     * Delete backup file
     * DELETE /api/v1/admin/system/backup/{filename}
     */
    public function deleteBackup($filename)
    {
        $filePath = 'backups/' . $filename;
        
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['error' => 'Backup file not found'], 404);
        }

        Storage::disk('local')->delete($filePath);

        return response()->json(['message' => 'Backup deleted successfully']);
    }

    /**
     * Stream a full database dump to the browser (legacy method)
     * GET /api/v1/admin/system/backup/db
     */
    public function downloadDbDump()
    {
        /*
         * DELETED: Legacy Memory-Hog Implementation
         * Logic that built the SQL string in a PHP variable.
         * REASON: Will crash server on production data volume.
         */
        
        $dbName = env('DB_DATABASE');
        $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        $headers = [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        // ADDED: Streamed Response using popen (Pipe Open)
        // Streams the output of mysqldump directly to the browser output buffer.
        // PHP memory usage stays near zero regardless of DB size.
        return new StreamedResponse(function() {
            $handle = fopen('php://output', 'w');
            
             $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s',
                escapeshellarg(env('DB_USERNAME')),
                escapeshellarg(env('DB_PASSWORD')),
                escapeshellarg(env('DB_HOST')),
                escapeshellarg(env('DB_DATABASE'))
            );
            
            $proc = popen($command, 'r');
            while (!feof($proc)) {
                fwrite($handle, fread($proc, 4096)); // Buffer size 4KB
            }
            pclose($proc);
            fclose($handle);
        }, 200, $headers);
    }
}