<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;

/**
 * 📁 BackupFiles - Резервное копирование файлов
 */
class BackupFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    /**
     * Выполнение задачи
     */
    public function handle(): void
    {
        try {
            $backupPath = $this->createFilesBackup();
            
            if ($backupPath) {
                // Загрузка в облако
                $this->uploadToCloud($backupPath);
                
                // Очистка старых бэкапов
                $this->cleanOldBackups();
                
                Log::info('Files backup completed', ['path' => $backupPath]);
            }
        } catch (\Exception $e) {
            Log::error('Files backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Создание бэкапа файлов
     */
    private function createFilesBackup(): ?string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "files_backup_{$timestamp}.zip";
        
        $backupDir = storage_path('app/backups/files');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupPath = $backupDir . '/' . $filename;
        
        $zip = new ZipArchive;
        if ($zip->open($backupPath, ZipArchive::CREATE) !== true) {
            return null;
        }
        
        // Бэкап загруженных файлов
        $this->addDirectoryToZip($zip, storage_path('app/public'), 'public');
        
        // Бэкап конфигов (опционально)
        if (config('backup.include_config', false)) {
            $this->addDirectoryToZip($zip, config_path(), 'config');
        }
        
        $zip->close();
        
        return file_exists($backupPath) ? $backupPath : null;
    }

    /**
     * Добавление директории в ZIP
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $basePath): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $basePath . '/' . substr($filePath, strlen($dir) + 1);
                
                $zip->addFile($filePath, $relativePath);
            }
        }
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
            $cloudPath = "backups/files/{$filename}";
            
            Storage::disk($cloudDriver)->put($cloudPath, file_get_contents($backupPath));
            
            Log::info('Files backup uploaded to cloud', ['path' => $cloudPath]);
        } catch (\Exception $e) {
            Log::warning('Failed to upload files backup to cloud', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Очистка старых бэкапов
     */
    private function cleanOldBackups(): void
    {
        $backupDir = storage_path('app/backups/files');
        $retentionDays = config('backup.retention_days', 30);
        
        if (!is_dir($backupDir)) {
            return;
        }
        
        $files = glob($backupDir . '/*.zip');
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate->timestamp) {
                unlink($file);
                Log::info('Old files backup deleted', ['file' => basename($file)]);
            }
        }
    }
}

