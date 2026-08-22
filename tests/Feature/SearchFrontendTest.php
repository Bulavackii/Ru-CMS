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
        // Подпись переписана вместе со страницей: прежняя «Начните поиск»
        // сообщала очевидное, а поле ввода живёт в шапке сайта.
        $response->assertSee('Что найти на сайте?');
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

    public function test_nothing_found_offers_materials_instead_of_a_dead_end_button(): void
    {
        // Раньше единственным действием при пустой выдаче была кнопка
        // «Все новости»: она уводила из поиска в список из семи десятков
        // записей и ничем не помогала уточнить запрос.
        News::create([
            'title' => 'Свежий материал',
            'slug' => 'svezhiy-material',
            'content' => 'Текст',
            'template' => 'default',
            'published' => true,
        ]);

        $response = $this->withSession(['app_locale' => 'ru'])
            ->get(route('frontend.search', ['q' => 'ываыва']));

        $response->assertStatus(200);
        $this->assertSame(0, $response->viewData('total'));

        // Запрос назван прямо в заголовке — а не «Ничего не найдено»
        // отдельной строкой и тем же текстом ещё раз под ней.
        $response->assertSee('«ываыва» ничего не нашлось', false);

        // Крупной кнопки-тупика нет, зато есть чем заняться дальше.
        $response->assertDontSee('class="fx-btn', false);
        $response->assertSee('Свежий материал');
    }

    public function test_soft_search_finds_by_word_stem(): void
    {
        // В русском окончания меняются: точного совпадения нет, а материал есть.
        // Регистр запроса совпадает с заголовком намеренно: на SQLite LIKE
        // регистронезависим только для латиницы (на бою это ILIKE).
        News::create([
            'title' => 'Модульность системы',
            'slug' => 'modulnost',
            'content' => 'Текст',
            'template' => 'default',
            'published' => true,
        ]);

        $response = $this->withSession(['app_locale' => 'ru'])
            ->get(route('frontend.search', ['q' => 'Модульностью']));

        $response->assertStatus(200);
        $this->assertSame(1, $response->viewData('total'));
        $this->assertTrue($response->viewData('approximate'));

        // О подмене говорим прямо, иначе выдача выглядит ошибкой поиска.
        $response->assertSee('Точных совпадений нет', false);
    }

    public function test_title_match_outranks_body_match(): void
    {
        // Совпадение в тексте — материал свежее, то есть по прежней сортировке
        // «по дате» он стоял бы первым, а нужный уезжал вниз.
        News::create([
            'title' => 'Совсем про другое',
            'slug' => 'drugoe',
            'content' => 'Где-то в глубине текста встречается слово Лицензия.',
            'template' => 'default',
            'published' => true,
            'created_at' => now(),
        ]);

        News::create([
            'title' => 'Лицензия и поддержка',
            'slug' => 'licenziya',
            'content' => 'Текст',
            'template' => 'default',
            'published' => true,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('frontend.search', ['q' => 'Лицензия']));

        $response->assertStatus(200);
        $this->assertSame(
            'Лицензия и поддержка',
            $response->viewData('results')->first()->title,
        );
    }

    public function test_pages_counter_shows_all_matches_not_only_shown_ones(): void
    {
        // Показываем шесть, но считать обязаны все: при семи совпадениях
        // блок уверенно сообщал «5» — ровно столько, сколько выводил.
        for ($i = 1; $i <= 8; $i++) {
            Page::create([
                'title' => "Страница про лицензию {$i}",
                'slug' => "licenziya-{$i}",
                'content' => 'Текст',
                'published' => true,
            ]);
        }

        $response = $this->get(route('frontend.search', ['q' => 'лицензию']));

        $response->assertStatus(200);
        $this->assertSame(8, $response->viewData('pagesTotal'));
        $this->assertCount(6, $response->viewData('pages'));
        $this->assertSame(8, $response->viewData('total'));
    }
}
