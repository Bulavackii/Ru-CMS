<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

/**
 * 🔄 UpdateService - Централизованная система обновлений
 * 
 * Обеспечивает:
 * - Проверку обновлений через центральный сервер
 * - Безопасную установку обновлений
 * - Откат при ошибках
 * - Логирование всех операций
 */
class UpdateService
{
    private string $updateServerUrl;
    private string $licenseKey;
    private string $currentVersion;

    public function __construct()
    {
        $this->updateServerUrl = config('app.update_server_url', 'https://updates.rucms.ru/api');
        $this->licenseKey = config('app.license_key', '');
        $this->currentVersion = config('app.version', '1.0.0');
    }

    /**
     * 🔍 Проверка доступных обновлений
     */
    public function checkForUpdates(): array
    {
        $cacheKey = 'updates:available';
        
        return Cache::remember($cacheKey, 3600, function () {
            try {
                $response = Http::timeout(10)->post("{$this->updateServerUrl}/check", [
                    'license_key' => $this->licenseKey,
                    'current_version' => $this->currentVersion,
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return [
                        'available' => $data['available'] ?? false,
                        'latest_version' => $data['latest_version'] ?? $this->currentVersion,
                        'current_version' => $this->currentVersion,
                        'changelog' => $data['changelog'] ?? [],
                        'security_update' => $data['security_update'] ?? false,
                        'download_url' => $data['download_url'] ?? null,
                        'checksum' => $data['checksum'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Update check failed', ['error' => $e->getMessage()]);
            }

            return [
                'available' => false,
                'current_version' => $this->currentVersion,
            ];
        });
    }

    /**
     * 📥 Загрузка обновления
     */
    public function downloadUpdate(string $version): ?string
    {
        try {
            $updateInfo = $this->checkForUpdates();
            
            if (!$updateInfo['available'] || $updateInfo['latest_version'] !== $version) {
                throw new \Exception('Update not available');
            }

            $downloadUrl = $updateInfo['download_url'];
            if (!$downloadUrl) {
                throw new \Exception('Download URL not provided');
            }

            // Загрузка файла
            $response = Http::timeout(300)->get($downloadUrl);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to download update');
            }

            // Сохранение во временную директорию
            $tempPath = storage_path("app/updates/update-{$version}.zip");
            File::ensureDirectoryExists(dirname($tempPath));
            File::put($tempPath, $response->body());

            // Проверка checksum
            if (isset($updateInfo['checksum'])) {
                $calculatedChecksum = hash_file('sha256', $tempPath);
                if ($calculatedChecksum !== $updateInfo['checksum']) {
                    File::delete($tempPath);
                    throw new \Exception('Checksum verification failed');
                }
            }

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Update download failed', [
                'version' => $version,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 📦 Установка обновления
     */
    public function installUpdate(string $updatePath): bool
    {
        try {
            // Создание бэкапа
            $backupPath = $this->createBackup();
            
            if (!$backupPath) {
                throw new \Exception('Failed to create backup');
            }

            // Распаковка обновления
            $extractPath = storage_path('app/updates/extract');
            File::deleteDirectory($extractPath);
            File::ensureDirectoryExists($extractPath);

            $zip = new ZipArchive;
            if ($zip->open($updatePath) !== true) {
                throw new \Exception('Failed to open update archive');
            }

            // Безопасная распаковка
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                
                // Защита от Zip Slip
                $targetPath = realpath($extractPath) . DIRECTORY_SEPARATOR . $entry;
                if (strpos($targetPath, realpath($extractPath)) !== 0) {
                    $zip->close();
                    throw new \Exception("Invalid path in archive: {$entry}");
                }
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Применение обновления
            $this->applyUpdate($extractPath);

            // Очистка
            File::deleteDirectory($extractPath);
            File::delete($updatePath);

            // Обновление версии
            $this->updateVersion();

            Log::info('Update installed successfully', ['version' => $this->currentVersion]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Update installation failed', ['error' => $e->getMessage()]);
            
            // Откат при ошибке
            if (isset($backupPath)) {
                $this->restoreBackup($backupPath);
            }
            
            return false;
        }
    }

    /**
     * 💾 Создание бэкапа перед обновлением
     */
    private function createBackup(): ?string
    {
        try {
            $backupName = 'backup-' . date('Y-m-d-H-i-s') . '.zip';
            $backupPath = storage_path("app/backups/{$backupName}");
            
            File::ensureDirectoryExists(dirname($backupPath));

            $zip = new ZipArchive;
            if ($zip->open($backupPath, ZipArchive::CREATE) !== true) {
                return null;
            }

            // Бэкап важных директорий
            $directories = [
                'app',
                'config',
                'database/migrations',
                'routes',
                'resources/views',
            ];

            foreach ($directories as $dir) {
                $fullPath = base_path($dir);
                if (File::isDirectory($fullPath)) {
                    $this->addDirectoryToZip($zip, $fullPath, $dir);
                }
            }

            $zip->close();
            
            return $backupPath;
        } catch (\Exception $e) {
            Log::error('Backup creation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 🔄 Откат к бэкапу
     */
    private function restoreBackup(string $backupPath): bool
    {
        try {
            if (!File::exists($backupPath)) {
                Log::error('Backup file not found', ['path' => $backupPath]);
                return false;
            }

            Log::info('Backup restore initiated', ['backup' => $backupPath]);

            // Распаковка бэкапа
            $extractPath = storage_path('app/backups/restore');
            File::deleteDirectory($extractPath);
            File::ensureDirectoryExists($extractPath);

            $zip = new ZipArchive;
            if ($zip->open($backupPath) !== true) {
                throw new \Exception('Failed to open backup archive');
            }

            // Безопасная распаковка
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                
                // Защита от Zip Slip
                $targetPath = realpath($extractPath) . DIRECTORY_SEPARATOR . $entry;
                if (strpos($targetPath, realpath($extractPath)) !== 0) {
                    $zip->close();
                    throw new \Exception("Invalid path in backup archive: {$entry}");
                }
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Восстановление файлов
            $restoreFiles = File::allFiles($extractPath);
            
            foreach ($restoreFiles as $file) {
                $relativePath = str_replace($extractPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $targetPath = base_path($relativePath);
                
                // Пропускаем файлы, которые не должны быть восстановлены
                if (str_contains($relativePath, '.env') || str_contains($relativePath, 'storage/')) {
                    continue;
                }
                
                File::ensureDirectoryExists(dirname($targetPath));
                File::copy($file->getPathname(), $targetPath);
            }

            // Очистка
            File::deleteDirectory($extractPath);

            // Очистка кеша после восстановления
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            Log::info('Backup restored successfully', ['backup' => $backupPath]);
            return true;
        } catch (\Exception $e) {
            Log::error('Backup restore failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 📝 Применение обновления
     */
    private function applyUpdate(string $extractPath): void
    {
        // Копирование файлов
        $updateFiles = File::allFiles($extractPath);
        
        foreach ($updateFiles as $file) {
            $relativePath = str_replace($extractPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $targetPath = base_path($relativePath);
            
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
        }

        // Запуск миграций если есть
        if (File::exists($extractPath . '/database/migrations')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        // Очистка кеша
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
    }

    /**
     * 🔢 Обновление версии в конфиге
     */
    private function updateVersion(): void
    {
        try {
            $newVersion = $this->checkForUpdates()['latest_version'] ?? $this->currentVersion;
            
            // Обновление версии в .env
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $env = File::get($envPath);
                
                if (preg_match('/^APP_VERSION=.*$/m', $env)) {
                    $env = preg_replace('/^APP_VERSION=.*$/m', "APP_VERSION={$newVersion}", $env);
                } else {
                    $env .= "\nAPP_VERSION={$newVersion}";
                }
                
                File::put($envPath, $env);
            }

            // Обновление версии в config/app.php (если есть константа)
            $configPath = config_path('app.php');
            if (File::exists($configPath)) {
                $config = File::get($configPath);
                
                // Ищем определение версии в конфиге
                if (preg_match("/'version'\s*=>\s*['\"]([^'\"]+)['\"]/", $config, $matches)) {
                    $config = preg_replace(
                        "/'version'\s*=>\s*['\"]([^'\"]+)['\"]/",
                        "'version' => '{$newVersion}'",
                        $config
                    );
                    File::put($configPath, $config);
                }
            }

            $this->currentVersion = $newVersion;
            Log::info('Version updated', ['new_version' => $newVersion]);
        } catch (\Exception $e) {
            Log::error('Version update failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 📁 Добавление директории в ZIP
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $basePath): void
    {
        $files = File::allFiles($dir);
        
        foreach ($files as $file) {
            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getRelativePathname());
            $zip->addFile($file->getPathname(), $basePath . '/' . $relativePath);
        }
    }
}

