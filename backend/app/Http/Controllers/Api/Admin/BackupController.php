<?php
// V-FINAL-1730-226 | V-SYSTEM-CONFIG-001 (Enhanced)

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

            // Create SQL dump
            $handle = fopen(storage_path('app/' . $filePath), 'w');
            
            $tables = DB::select('SHOW TABLES');
            $key = "Tables_in_" . $dbName;

            foreach ($tables as $table) {
                $tableName = $table->$key;
                
                fwrite($handle, "\nDROP TABLE IF EXISTS `$tableName`;\n");
                
                $createTable = DB::select("SHOW CREATE TABLE `$tableName`")[0]->{'Create Table'};
                fwrite($handle, $createTable . ";\n\n");

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($value) {
                        return is_null($value) ? "NULL" : "'" . addslashes($value) . "'";
                    }, (array) $row);
                    
                    $sql = "INSERT INTO `$tableName` VALUES (" . implode(", ", $values) . ");\n";
                    fwrite($handle, $sql);
                }
            }
            
            fclose($handle);

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
        $dbName = env('DB_DATABASE');
        $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';

        $headers = [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            
            $tables = DB::select('SHOW TABLES');
            $key = "Tables_in_" . env('DB_DATABASE');

            foreach ($tables as $table) {
                $tableName = $table->$key;
                
                fwrite($handle, "\nDROP TABLE IF EXISTS `$tableName`;\n");
                
                $createTable = DB::select("SHOW CREATE TABLE `$tableName`")[0]->{'Create Table'};
                fwrite($handle, $createTable . ";\n\n");

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($value) {
                        return is_null($value) ? "NULL" : "'" . addslashes($value) . "'";
                    }, (array) $row);
                    
                    $sql = "INSERT INTO `$tableName` VALUES (" . implode(", ", $values) . ");\n";
                    fwrite($handle, $sql);
                }
            }
            
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
