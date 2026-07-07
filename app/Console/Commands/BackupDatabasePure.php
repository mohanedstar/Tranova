<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupDatabasePure extends Command
{
    /**
     * Backup using pure PHP (no mysqldump needed)
     */
    protected $signature = 'db:backup-pure
                            {--path= : Custom backup path}
                            {--compress : Compress the backup file}
                            {--keep=7 : Number of backups to keep}
                            {--tables-only : Backup only structure (no data)}
                            {--data-only : Backup only data (no structure)}';

    protected $description = 'Backup database using pure PHP (no mysqldump needed)';

    protected string $backupPath;
    protected int $queryCount = 0;
    protected int $tableCount = 0;

    public function handle(): int
    {
        $this->info('🚀 Starting pure PHP database backup...');
        $this->newLine();

        try {
            // ✅ Set backup path
            $this->backupPath = $this->option('path')
                ?? storage_path('app/backups');

            if (!is_dir($this->backupPath)) {
                mkdir($this->backupPath, 0755, true);
                $this->info("📁 Created backup directory: {$this->backupPath}");
            }

            // ✅ Get database connection
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            $this->info("📊 Database: {$config['database']}");
            $this->info("🔌 Driver: {$config['driver']}");
            $this->newLine();

            // ✅ Only MySQL is supported for pure backup
            if ($config['driver'] !== 'mysql') {
                throw new \Exception("Pure backup only supports MySQL. Current: {$config['driver']}");
            }

            // ✅ Create backup
            $backupFile = $this->createBackup($config);

            // ✅ Compress if requested
            if ($this->option('compress')) {
                $backupFile = $this->compressFile($backupFile);
            }

            // ✅ Get stats
            $fileSize = $this->formatBytes(filesize($backupFile));

            $this->newLine();
            $this->info("📦 Backup created: " . basename($backupFile));
            $this->info("💾 File size: {$fileSize}");
            $this->info("📊 Tables: {$this->tableCount}");
            $this->info("📝 Queries: {$this->queryCount}");

            // ✅ Clean old backups
            if ($this->option('keep') > 0) {
                $this->cleanOldBackups((int) $this->option('keep'));
            }

            // ✅ Log success
            Log::info('Database backup created (pure PHP)', [
                'file' => $backupFile,
                'size' => $fileSize,
                'tables' => $this->tableCount,
            ]);

            $this->newLine();
            $this->info('✅ Backup completed successfully!');
            $this->info("📂 Location: {$backupFile}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Backup failed: ' . $e->getMessage());

            Log::error('Database backup failed (pure PHP)', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Create backup using PDO
     */
    protected function createBackup(array $config): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "mysql_backup_{$config['database']}_{$timestamp}.sql";
        $filepath = "{$this->backupPath}/{$filename}";

        $this->info('🔄 Connecting to database...');

        $pdo = DB::connection()->getPdo();

        $this->info('✅ Connected successfully');
        $this->newLine();

        // ✅ Get all tables
        $tables = $this->getAllTables($pdo);
        $this->info("📊 Found {$this->tableCount} tables");
        $this->newLine();

        // ✅ Start building SQL
        $sql = "-- ============================================\n";
        $sql .= "-- Trinova Database Backup (Pure PHP)\n";
        $sql .= "-- ============================================\n";
        $sql .= "-- Database: {$config['database']}\n";
        $sql .= "-- Date: " . now()->toDateTimeString() . "\n";
        $sql .= "-- Tables: {$this->tableCount}\n";
        $sql .= "-- ============================================\n\n";

        // ✅ Disable foreign key checks
        $sql .= "SET NAMES utf8mb4;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n\n";

        // ✅ Backup each table
        $bar = $this->output->createProgressBar(count($tables));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        foreach ($tables as $table) {
            $bar->setMessage($table);

            // ✅ Table structure
            if (!$this->option('data-only')) {
                $sql .= $this->getTableStructure($pdo, $table);
            }

            // ✅ Table data
            if (!$this->option('tables-only')) {
                $sql .= $this->getTableData($pdo, $table);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // ✅ Re-enable foreign key checks
        $sql .= "COMMIT;\n";
        $sql .= "SET AUTOCOMMIT = 1;\n";
        $sql .= "SET SQL_MODE = '';\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // ✅ Write to file
        file_put_contents($filepath, $sql);

        return $filepath;
    }

    /**
     * Get all table names
     */
    protected function getAllTables($pdo): array
    {
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");

        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
            $this->tableCount++;
        }

        return $tables;
    }

    /**
     * Get table structure (CREATE TABLE)
     */
    protected function getTableStructure($pdo, string $table): string
    {
        $sql = "-- -------------------------------------------\n";
        $sql .= "-- Table: {$table}\n";
        $sql .= "-- -------------------------------------------\n\n";

        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $row['Create Table'] . ";\n\n";

        $this->queryCount++;

        return $sql;
    }

    /**
     * Get table data (INSERT statements)
     */
    protected function getTableData($pdo, string $table): string
    {
        $sql = "";

        // ✅ Get row count first
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $rowCount = $countStmt->fetchColumn();

        if ($rowCount === 0) {
            return "-- No data in table: {$table}\n\n";
        }

        // ✅ Get all data
        $stmt = $pdo->query("SELECT * FROM `{$table}`");

        $batchSize = 100; // Insert 100 rows at a time
        $batch = [];
        $columns = null;

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($columns === null) {
                $columns = array_keys($row);
            }

            $values = array_map(function($value) use ($pdo) {
                if ($value === null) {
                    return 'NULL';
                }
                return $pdo->quote($value);
            }, array_values($row));

            $batch[] = '(' . implode(', ', $values) . ')';

            // ✅ Write batch when full
            if (count($batch) >= $batchSize) {
                $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                $sql .= implode(",\n", $batch) . ";\n\n";
                $batch = [];
                $this->queryCount++;
            }
        }

        // ✅ Write remaining batch
        if (!empty($batch)) {
            $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
            $sql .= implode(",\n", $batch) . ";\n\n";
            $this->queryCount++;
        }

        return $sql;
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

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $toDelete = array_slice($files, $keep);

        foreach ($toDelete as $file) {
            if (unlink($file)) {
                $this->line("  🗑️  Deleted: " . basename($file));
            }
        }

        $this->info("✅ Cleaned " . count($toDelete) . " old backup(s)");
    }

    /**
     * Format bytes
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
