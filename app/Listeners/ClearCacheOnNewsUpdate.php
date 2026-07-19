<?php

namespace App\Listeners;

use App\Events\NewsCreated;
use App\Events\NewsUpdated;
use App\Events\NewsDeleted;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearCacheOnNewsUpdate
{
    public function handle(NewsCreated|NewsUpdated|NewsDeleted $event): void
    {
        try {
            $news = $event->news;
            $cacheService = app('cacheService');

            // Очищаем кэш главной страницы
            Cache::forget('home_categories');
            Cache::forget('home_slideshows');
            Cache::forget('home_menus');
            Cache::forget('home_pages');

            // Очищаем тегированный кэш через CacheService
            $cacheService->invalidateNews($news->id);

            Log::info('Cache cleared after news creation', [
                'news_id' => $news->id,
                'template' => $news->template,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to clear cache on news update', [
                'news_id' => $event->news->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
