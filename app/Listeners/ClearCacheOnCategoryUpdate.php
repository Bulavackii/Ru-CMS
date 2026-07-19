<?php

namespace App\Listeners;

use App\Events\CategoryCreated;
use App\Events\CategoryUpdated;
use App\Events\CategoryDeleted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🗑️ Слушатель для очистки кэша при изменении категорий
 */
class ClearCacheOnCategoryUpdate
{
    /**
     * Handle the event.
     *
     * @param CategoryCreated|CategoryUpdated|CategoryDeleted $event
     * @return void
     */
    public function handle(CategoryCreated|CategoryUpdated|CategoryDeleted $event): void
    {
        try {
            $category = $event->category;

            // Очищаем кэш главной страницы
            Cache::forget('home_categories');
            Cache::forget('home_slideshows');
            Cache::forget('home_menus');
            Cache::forget('home_pages');

            // Очищаем кэш категорий по типу
            if ($category->type) {
                Cache::forget("categories_type_{$category->type}");
            }

            // Очищаем общий кэш категорий
            Cache::forget('categories_all');
            Cache::forget("category_{$category->id}");

            // Очищаем кэш родительских категорий, если есть родитель
            if ($category->parent_id) {
                Cache::forget("category_{$category->parent_id}_children");
            }

            Log::info('Cache cleared after category update', [
                'category_id' => $category->id,
                'type' => $category->type,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to clear cache on category update', [
                'category_id' => $event->category->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}




