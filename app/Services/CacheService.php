<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💾 CacheService - Расширенное управление кэшем
 * 
 * Обеспечивает:
 * - Tagged cache для группировки
 * - Автоматическую инвалидацию
 * - Статистику кэша
 * - Оптимизацию запросов
 */
class CacheService
{
    /**
     * 🏷️ Кэширование с тегами
     */
    public function rememberTagged(string $tag, string $key, $callback, int $ttl = 3600)
    {
        $cacheKey = "{$tag}:{$key}";
        
        return Cache::tags([$tag])->remember($cacheKey, $ttl, $callback);
    }

    /**
     * 🗑️ Очистка кэша по тегу
     */
    public function forgetTagged(string $tag): void
    {
        try {
            Cache::tags([$tag])->flush();
        } catch (\Exception $e) {
            Log::warning("Failed to flush cache tag: {$tag}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * 📋 Кэширование меню
     */
    public function rememberMenu(string $position, $callback, int $ttl = 3600)
    {
        return $this->rememberTagged('menu', $position, $callback, $ttl);
    }

    /**
     * 📰 Кэширование новостей
     */
    public function rememberNews(string $key, $callback, int $ttl = 1800)
    {
        return $this->rememberTagged('news', $key, $callback, $ttl);
    }

    /**
     * 🏷️ Кэширование категорий
     */
    public function rememberCategories($callback, int $ttl = 3600)
    {
        return $this->rememberTagged('categories', 'all', $callback, $ttl);
    }

    /**
     * ⚙️ Кэширование настроек
     */
    public function rememberSettings(string $key, $callback, int $ttl = 86400)
    {
        return $this->rememberTagged('settings', $key, $callback, $ttl);
    }

    /**
     * 🔄 Инвалидация кэша при обновлении новости
     */
    public function invalidateNews(int $newsId): void
    {
        $this->forgetTagged('news');
        Cache::forget("news:{$newsId}");
    }

    /**
     * 🔄 Инвалидация кэша при обновлении категории
     */
    public function invalidateCategory(int $categoryId): void
    {
        $this->forgetTagged('categories');
        $this->forgetTagged('news'); // Новости тоже зависят от категорий
    }

    /**
     * 🔄 Инвалидация кэша при обновлении меню
     */
    public function invalidateMenu(string $position): void
    {
        $this->forgetTagged('menu');
        Cache::forget("menu:{$position}");
    }

    /**
     * 📊 Получить статистику кэша
     */
    public function getStats(): array
    {
        $driver = config('cache.default');
        
        return [
            'driver' => $driver,
            'prefix' => config('cache.prefix'),
            'stores' => array_keys(config('cache.stores', [])),
        ];
    }

    /**
     * 🧹 Очистка всего кэша
     */
    public function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * 🔍 Проверка существования ключа
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }
}

