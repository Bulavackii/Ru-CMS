<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

/**
 * 🖼️ ImageOptimizationService - Оптимизация изображений
 * 
 * Обеспечивает:
 * - Автоматическое создание thumbnails
 * - Сжатие изображений
 * - Конвертацию в WebP
 * - Lazy loading поддержку
 */
class ImageOptimizationService
{
    private $imageManager;
    private int $maxWidth = 1920;
    private int $maxHeight = 1080;
    private int $quality = 85;

    public function __construct()
    {
        // Используем GD или Imagick в зависимости от доступности
        if (extension_loaded('imagick')) {
            $this->imageManager = new ImageManager(new ImagickDriver());
        } elseif (extension_loaded('gd')) {
            $this->imageManager = new ImageManager(new Driver());
        } else {
            throw new \Exception('Требуется расширение GD или Imagick для работы с изображениями');
        }
    }

    /**
     * 📦 Оптимизация загруженного изображения
     */
    public function optimize(string $path, array $options = []): array
    {
        try {
            $fullPath = Storage::path($path);
            
            if (!file_exists($fullPath)) {
                throw new \Exception("Файл не найден: {$path}");
            }

            $maxWidth = $options['max_width'] ?? $this->maxWidth;
            $maxHeight = $options['max_height'] ?? $this->maxHeight;
            $quality = $options['quality'] ?? $this->quality;
            $createThumbnail = $options['thumbnail'] ?? true;
            $convertToWebP = $options['webp'] ?? true;

            $image = $this->imageManager->read($fullPath);
            
            // Изменение размера если нужно
            $image->scaleDown($maxWidth, $maxHeight);

            // Сохранение оптимизированного изображения
            $optimizedPath = $this->saveOptimized($image, $fullPath, $quality);

            $result = [
                'original' => $path,
                'optimized' => $optimizedPath,
                'size_reduction' => $this->calculateSizeReduction($fullPath, Storage::path($optimizedPath)),
            ];

            // Создание thumbnail
            if ($createThumbnail) {
                $thumbnailPath = $this->createThumbnail($image, $fullPath, $options);
                $result['thumbnail'] = $thumbnailPath;
            }

            // Конвертация в WebP
            if ($convertToWebP && $this->supportsWebP()) {
                $webpPath = $this->convertToWebP($image, $fullPath);
                $result['webp'] = $webpPath;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            
            return ['original' => $path, 'error' => $e->getMessage()];
        }
    }

    /**
     * 💾 Сохранение оптимизированного изображения
     */
    private function saveOptimized($image, string $originalPath, int $quality): string
    {
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $optimizedPath = str_replace(".{$extension}", "_optimized.{$extension}", $originalPath);

        $image->save($optimizedPath, quality: $quality);

        return str_replace(Storage::path(''), '', $optimizedPath);
    }

    /**
     * 🖼️ Создание thumbnail
     */
    private function createThumbnail($image, string $originalPath, array $options): string
    {
        $width = $options['thumbnail_width'] ?? 300;
        $height = $options['thumbnail_height'] ?? 300;
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        
        $thumbnail = clone $image;
        $thumbnail->cover($width, $height);
        
        $thumbnailPath = str_replace(".{$extension}", "_thumb.{$extension}", $originalPath);
        $thumbnail->save($thumbnailPath, quality: 80);

        return str_replace(Storage::path(''), '', $thumbnailPath);
    }

    /**
     * 🌐 Конвертация в WebP
     */
    private function convertToWebP($image, string $originalPath): ?string
    {
        try {
            $webpPath = str_replace('.' . pathinfo($originalPath, PATHINFO_EXTENSION), '.webp', $originalPath);
            $image->toWebp(quality: 85)->save($webpPath);
            
            return str_replace(Storage::path(''), '', $webpPath);
        } catch (\Exception $e) {
            Log::warning('WebP conversion failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 📊 Расчет уменьшения размера
     */
    private function calculateSizeReduction(string $originalPath, string $optimizedPath): float
    {
        $originalSize = filesize($originalPath);
        $optimizedSize = filesize($optimizedPath);
        
        if ($originalSize === 0) {
            return 0;
        }

        return round((($originalSize - $optimizedSize) / $originalSize) * 100, 2);
    }

    /**
     * ✅ Проверка поддержки WebP
     */
    private function supportsWebP(): bool
    {
        if (extension_loaded('imagick')) {
            return in_array('WEBP', \Imagick::queryFormats());
        }
        
        return function_exists('imagewebp');
    }

    /**
     * 🔍 Получить информацию об изображении
     */
    public function getImageInfo(string $path): array
    {
        try {
            $fullPath = Storage::path($path);
            $image = $this->imageManager->read($fullPath);
            
            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'size' => filesize($fullPath),
                'mime' => mime_content_type($fullPath),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

