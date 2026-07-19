<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 💾 BackupDatabase - Автоматическое резервное копирование БД
 */
class BackupDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Выполнение задачи
     */
    public function handle(): void
    {
        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            
            $backupPath = $this->createDatabaseBackup($database, $connection);
            
            if ($backupPath) {
                // Загрузка в облако (если настроено)
                $this->uploadToCloud($backupPath);
                
                // Очистка старых бэкапов
                $this->cleanOldBackups();
                
                Log::info('Database backup completed', ['path' => $backupPath]);
            }
        } catch (\Exception $e) {
            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Создание бэкапа БД
     */
    private function createDatabaseBackup(string $database, string $connection): ?string
    {
        $driver = config("database.connections.{$connection}.driver");
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$database}_{$timestamp}.sql";
        
        $backupDir = storage_path('app/backups/database');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupPath = $backupDir . '/' . $filename;
        
        switch ($driver) {
            case 'mysql':
                return $this->backupMySQL($database, $backupPath);
            case 'pgsql':
                return $this->backupPostgreSQL($database, $backupPath);
            case 'sqlite':
                return $this->backupSQLite($database, $backupPath);
            default:
                Log::warning("Unsupported database driver: {$driver}");
                return null;
        }
    }

    /**
     * Бэкап MySQL
     */
    private function backupMySQL(string $database, string $backupPath): ?string
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($backupPath)) {
            // Сжатие
            $compressedPath = $backupPath . '.gz';
            exec("gzip -c {$backupPath} > {$compressedPath}");
            unlink($backupPath);
            
            return $compressedPath;
        }
        
        return null;
    }

    /**
     * Бэкап PostgreSQL
     */
    private function backupPostgreSQL(string $database, string $backupPath): ?string
    {
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port', 5432);
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');
        
        putenv("PGPASSWORD={$password}");
        
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -F c -f %s %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($backupPath),
            escapeshellarg($database)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($backupPath)) {
            return $backupPath;
        }
        
        return null;
    }

    /**
     * Бэкап SQLite
     */
    private function backupSQLite(string $database, string $backupPath): ?string
    {
        $dbPath = database_path($database);
        
        if (file_exists($dbPath)) {
            copy($dbPath, $backupPath);
            return $backupPath;
        }
        
        return null;
    }

    /**
     * Загрузка в облако
     */
    private function uploadToCloud(string $backupPath): void
    {
        $cloudDriver = config('backup.cloud_driver');
        
        if (!$cloudDriver) {
            return;
        }
        
        try {
            $filename = basename($backupPath);
            $cloudPath = "backups/database/{$filename}";
            
            Storage::disk($cloudDriver)->put($cloudPath, file_get_contents($backupPath));
            
            Log::info('Backup uploaded to cloud', ['path' => $cloudPath]);
        } catch (\Exception $e) {
            Log::warning('Failed to upload backup to cloud', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Очистка старых бэкапов
     */
    private function cleanOldBackups(): void
    {
        $backupDir = storage_path('app/backups/database');
        $retentionDays = config('backup.retention_days', 30);
        
        if (!is_dir($backupDir)) {
            return;
        }
        
        $files = glob($backupDir . '/*.{sql,sql.gz,custom}', GLOB_BRACE);
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate->timestamp) {
                unlink($file);
                Log::info('Old backup deleted', ['file' => basename($file)]);
            }
        }
    }
}

