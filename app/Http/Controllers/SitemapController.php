<?php

namespace App\Http\Controllers;

use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\URL as URLFacade;
use Illuminate\Http\Request;
use Modules\News\Models\News;
use Modules\Categories\Models\Category;
use Modules\Menu\Models\Page; // если модуль Menu существует

class SitemapController extends Controller
{
    public function __invoke(Request $request)
    {
        $sitemap = Sitemap::create();

        // 🏠 Главная
        $sitemap->add(
            Url::create(url('/'))
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        );

        // 📰 Новости (оптимизация: выбираем только нужные поля)
        foreach (News::select('id', 'slug', 'updated_at')
            ->where('published', true)
            ->get() as $news) {
            $sitemap->add(
                Url::create(route('news.show', $news->slug))
                    ->setLastModificationDate($news->updated_at)
                    ->setPriority(0.8)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            );
        }

        // 📂 Категории (оптимизация: выбираем только нужные поля)
        foreach (Category::select('id', 'template')->get() as $cat) {
            $sitemap->add(
                Url::create(url('/?category_' . $cat->template . '=' . $cat->id))
                    ->setPriority(0.6)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            );
        }

        // 📄 Страницы (если модуль Page есть) - оптимизация: выбираем только нужные поля
        if (class_exists(Page::class)) {
            foreach (Page::select('id', 'slug', 'updated_at', 'published')
                ->where('published', true)
                ->get() as $page) {
                $sitemap->add(
                    Url::create(url($page->slug))
                        ->setLastModificationDate($page->updated_at)
                        ->setPriority(0.7)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                );
            }
        }

        return $sitemap->toResponse($request);
    }
}
