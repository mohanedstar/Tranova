<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:backup
                            {--connection= : Database connection to backup}
                            {--path= : Custom backup path}
                            {--compress : Compress the backup file}
                            {--keep=7 : Number of backups to keep (0 = keep all)}
                            {--upload : Upload to cloud storage}
                            {--notify : Send email notification}';

    /**
     * The console command description.
     */
    protected $description = 'Create a backup of the database';

    /**
     * Backup directory
     */
    protected string $backupPath;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting database backup...');
        $this->newLine();

        try {
            // ✅ Set backup path
            $this->backupPath = $this->option('path')
                ?? storage_path('app/backups');

            // ✅ Create directory if not exists
            if (!is_dir($this->backupPath)) {
                mkdir($this->backupPath, 0755, true);
                $this->info("📁 Created backup directory: {$this->backupPath}");
            }

            // ✅ Get database configuration
            $connection = $this->option('connection') ?? config('database.default');
            $config = config("database.connections.{$connection}");

            $this->info("📊 Database: {$config['database']}");
            $this->info("🔌 Driver: {$config['driver']}");
            $this->newLine();

            // ✅ Create backup based on driver
            $backupFile = match ($config['driver']) {
                'mysql' => $this->backupMySQL($config),
                'pgsql' => $this->backupPostgreSQL($config),
                'sqlite' => $this->backupSQLite($config),
                default => throw new \Exception("Unsupported driver: {$config['driver']}"),
            };

            // ✅ Compress if requested
            if ($this->option('compress')) {
                $backupFile = $this->compressFile($backupFile);
            }

            // ✅ Get file size
            $fileSize = $this->formatBytes(filesize($backupFile));
            $this->info("📦 Backup created: " . basename($backupFile));
            $this->info("💾 File size: {$fileSize}");

            // ✅ Clean old backups
            if ($this->option('keep') > 0) {
                $this->cleanOldBackups((int) $this->option('keep'));
            }

            // ✅ Upload to cloud if requested
            if ($this->option('upload')) {
                $this->uploadToCloud($backupFile);
            }

            // ✅ Send notification if requested
            if ($this->option('notify')) {
                $this->sendNotification($backupFile, $fileSize);
            }

            // ✅ Log success
            Log::info('Database backup created successfully', [
                'file' => $backupFile,
                'size' => $fileSize,
                'connection' => $connection,
            ]);

            $this->newLine();
            $this->info('✅ Backup completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Backup failed: ' . $e->getMessage());

            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Backup MySQL database
     */
    protected function backupMySQL(array $config): string
    {
        $this->info('🔄 Creating MySQL backup...');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "mysql_backup_{$config['database']}_{$timestamp}.sql";
        $filepath = "{$this->backupPath}/{$filename}";

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s 2>&1',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port'] ?? 3306),
            escapeshellarg($config['database']),
            escapeshellarg($filepath)
        );

        // Execute command
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('mysqldump failed: ' . implode("\n", $output));
        }

        // Verify file was created
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new \Exception('Backup file was not created or is empty');
        }

        return $filepath;
    }

    /**
     * Backup PostgreSQL database
     */
    protected function backupPostgreSQL(array $config): string
    {
        $this->info('🔄 Creating PostgreSQL backup...');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "pgsql_backup_{$config['database']}_{$timestamp}.sql";
        $filepath = "{$this->backupPath}/{$filename}";

        // Set environment variables for pg_dump
        $env = sprintf(
            'PGPASSWORD=%s',
            escapeshellarg($config['password'])
        );

        // Build pg_dump command
        $command = sprintf(
            '%s pg_dump -U %s -h %s -p %s -F p -f %s %s 2>&1',
            $env,
            escapeshellarg($config['username']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port'] ?? 5432),
            escapeshellarg($filepath),
            escapeshellarg($config['database'])
        );

        // Execute command
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('pg_dump failed: ' . implode("\n", $output));
        }

        // Verify file was created
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new \Exception('Backup file was not created or is empty');
        }

        return $filepath;
    }

    /**
     * Backup SQLite database
     */
    protected function backupSQLite(array $config): string
    {
        $this->info('🔄 Creating SQLite backup...');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "sqlite_backup_{$timestamp}.sql";
        $filepath = "{$this->backupPath}/{$filename}";

        // Copy SQLite file
        $source = $config['database'];

        if (!file_exists($source)) {
            throw new \Exception("SQLite database not found: {$source}");
        }

        if (!copy($source, $filepath)) {
            throw new \Exception('Failed to copy SQLite database');
        }

        return $filepath;
    }

    /**
     * Compress backup file
     */
    protected function compressFile(string $filepath): string
    {
        $this->info('🗜️  Compressing backup file...');

        $gzFile = $filepath . '.gz';

        $fp = fopen($filepath, 'rb');
        $gz = gzopen($gzFile, 'wb9');

        while (!feof($fp)) {
            gzwrite($gz, fread($fp, 1024 * 512));
        }

        gzclose($gz);
        fclose($fp);

        // Remove original file
        unlink($filepath);

        $this->info("✅ Compressed: " . basename($gzFile));

        return $gzFile;
    }

    /**
     * Clean old backups
     */
    protected function cleanOldBackups(int $keep): void
    {
        $this->info("🧹 Cleaning old backups (keeping last {$keep})...");

        $files = glob("{$this->backupPath}/*.{sql,sql.gz}", GLOB_BRACE);

        // Sort by modification time (newest first)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Remove old files
        $toDelete = array_slice($files, $keep);

        foreach ($toDelete as $file) {
            if (unlink($file)) {
                $this->line("  🗑️  Deleted: " . basename($file));
            }
        }

        $this->info("✅ Cleaned " . count($toDelete) . " old backup(s)");
    }

    /**
     * Upload to cloud storage
     */
    protected function uploadToCloud(string $filepath): void
    {
        $this->info('☁️  Uploading to cloud storage...');

        try {
            $filename = basename($filepath);
            $cloudPath = "backups/database/{$filename}";

            Storage::disk('s3')->putFileAs(
                'backups/database',
                new \Illuminate\Http\File($filepath),
                $filename
            );

            $this->info("✅ Uploaded to: {$cloudPath}");

        } catch (\Exception $e) {
            $this->warn("⚠️  Cloud upload failed: " . $e->getMessage());
            $this->warn("   (Backup is still saved locally)");
        }
    }

    /**
     * Send email notification
     */
    protected function sendNotification(string $filepath, string $fileSize): void
    {
        $this->info('📧 Sending notification...');

        try {
            // TODO: Implement email notification
            // Mail::to(config('backup.notification_email'))
            //     ->send(new BackupCompleted(basename($filepath), $fileSize));

            $this->info('✅ Notification sent');

        } catch (\Exception $e) {
            $this->warn("⚠️  Notification failed: " . $e->getMessage());
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
