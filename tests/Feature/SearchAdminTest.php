<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * 🔍 Поиск по административной части (/admin/search).
 *
 * До 25.07.2026 по этому модулю не было ни одного теста, а в нём жили три
 * молчаливых бага: раздел «Новости» искал по несуществующему template='news',
 * счётчики разделов обнулялись при выборе фильтра, а на Postgres поиск был
 * регистрозависимым.
 */
class SearchAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_search_page_opens_without_query(): void
    {
        // Подписи вьюхи теперь из словаря, а тестовое окружение стартует на en —
        // привязываем локаль, иначе проверяется английский текст.
        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.search.index'));

        $response->assertStatus(200);
        $response->assertViewIs('Search::admin.index');
        $response->assertSee('Начните поиск');
    }

    public function test_news_are_found_regardless_of_template(): void
    {
        // Реальные записи создаются с template = 'default', а раздел «Новости»
        // раньше искал строго template = 'news' и не находил вообще ничего.
        $news = News::create([
            'title' => 'Модульная архитектура',
            'content' => 'Текст новости про модули',
            'template' => 'default',
            'published' => true,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.search.index', ['q' => 'Модульная']));

        $response->assertStatus(200);
        // Совпадение в выдаче обёрнуто в <mark>, поэтому заголовок целиком
        // непрерывной строкой не встречается — проверяем неподсвеченный хвост
        $response->assertSee('архитектура', false);

        $sections = $response->viewData('sections');
        $this->assertSame(1, $sections['news']['count']);
        $this->assertSame($news->title, $sections['news']['items'][0]['title']);
    }

    public function test_pages_are_searchable_and_linked(): void
    {
        $page = Page::create([
            'title' => 'О проекте',
            'slug' => 'o-proekte',
            'content' => 'Описание проекта',
            'published' => true,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.search.index', ['q' => 'проект']));

        $response->assertStatus(200);
        // Результат должен вести на форму правки, а не быть просто текстом
        $response->assertSee(route('admin.pages.edit', $page), false);
    }

    public function test_counts_stay_visible_when_filter_selected(): void
    {
        News::create([
            'title' => 'Тестовая новость',
            'content' => 'Содержимое',
            'template' => 'default',
            'published' => true,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.search.index', ['q' => 'Тестовая', 'filter' => 'users']));

        $response->assertStatus(200);

        $sections = $response->viewData('sections');

        // Раздел скрыт фильтром, но его счётчик обязан остаться — иначе чипы
        // показывали бы нули везде, кроме выбранного раздела.
        $this->assertFalse($sections['news']['visible']);
        $this->assertSame(1, $sections['news']['count']);
        $this->assertCount(0, $sections['news']['items']);
    }

    public function test_search_is_case_insensitive(): void
    {
        // Тесты идут на SQLite, где LIKE регистронезависим только для латиницы,
        // поэтому проверяем именно её. На боевом Postgres регистр кириллицы
        // покрывает ILIKE — его подставляет search_like() (app/helpers.php).
        News::create([
            'title' => 'Laravel Modules',
            'content' => 'Текст',
            'template' => 'default',
            'published' => true,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.search.index', ['q' => 'LARAVEL']));

        $response->assertStatus(200);
        $this->assertSame(1, $response->viewData('sections')['news']['count']);
    }

    public function test_unknown_filter_is_rejected(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.search.index', ['q' => 'тест', 'filter' => 'пропало']));

        $response->assertSessionHasErrors('filter');
    }
}
