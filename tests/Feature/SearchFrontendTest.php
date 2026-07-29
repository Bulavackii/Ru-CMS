<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * 🔍 Поиск на публичной части сайта (/search, frontend.search).
 *
 * NB: маршрут /search обслуживает App\Http\Controllers\Frontend\FrontendSearchController,
 * а не одноимённый контроллер модуля Search — тот перекрыт и в route:list не попадает.
 */
class SearchFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_opens_without_query(): void
    {
        // Подписи вьюхи теперь из словаря, а тестовое окружение стартует
        // на en — привязываем локаль, иначе проверяется английский текст.
        $response = $this->withSession(['app_locale' => 'ru'])->get(route('frontend.search'));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.search.results');
        $response->assertSee('Начните поиск');
    }

    public function test_published_pages_are_searchable(): void
    {
        // Раньше поиск на сайте искал только новости, поэтому статические
        // страницы («О проекте» и прочие) не находились вообще.
        $page = Page::create([
            'title' => 'О проекте',
            'slug' => 'o-proekte',
            'content' => 'Рассказ о проекте',
            'published' => true,
        ]);

        $response = $this->get(route('frontend.search', ['q' => 'проекте']));

        $response->assertStatus(200);
        $response->assertSee(route('frontend.pages.show', $page->slug), false);
        $this->assertCount(1, $response->viewData('pages'));
    }

    public function test_unpublished_content_is_hidden(): void
    {
        Page::create([
            'title' => 'Черновик страницы',
            'slug' => 'chernovik',
            'content' => 'Секрет',
            'published' => false,
        ]);

        News::create([
            'title' => 'Черновик новости',
            'content' => 'Секрет',
            'template' => 'default',
            'published' => false,
        ]);

        $response = $this->get(route('frontend.search', ['q' => 'Черновик']));

        $response->assertStatus(200);
        $this->assertSame(0, $response->viewData('total'));
    }

    public function test_search_is_case_insensitive(): void
    {
        // На SQLite (тесты) LIKE регистронезависим только для латиницы;
        // кириллицу на боевом Postgres покрывает ILIKE из search_like().
        News::create([
            'title' => 'Laravel Modules',
            'content' => 'Текст',
            'template' => 'default',
            'published' => true,
        ]);

        $response = $this->get(route('frontend.search', ['q' => 'LARAVEL']));

        $response->assertStatus(200);
        $this->assertSame(1, $response->viewData('total'));
    }

    public function test_too_short_query_is_reported(): void
    {
        $response = $this->withSession(['app_locale' => 'ru'])
            ->get(route('frontend.search', ['q' => 'a']));

        $response->assertStatus(200);
        $response->assertSee('Слишком короткий запрос');
    }
}
