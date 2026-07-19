<?php

namespace App\Listeners;

use App\Events\NewsCreated;
use App\Events\NewsUpdated;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoPage;

class UpdateSeoForNews
{
    public function handle(NewsCreated|NewsUpdated $event): void
    {
        try {
            $news = $event->news;

            // Создаем или обновляем SEO запись
            $seoData = [
                'slug' => '/news/' . ltrim($news->slug, '/'),
                'title' => $news->meta_title ?? $news->title,
                'description' => $news->meta_description ?? substr(strip_tags($news->content), 0, 160),
                'keywords' => $news->meta_keywords ?? '',
                'h1' => $news->meta_header ?? $news->title,
                'index' => $news->published,
                'follow' => true,
                'canonical' => url('/news/' . $news->slug),
                'source_type' => 'news',
                'source_id' => $news->id,
                'locked' => false,
            ];

            SeoPage::updateOrCreate(
                ['slug' => $seoData['slug']],
                $seoData
            );

            Log::info('SEO updated for news', [
                'news_id' => $news->id,
                'slug' => $seoData['slug'],
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to update SEO for news', [
                'news_id' => $event->news->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
