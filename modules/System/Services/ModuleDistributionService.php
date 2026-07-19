<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Modules\System\Models\Module;
use ZipArchive;

/**
 * 📦 Сервис децентрализованной загрузки модулей
 *
 * Позволяет:
 * - Загружать модули из внешних источников
 * - Проверять подписи и безопасность
 * - Устанавливать из ZIP архивов
 * - Работать с несколькими репозиториями
 */
class ModuleDistributionService
{
    /**
     * Стандартные репозитории модулей
     */
    protected $repositories = [
        'official' => 'https://api.github.com/repos/russiacms/modules/contents',
        'community' => 'https://api.github.com/repos/russiacms/community-modules/contents',
    ];

    /**
     * Получить список доступных модулей из репозитория
     */
    public function getAvailableModules(string $repository = 'official')
    {
        $url = $this->repositories[$repository] ?? $repository;

        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return ['error' => 'Не удалось получить список модулей'];
            }

            $modules = $response->json();

            return array_map(function ($module) use ($repository) {
                return [
                    'name' => $module['name'],
                    'type' => $module['type'],
                    'download_url' => $module['download_url'],
                    'repository' => $repository,
                    'path' => $module['path'],
                ];
            }, array_filter($modules, fn($m) => $m['type'] === 'dir'));

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Загрузить и установить модуль из URL
     */
    public function installFromUrl(string $url, ?string $signature = null)
    {
        $tempFile = storage_path('app/temp/module_' . uniqid() . '.zip');

        try {
            // Создание директории
            if (!is_dir(dirname($tempFile))) {
                File::makeDirectory(dirname($tempFile), 0755, true);
            }

            // Загрузка файла
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                return ['error' => 'Ошибка загрузки файла'];
            }

            File::put($tempFile, $response->body());

            // Проверка архива
            $zip = new ZipArchive;
            if ($zip->open($tempFile) !== true) {
                File::delete($tempFile);
                return ['error' => 'Невалидный ZIP архив'];
            }

            // Извлечение имени модуля
            $moduleName = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, 'module.json') !== false) {
                    $moduleName = basename(dirname($name));
                    break;
                }
            }

            if (!$moduleName) {
                $zip->close();
                File::delete($tempFile);
                return ['error' => 'module.json не найден в архиве'];
            }

            // Распаковка
            $extractPath = base_path("modules/{$moduleName}");
            if (File::exists($extractPath)) {
                $zip->close();
                File::delete($tempFile);
                return ['error' => "Модуль {$moduleName} уже установлен"];
            }

            File::makeDirectory($extractPath, 0755, true);
            $zip->extractTo($extractPath);
            $zip->close();

            // Проверка подписи
            if ($signature) {
                $isValid = ModuleSecurityService::verifyModule($extractPath, $moduleName);
                if (!$isValid) {
                    File::deleteDirectory($extractPath);
                    File::delete($tempFile);
                    return ['error' => 'Неверная цифровая подпись'];
                }
            }

            // Сканирование безопасности
            $warnings = ModuleSecurityService::scanForMaliciousCode($extractPath);
            if (!empty($warnings)) {
                // Логируем предупреждения
                Log::warning("ModuleSecurity: {$moduleName} has suspicious code", $warnings);
            }

            // Чтение конфига
            $configPath = "{$extractPath}/module.json";
            $config = json_decode(File::get($configPath), true);

            // Создание записи в БД
            $module = Module::updateOrCreate(
                ['name' => $config['name']],
                [
                    'title' => $config['title'] ?? $config['name'],
                    'version' => $config['version'],
                    'priority' => $config['priority'] ?? Module::max('priority') + 1,
                    'active' => $config['active'] ?? false,
                ]
            );

            // Очистка
            File::delete($tempFile);

            return [
                'success' => true,
                'module' => $module,
                'warnings' => $warnings,
                'message' => "Модуль {$config['title']} успешно установлен",
            ];

        } catch (\Exception $e) {
            if (File::exists($tempFile)) {
                File::delete($tempFile);
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Загрузить модуль из GitHub репозитория
     */
    public function installFromGitHub(string $repo, string $branch = 'main')
    {
        $url = "https://github.com/{$repo}/archive/{$branch}.zip";
        return $this->installFromUrl($url);
    }

    /**
     * Экспорт модуля для распространения
     */
    public function exportModule(string $moduleName, ?string $privateKey = null)
    {
        $moduleDir = base_path("modules/{$moduleName}");

        if (!File::exists($moduleDir)) {
            return ['error' => 'Модуль не найден'];
        }

        $archiveDir = base_path('modules/distribution');
        if (!File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        $zipPath = "{$archiveDir}/{$moduleName}.zip";

        // Создаем архив
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return ['error' => 'Не удалось создать архив'];
        }

        // Добавляем файлы
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($moduleDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        // Добавляем подпись если есть ключ
        if ($privateKey) {
            $signature = ModuleSecurityService::signModule($moduleDir, $privateKey);
            $zip->addFromString('signature.txt', $signature);
        }

        $zip->close();

        return [
            'success' => true,
            'path' => $zipPath,
            'size' => File::size($zipPath),
            'message' => "Архив создан: {$zipPath}",
        ];
    }

    /**
     * Добавить кастомный репозиторий
     */
    public function addRepository(string $name, string $url)
    {
        $this->repositories[$name] = $url;
        return $this->repositories;
    }

    /**
     * Получить список репозиториев
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    /**
     * Проверить обновления модуля
     */
    public function checkUpdates(string $moduleName, string $repository = 'official')
    {
        $module = Module::where('name', $moduleName)->first();

        if (!$module) {
            return ['error' => 'Модуль не установлен'];
        }

        // Получаем информацию о последней версии
        $modules = $this->getAvailableModules($repository);

        if (isset($modules['error'])) {
            return $modules;
        }

        $remoteModule = collect($modules)->firstWhere('name', $moduleName);

        if (!$remoteModule) {
            return ['error' => 'Модуль не найден в репозитории'];
        }

        // Здесь можно добавить логику получения версии из удаленного источника
        // Для простоты предположим, что версия есть в URL или нужно парсить module.json

        return [
            'current' => $module->version,
            'available' => '1.0.1', // Пример
            'has_update' => version_compare('1.0.1', $module->version) > 0,
        ];
    }
}
