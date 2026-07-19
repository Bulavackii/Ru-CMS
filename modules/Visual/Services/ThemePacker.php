<?php

namespace Modules\Visual\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Modules\Visual\Models\Theme;
use ZipArchive;

class ThemePacker
{
    public function export(Theme $theme)
    {
        $zip = new ZipArchive();
        $fileName = "themes/{$theme->slug}.zip";
        $path = Storage::path($fileName);

        // Создаем директорию если не существует
        Storage::makeDirectory(dirname($fileName));

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Не удалось создать ZIP');
        }

        // Собираем список файлов темы
        $assets = [];
        $themeAssetsPath = resource_path("themes/{$theme->slug}");
        $publicAssetsPath = public_path("themes/{$theme->slug}");

        // Добавляем файлы из resources/themes/{slug}
        if (File::isDirectory($themeAssetsPath)) {
            $this->addDirectoryToZip($zip, $themeAssetsPath, "resources", $assets);
        }

        // Добавляем файлы из public/themes/{slug}
        if (File::isDirectory($publicAssetsPath)) {
            $this->addDirectoryToZip($zip, $publicAssetsPath, "public", $assets);
        }

        // Добавляем шаблоны из views если есть
        $viewsPath = resource_path("views/themes/{$theme->slug}");
        if (File::isDirectory($viewsPath)) {
            $this->addDirectoryToZip($zip, $viewsPath, "views", $assets);
        }

        $manifest = [
            'theme' => $theme->toArray(),
            'assets' => $assets,
            'version' => '1.0.0',
            'exported_at' => now()->toIso8601String(),
        ];

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $zip->close();

        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * Добавление директории в ZIP архив
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $basePath, array &$assets): void
    {
        if (!File::isDirectory($dir)) {
            return;
        }

        $files = File::allFiles($dir);
        
        foreach ($files as $file) {
            $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $zipPath = "{$basePath}/{$relativePath}";
            
            // Защита от Zip Slip
            $realPath = realpath($file->getPathname());
            $realDir = realpath($dir);
            if ($realPath && $realDir && strpos($realPath, $realDir) === 0) {
                $zip->addFile($file->getPathname(), $zipPath);
                $assets[] = [
                    'path' => $zipPath,
                    'size' => $file->getSize(),
                    'type' => $file->getExtension(),
                ];
            }
        }
    }

    public function import(UploadedFile $file)
    {
        $zip = new ZipArchive();
        $zip->open($file->getPathname());

        $manifestStream = $zip->getStream('manifest.json');
        if (!$manifestStream) {
            throw new \RuntimeException('manifest.json not found in ZIP');
        }
        $json = stream_get_contents($manifestStream);
        fclose($manifestStream);
        $data = json_decode($json, true);

        $themeData = $data['theme'];
        Theme::updateOrCreate(
            ['slug' => $themeData['slug']],
            $themeData
        );

        $zip->close();
    }
}
