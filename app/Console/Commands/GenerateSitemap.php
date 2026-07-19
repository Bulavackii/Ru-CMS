<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Illuminate\Support\Facades\URL as LaravelURL;
use Modules\News\Models\News;
use Modules\Categories\Models\Category;
use Modules\Menu\Models\Page;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = '🔄 Генерирует sitemap.xml со всеми доступными маршрутами';

    public function handle(): void
    {
        $sitemap = Sitemap::create();

        // 🏠 Главная страница
        $sitemap->add(
            Url::create(url('/'))
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        );

        // 🧩 Шаблоны новостей
        $this->info('📰 Добавление новостей по шаблонам...');
        $newsByTemplate = News::where('published', true)->get()->groupBy('template');

        foreach ($newsByTemplate as $template => $items) {
            $this->info(" ➤ Шаблон: {$template} (". $items->count() .")");

            foreach ($items as $news) {
                $sitemap->add(
                    Url::create(route('news.show', $news->slug))
                        ->setLastModificationDate($news->updated_at)
                        ->setPriority($this->getPriorityForTemplate($template))
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                );
            }
        }

        // 📂 Категории
        if (class_exists(Category::class)) {
            $this->info('📂 Добавление категорий...');
            foreach (Category::all() as $category) {
                $template = $category->template ?? 'default';
                $categoryUrl = url('/?category_' . $template . '=' . $category->id);

                $sitemap->add(
                    Url::create($categoryUrl)
                        ->setPriority(0.6)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                );
            }
        }

        // 📄 Страницы (модуль Menu)
        if (class_exists(Page::class)) {
            $this->info('📄 Добавление страниц...');
            foreach (Page::all() as $page) {
                $sitemap->add(
                    Url::create(url($page->slug))
                        ->setLastModificationDate($page->updated_at)
                        ->setPriority(0.7)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                );
            }
        }

        // 💾 Сохранение карты сайта
        $sitemap->writeToFile(storage_path('app/public/sitemap.xml'));
        $this->info('✅ sitemap.xml успешно сгенерирован: public/sitemap.xml');
    }

    /**
     * 🧠 Автоопределение приоритета по шаблону
     */
    protected function getPriorityForTemplate(string $template): float
    {
        return match ($template) {
            'products' => 0.9,
            'faq'      => 0.6,
            'reviews'  => 0.7,
            default    => 0.8,
        };
    }
}
