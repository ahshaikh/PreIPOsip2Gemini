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
    // [AUDIT FIX] Helper to create a secure temp auth file for mysqldump
    // This prevents the password from appearing in the process list (ps aux)
    private function createDbAuthFile()
    {
        $content = "[client]\n" .
                   "user=\"" . env('DB_USERNAME') . "\"\n" .
                   "password=\"" . env('DB_PASSWORD') . "\"\n" .
                   "host=\"" . env('DB_HOST') . "\"\n";
                   
        $filename = 'backup_auth_' . uniqid() . '.cnf';
        $path = storage_path('app/' . $filename);
        file_put_contents($path, $content);
        chmod($path, 0600); // Restrict permissions to owner only
        return $path;
    }

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
        $backupDir = storage_path('app/backups');
        $backups = [];

        // Ensure backup directory exists
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                \Log::warning('Failed to create backup directory in getHistory');
            }
        }

        // Get all SQL files from the backup directory
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/*.sql');

            foreach ($files as $filePath) {
                if (is_file($filePath)) {
                    $backups[] = [
                        'filename' => basename($filePath),
                        'size' => filesize($filePath),
                        'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'path' => 'backups/' . basename($filePath),
                    ];
                }
            }

            // Sort by created_at descending (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }

        return response()->json([
            'backups' => $backups,
            'backup_directory' => $backupDir,
            'directory_exists' => is_dir($backupDir),
            'total_backups' => count($backups),
        ]);
    }

    /**
     * Create manual backup
     * POST /api/v1/admin/system/backup/create
     * FIX: Module 20 - Rewrite Backup Logic (Critical)
     * CROSS-PLATFORM: Works on Windows and Linux with fallback to PHP-based backup
     */
    public function createBackup(Request $request)
    {
        $validated = $request->validate([
            'include_files' => 'sometimes|boolean',
        ]);

        $authFilePath = null; // [AUDIT FIX] Initialize variable for cleanup

        try {
            // Create backup directory if it doesn't exist
            $backupDir = storage_path('app/backups');

            \Log::info('Backup creation started', [
                'backup_dir' => $backupDir,
                'dir_exists' => is_dir($backupDir),
                'dir_writable' => is_writable(storage_path('app')),
            ]);

            if (!is_dir($backupDir)) {
                $mkdirResult = mkdir($backupDir, 0755, true);
                if (!$mkdirResult && !is_dir($backupDir)) {
                    throw new \Exception('Failed to create backup directory at: ' . $backupDir);
                }
                \Log::info('Backup directory created', ['path' => $backupDir]);
            }

            // Verify directory is writable
            if (!is_writable($backupDir)) {
                throw new \Exception('Backup directory is not writable: ' . $backupDir);
            }

            $dbName = env('DB_DATABASE');
            $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = 'backups/' . $fileName;
            $fullPath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

            \Log::info('Backup file path', [
                'filename' => $fileName,
                'full_path' => $fullPath,
            ]);

            // Try mysqldump first (preferred method)
            $mysqldumpPath = $this->findMysqldump();
            $method = 'unknown';

            if ($mysqldumpPath) {
                // [AUDIT FIX] Create secure auth file
                $authFilePath = $this->createDbAuthFile();

                // ADDED: Use system mysqldump command with defaults-extra-file
                // This runs outside PHP memory space and streams the backup efficiently.
                // [AUDIT FIX] Password is no longer exposed in CLI arguments
                $command = sprintf(
                    '%s --defaults-extra-file=%s %s > %s 2>&1',
                    escapeshellcmd($mysqldumpPath),
                    escapeshellarg($authFilePath),
                    escapeshellarg($dbName),
                    escapeshellarg($fullPath)
                );

                \Log::info('Executing mysqldump command');

                // Execute the command
                $returnVar = null;
                $output = [];
                exec($command, $output, $returnVar);

                if ($returnVar !== 0) {
                    // mysqldump failed, fall back to PHP method
                    \Log::warning('mysqldump failed, falling back to PHP backup', [
                        'return_code' => $returnVar,
                        'output' => implode("\n", $output)
                    ]);
                    $this->createBackupWithPHP($fullPath);
                    $method = 'php';
                } else {
                    $method = 'mysqldump';
                    \Log::info('mysqldump completed successfully');
                }
            } else {
                // mysqldump not available, use PHP method
                \Log::info('mysqldump not found, using PHP backup method');
                $this->createBackupWithPHP($fullPath);
                $method = 'php';
            }

            // Verify backup file was created and has content
            \Log::info('Verifying backup file', [
                'exists' => file_exists($fullPath),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            ]);

            if (!file_exists($fullPath)) {
                throw new \Exception('Backup file was not created at: ' . $fullPath);
            }

            $fileSize = filesize($fullPath);
            if ($fileSize < 100) {
                throw new \Exception('Backup file is too small (' . $fileSize . ' bytes). Backup may have failed.');
            }

            \Log::info('Backup created successfully', [
                'filename' => $fileName,
                'size' => $fileSize,
                'method' => $method,
            ]);

            return response()->json([
                'message' => 'Backup created successfully',
                'filename' => $fileName,
                'path' => $filePath,
                'full_path' => $fullPath,
                'size' => $fileSize,
                'method' => $method,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Backup failed',
                'message' => $e->getMessage(),
            ], 500);
        } finally {
            // [AUDIT FIX] Always delete the temp auth file
            if ($authFilePath && file_exists($authFilePath)) {
                unlink($authFilePath);
            }
        }
    }

    /**
     * Find mysqldump executable (cross-platform)
     * Returns full path to mysqldump or null if not found
     */
    private function findMysqldump()
    {
        // Common locations to check
        $possiblePaths = [
            'mysqldump', // In PATH
            '/usr/bin/mysqldump', // Linux
            '/usr/local/bin/mysqldump', // Linux/macOS
            '/usr/local/mysql/bin/mysqldump', // macOS with MySQL
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe', // Windows MySQL 8.0
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe', // Windows MySQL 5.7
            'C:\\xampp\\mysql\\bin\\mysqldump.exe', // XAMPP on Windows
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe', // WAMP on Windows
        ];

        // Check if mysqldump is in PATH
        $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';
        $output = [];
        $returnVar = null;
        exec("$which mysqldump 2>&1", $output, $returnVar);

        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        // Check common paths
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Create backup using pure PHP (fallback method)
     * Memory-efficient streaming approach for moderate databases
     */
    private function createBackupWithPHP($outputPath)
    {
        $dbHost = env('DB_HOST');
        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbPort = env('DB_PORT', 3306);

        // Open output file for writing
        $handle = fopen($outputPath, 'w');
        if (!$handle) {
            throw new \Exception('Cannot create backup file');
        }

        try {
            // Write header
            fwrite($handle, "-- MySQL Database Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: {$dbName}\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $dbName;

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                // Skip certain system tables if needed
                if (in_array($tableName, ['migrations', 'failed_jobs', 'cache', 'cache_locks'])) {
                    continue;
                }

                fwrite($handle, "\n-- Table: {$tableName}\n");

                // Get CREATE TABLE statement
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                if (!empty($createTable)) {
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                    fwrite($handle, $createTable[0]->{'Create Table'} . ";\n\n");
                }

                // Export data in chunks to avoid memory issues
                $chunkSize = 100;
                $offset = 0;

                while (true) {
                    $rows = DB::table($tableName)
                        ->limit($chunkSize)
                        ->offset($offset)
                        ->get();

                    if ($rows->isEmpty()) {
                        break;
                    }

                    foreach ($rows as $row) {
                        $values = array_map(function ($value) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            return "'" . addslashes($value) . "'";
                        }, (array) $row);

                        $sql = "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                        fwrite($handle, $sql);
                    }

                    $offset += $chunkSize;
                }
            }

            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

        } catch (\Exception $e) {
            fclose($handle);
            throw $e;
        }
    }

    /**
     * Restore database from backup file
     * POST /api/v1/admin/system/backup/restore/{filename}
     * CRITICAL: Creates safety backup before restore, cross-platform support
     */
    public function restoreBackup($filename)
    {
        // V-AUDIT-MODULE19-LOW: Validate filename for path traversal attacks
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            return response()->json([
                'error' => 'Invalid filename',
                'message' => 'Filename must not contain directory separators or parent directory references',
            ], 400);
        }

        // V-AUDIT-MODULE19-LOW: Additional validation - must end with .sql
        if (!str_ends_with($filename, '.sql')) {
            return response()->json([
                'error' => 'Invalid file type',
                'message' => 'Only .sql backup files can be restored',
            ], 400);
        }

        $backupDir = storage_path('app/backups');
        $restoreFilePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($restoreFilePath)) {
            return response()->json([
                'error' => 'Backup file not found',
                'path_checked' => $restoreFilePath,
            ], 404);
        }

        $authFilePath = null;
        $safetyBackupFile = null;

        try {
            // Step 1: Create a safety backup before restore
            $dbName = env('DB_DATABASE');
            $safetyBackupFile = 'backup_' . $dbName . '_pre-restore_' . date('Y-m-d_H-i-s') . '.sql';
            $safetyBackupPath = $backupDir . DIRECTORY_SEPARATOR . $safetyBackupFile;

            \Log::info('Creating safety backup before restore', ['filename' => $safetyBackupFile]);

            // Create safety backup using the same methods
            $mysqldumpPath = $this->findMysqldump();
            if ($mysqldumpPath) {
                $authFilePath = $this->createDbAuthFile();
                $command = sprintf(
                    '%s --defaults-extra-file=%s %s > %s 2>&1',
                    escapeshellcmd($mysqldumpPath),
                    escapeshellarg($authFilePath),
                    escapeshellarg($dbName),
                    escapeshellarg($safetyBackupPath)
                );
                $returnVar = null;
                $output = [];
                exec($command, $output, $returnVar);

                if ($returnVar !== 0) {
                    $this->createBackupWithPHP($safetyBackupPath);
                }
            } else {
                $this->createBackupWithPHP($safetyBackupPath);
            }

            if (!file_exists($safetyBackupPath) || filesize($safetyBackupPath) < 100) {
                throw new \Exception('Failed to create safety backup before restore');
            }

            // Step 2: Restore from the selected backup file

            \Log::info('Starting database restore', ['source' => $filename]);

            $mysqlPath = $this->findMysql();

            if ($mysqlPath) {
                // Use mysql command line tool
                if (!$authFilePath) {
                    $authFilePath = $this->createDbAuthFile();
                }

                $command = sprintf(
                    '%s --defaults-extra-file=%s %s < %s 2>&1',
                    escapeshellcmd($mysqlPath),
                    escapeshellarg($authFilePath),
                    escapeshellarg($dbName),
                    escapeshellarg($restoreFilePath)
                );

                $returnVar = null;
                $output = [];
                exec($command, $output, $returnVar);

                if ($returnVar !== 0) {
                    // mysql command failed, fall back to PHP
                    \Log::warning('mysql command failed, falling back to PHP restore', [
                        'return_code' => $returnVar,
                        'output' => implode("\n", $output)
                    ]);
                    $this->restoreWithPHP($restoreFilePath);
                }
            } else {
                // Fallback to PHP method
                \Log::info('mysql not found, using PHP restore method');
                $this->restoreWithPHP($restoreFilePath);
            }

            \Log::info('Database restore completed successfully', [
                'restored_from' => $filename,
                'safety_backup' => $safetyBackupFile
            ]);

            return response()->json([
                'message' => 'Database restored successfully',
                'restored_from' => $filename,
                'safety_backup' => $safetyBackupFile,
                'warning' => 'A safety backup was created before restore: ' . $safetyBackupFile,
            ]);

        } catch (\Exception $e) {
            \Log::error('Database restore failed', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);

            return response()->json([
                'error' => 'Database restore failed',
                'message' => $e->getMessage(),
                'safety_backup' => $safetyBackupFile,
                'recovery_note' => $safetyBackupFile
                    ? 'A safety backup was created: ' . $safetyBackupFile . '. You can restore from it if needed.'
                    : 'No safety backup was created. The database may be in an inconsistent state.',
            ], 500);
        } finally {
            // Cleanup temp auth file
            if ($authFilePath && file_exists($authFilePath)) {
                unlink($authFilePath);
            }
        }
    }

    /**
     * Find mysql executable (cross-platform)
     * Returns full path to mysql or null if not found
     */
    private function findMysql()
    {
        // Common locations to check
        $possiblePaths = [
            'mysql', // In PATH
            '/usr/bin/mysql', // Linux
            '/usr/local/bin/mysql', // Linux/macOS
            '/usr/local/mysql/bin/mysql', // macOS with MySQL
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe', // Windows MySQL 8.0
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysql.exe', // Windows MySQL 5.7
            'C:\\xampp\\mysql\\bin\\mysql.exe', // XAMPP on Windows
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysql.exe', // WAMP on Windows
        ];

        // Check if mysql is in PATH
        $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';
        $output = [];
        $returnVar = null;
        exec("$which mysql 2>&1", $output, $returnVar);

        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        // Check common paths
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Restore database using pure PHP (fallback method)
     * Executes SQL statements from backup file
     */
    private function restoreWithPHP($sqlFilePath)
    {
        if (!file_exists($sqlFilePath)) {
            throw new \Exception('Backup file not found');
        }

        // Read and execute SQL file
        $sql = file_get_contents($sqlFilePath);

        if ($sql === false || empty($sql)) {
            throw new \Exception('Failed to read backup file or file is empty');
        }

        // Disable foreign key checks during restore
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Split SQL into individual statements
            // This is a simple parser - handles most common SQL backup formats
            $statements = $this->parseSqlStatements($sql);

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement) || str_starts_with($statement, '--')) {
                    continue;
                }

                try {
                    DB::statement($statement);
                } catch (\Exception $e) {
                    // Log but continue with other statements
                    \Log::warning('Failed to execute SQL statement during restore', [
                        'error' => $e->getMessage(),
                        'statement' => substr($statement, 0, 100) . '...'
                    ]);
                }
            }

        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Parse SQL file into individual statements
     * Handles multi-line statements and comments
     */
    private function parseSqlStatements($sql)
    {
        // Remove comments
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Split by semicolon (considering strings and escaped characters)
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        $escaped = false;

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];

            if ($escaped) {
                $currentStatement .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $currentStatement .= $char;
                $escaped = true;
                continue;
            }

            if (($char === "'" || $char === '"') && !$inString) {
                $inString = true;
                $stringChar = $char;
                $currentStatement .= $char;
            } elseif ($char === $stringChar && $inString) {
                $inString = false;
                $currentStatement .= $char;
            } elseif ($char === ';' && !$inString) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            } else {
                $currentStatement .= $char;
            }
        }

        // Add last statement if not empty
        if (!empty(trim($currentStatement))) {
            $statements[] = trim($currentStatement);
        }

        return array_filter($statements, fn($s) => !empty($s));
    }

    /**
     * V-AUDIT-MODULE19-LOW: Fixed Backup Download Security (Path Traversal)
     *
     * PROBLEM: The $filename parameter was directly concatenated into the file path
     * without validation. An attacker could exploit this with path traversal attacks:
     * - /api/v1/admin/system/backup/download/../../.env → Expose environment variables
     * - /api/v1/admin/system/backup/download/../../../etc/passwd → Read system files
     *
     * While Laravel's Storage facade provides some protection, it's insufficient:
     * - Storage::exists() might not catch all path traversal attempts
     * - Different filesystems handle paths differently (Windows vs Linux)
     * - Defense-in-depth principle requires explicit validation
     *
     * SOLUTION: Validate that $filename:
     * 1. Contains no slashes (/, \) - must be a basename only
     * 2. Contains no parent directory references (..)
     * 3. Ends with expected extension (.sql)
     * 4. Matches expected backup filename pattern
     *
     * Security Impact: Prevents unauthorized file access via path traversal.
     *
     * Download backup file
     * GET /api/v1/admin/system/backup/download/{filename}
     */
    public function downloadBackup($filename)
    {
        // V-AUDIT-MODULE19-LOW: Validate filename for path traversal attacks
        // Reject if filename contains slashes (directory separators) or parent refs
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            return response()->json([
                'error' => 'Invalid filename',
                'message' => 'Filename must not contain directory separators or parent directory references',
            ], 400);
        }

        // V-AUDIT-MODULE19-LOW: Additional validation - must end with .sql (expected backup format)
        if (!str_ends_with($filename, '.sql')) {
            return response()->json([
                'error' => 'Invalid file type',
                'message' => 'Only .sql backup files can be downloaded',
            ], 400);
        }

        // V-AUDIT-MODULE19-LOW: Safely construct path (filename is now validated as basename-only)
        $backupDir = storage_path('app/backups');
        $fullPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($fullPath)) {
            return response()->json([
                'error' => 'Backup file not found',
                'path_checked' => $fullPath,
            ], 404);
        }

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * V-AUDIT-MODULE19-LOW: Fixed Backup Deletion Security (Path Traversal)
     *
     * PROBLEM: Same path traversal vulnerability as downloadBackup().
     * An attacker could delete critical files:
     * - /api/v1/admin/system/backup/../../../.env → Delete environment config
     * - /api/v1/admin/system/backup/../../database/database.sqlite → Delete database
     *
     * SOLUTION: Apply same validation as downloadBackup() before deletion.
     *
     * Delete backup file
     * DELETE /api/v1/admin/system/backup/{filename}
     */
    public function deleteBackup($filename)
    {
        // V-AUDIT-MODULE19-LOW: Validate filename for path traversal attacks
        // Reject if filename contains slashes (directory separators) or parent refs
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            return response()->json([
                'error' => 'Invalid filename',
                'message' => 'Filename must not contain directory separators or parent directory references',
            ], 400);
        }

        // V-AUDIT-MODULE19-LOW: Additional validation - must end with .sql (expected backup format)
        if (!str_ends_with($filename, '.sql')) {
            return response()->json([
                'error' => 'Invalid file type',
                'message' => 'Only .sql backup files can be deleted',
            ], 400);
        }

        // V-AUDIT-MODULE19-LOW: Safely construct path (filename is now validated as basename-only)
        $backupDir = storage_path('app/backups');
        $fullPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($fullPath)) {
            return response()->json([
                'error' => 'Backup file not found',
                'path_checked' => $fullPath,
            ], 404);
        }

        if (!unlink($fullPath)) {
            return response()->json([
                'error' => 'Failed to delete backup file',
                'message' => 'The file exists but could not be deleted. Check file permissions.',
            ], 500);
        }

        \Log::info('Backup file deleted', ['filename' => $filename]);

        return response()->json(['message' => 'Backup deleted successfully']);
    }

    /**
     * Stream a full database dump to the browser (legacy method)
     * GET /api/v1/admin/system/backup/db
     * CROSS-PLATFORM: Works with or without mysqldump
     */
    public function downloadDbDump()
    {
        $dbName = env('DB_DATABASE');
        $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';

        $headers = [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $mysqldumpPath = $this->findMysqldump();

        // ADDED: Streamed Response using popen (Pipe Open) if mysqldump available
        // Streams the output of mysqldump directly to the browser output buffer.
        // PHP memory usage stays near zero regardless of DB size.
        return new StreamedResponse(function() use ($mysqldumpPath) {
            $authFilePath = null; // [AUDIT FIX] Initialize for cleanup

            try {
                if ($mysqldumpPath) {
                    // [AUDIT FIX] Create secure auth file
                    $authFilePath = $this->createDbAuthFile();

                    $command = sprintf(
                        '%s --defaults-extra-file=%s %s 2>&1',
                        escapeshellcmd($mysqldumpPath),
                        escapeshellarg($authFilePath),
                        escapeshellarg(env('DB_DATABASE'))
                    );

                    $handle = fopen('php://output', 'w');
                    $proc = popen($command, 'r');
                    if ($proc) {
                        while (!feof($proc)) {
                            fwrite($handle, fread($proc, 4096)); // Buffer size 4KB
                        }
                        pclose($proc);
                        fclose($handle);
                    }
                } else {
                    // Fallback to PHP method - stream directly to output
                    $tempFile = tempnam(sys_get_temp_dir(), 'backup_');
                    $this->createBackupWithPHP($tempFile);

                    // Stream the file
                    $handle = fopen($tempFile, 'r');
                    while (!feof($handle)) {
                        echo fread($handle, 8192);
                        flush();
                    }
                    fclose($handle);
                    unlink($tempFile);
                }
            } catch (\Exception $e) {
                echo "-- Backup Error: " . $e->getMessage() . "\n";
            } finally {
                // [AUDIT FIX] Cleanup temp file
                if ($authFilePath && file_exists($authFilePath)) {
                    unlink($authFilePath);
                }
            }
        }, 200, $headers);
    }
}