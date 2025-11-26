<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--compress : Compress the backup file}
                            {--storage=local : Storage disk to use (local, s3, etc.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        try {
            // Generate backup filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $tempPath = storage_path("app/backups/{$filename}");

            // Ensure backups directory exists
            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }

            // Get database connection details
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            // Build mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($tempPath)
            );

            // Execute backup command
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Check if file was created
            if (!file_exists($tempPath)) {
                throw new \Exception('Backup file was not created');
            }

            $fileSize = filesize($tempPath);
            $this->info("Backup created: {$filename} (" . $this->formatBytes($fileSize) . ")");

            // Compress if requested
            if ($this->option('compress')) {
                $this->info('Compressing backup...');
                $compressedPath = $tempPath . '.gz';

                $gzHandle = gzopen($compressedPath, 'wb9');
                $fileHandle = fopen($tempPath, 'rb');

                while (!feof($fileHandle)) {
                    gzwrite($gzHandle, fread($fileHandle, 1024 * 512));
                }

                fclose($fileHandle);
                gzclose($gzHandle);

                // Remove uncompressed file
                unlink($tempPath);

                $filename .= '.gz';
                $tempPath = $compressedPath;
                $fileSize = filesize($tempPath);

                $this->info("Backup compressed: {$filename} (" . $this->formatBytes($fileSize) . ")");
            }

            // Upload to storage disk if specified
            $storageDisk = $this->option('storage');
            if ($storageDisk !== 'local') {
                $this->info("Uploading to {$storageDisk} storage...");

                $contents = file_get_contents($tempPath);
                Storage::disk($storageDisk)->put("backups/{$filename}", $contents);

                $this->info("Backup uploaded to {$storageDisk} storage");

                // Optionally remove local file after upload
                if (config('backup.delete_local_after_upload', false)) {
                    unlink($tempPath);
                    $this->info('Local backup file removed');
                }
            }

            // Clean old backups (keep last 30 days)
            $this->cleanOldBackups();

            $this->info('✓ Database backup completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            \Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Clean old backup files
     */
    protected function cleanOldBackups(): void
    {
        $retentionDays = config('backup.retention_days', 30);
        $backupPath = storage_path('app/backups');

        if (!is_dir($backupPath)) {
            return;
        }

        $files = glob($backupPath . '/backup_*.sql*');
        $cutoffTime = now()->subDays($retentionDays)->timestamp;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->info('Deleted old backup: ' . basename($file));
            }
        }
    }

    /**
     * Format bytes to human readable size
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
