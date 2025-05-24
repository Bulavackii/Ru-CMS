<?php

namespace App\Observers;

use Modules\News\Models\News;
use Illuminate\Support\Facades\Artisan;

class NewsObserver
{
    /**
     * Обработка события "создание новости"
     */
    public function created(News $news): void
    {
        $this->generateSitemap();
    }

    /**
     * Обработка события "обновление новости"
     */
    public function updated(News $news): void
    {
        $this->generateSitemap();
    }

    /**
     * Обработка события "удаление новости"
     */
    public function deleted(News $news): void
    {
        $this->generateSitemap();
    }

    /**
     * 📦 Генерация sitemap через Artisan-команду
     */
    protected function generateSitemap(): void
    {
        Artisan::call('sitemap:generate');
    }
}
