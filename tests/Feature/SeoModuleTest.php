<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;
use Modules\Seo\Models\SeoPage;
use Tests\TestCase;

/**
 * 🔎 Модуль SEO (/admin/seo/pages).
 *
 * По модулю не было ни одного теста, а в нём жили молчаливые баги: формы
 * создания и правки отдавали 500, поиск и фильтры падали, блокировка ломалась
 * об отсутствующую колонку, адреса страниц в sitemap вели на 404, а сами
 * SEO-данные вообще не доходили до сайта.
 */
class SeoModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function seoPage(array $overrides = []): SeoPage
    {
        $page = new SeoPage();
        $page->slug = $overrides['slug'] ?? '/page/test';
        $page->fill(array_merge([
            'title' => 'Заголовок',
            'description' => 'Описание',
            'robots_index' => true,
            'robots_follow' => true,
        ], collect($overrides)->except('slug')->all()));
        $page->save();

        return $page;
    }

    // ── Доступ ────────────────────────────────────────────────────────────

    public function test_section_is_closed_for_non_admins(): void
    {
        // Раздел висел на middleware ['web','auth'] без 'admin': любой
        // авторизованный пользователь мог править и удалять SEO-записи
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('seo.pages.index'))->assertStatus(403);
    }

    // ── Список, поиск, фильтры ────────────────────────────────────────────

    public function test_index_opens(): void
    {
        $this->actingAs($this->admin())
            ->get(route('seo.pages.index'))
            ->assertStatus(200)
            ->assertViewIs('seo::admin.index');
    }

    public function test_search_does_not_crash_and_finds_record(): void
    {
        // scopeSearch в модели не существовало — поиск падал с BadMethodCallException
        $this->seoPage(['slug' => '/page/o-proekte', 'title' => 'О проекте']);
        $this->seoPage(['slug' => '/page/kontakty', 'title' => 'Контакты']);

        $response = $this->actingAs($this->admin())
            ->get(route('seo.pages.index', ['q' => 'проекте']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('items'));
    }

    public function test_filters_are_applied_on_server(): void
    {
        // Фильтры были клиентскими: JS прятал строки только текущей страницы
        $this->seoPage(['slug' => '/page/a', 'robots_index' => true]);
        $this->seoPage(['slug' => '/page/b', 'robots_index' => false]);

        $response = $this->actingAs($this->admin())
            ->get(route('seo.pages.index', ['index' => '0']));

        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertSame('/page/b', $items->first()->slug);
    }

    public function test_source_type_filter_works(): void
    {
        $this->seoPage(['slug' => '/news/a', 'source_type' => 'news', 'source_id' => 1]);
        $this->seoPage(['slug' => '/page/b', 'source_type' => 'page', 'source_id' => 1]);

        $response = $this->actingAs($this->admin())
            ->get(route('seo.pages.index', ['source_type' => 'news']));

        $this->assertCount(1, $response->viewData('items'));
    }

    // ── Формы ─────────────────────────────────────────────────────────────

    public function test_create_and_edit_forms_open(): void
    {
        // Обе вьюхи не компилировались: в placeholder JSON-LD стояло "@context",
        // а Blade считает @context своей директивой и ломал разметку — 500
        $page = $this->seoPage();

        $this->actingAs($this->admin())->get(route('seo.pages.create'))->assertStatus(200);
        $this->actingAs($this->admin())->get(route('seo.pages.edit', $page->id))->assertStatus(200);
    }

    public function test_store_saves_og_and_jsonld(): void
    {
        // Колонок og/jsonld не было в таблице: форма их собирала, а
        // filterColumns() молча выбрасывал
        $this->actingAs($this->admin())->post(route('seo.pages.store'), [
            'slug' => '/page/novaya',
            'title' => 'Заголовок',
            'description' => 'Описание',
            'og_title' => 'OG заголовок',
            'jsonld_raw' => '{"@type":"WebPage"}',
            'robots_index' => '1',
            'robots_follow' => '1',
        ])->assertRedirect(route('seo.pages.index'));

        $page = SeoPage::where('slug', '/page/novaya')->firstOrFail();
        $this->assertSame('OG заголовок', $page->og['og:title'] ?? null);
        $this->assertSame('WebPage', $page->jsonld['@type'] ?? null);
    }

    // ── Действия ──────────────────────────────────────────────────────────

    public function test_lock_and_unlock(): void
    {
        // lock/unlock писали updated_by, которого не было в таблице → 500
        $admin = $this->admin();
        $page = $this->seoPage();

        $this->actingAs($admin)->post(route('seo.pages.lock', $page->id))->assertRedirect();
        $this->assertTrue($page->fresh()->locked);

        $this->actingAs($admin)->post(route('seo.pages.unlock', $page->id))->assertRedirect();
        $this->assertFalse($page->fresh()->locked);
    }

    public function test_bulk_index_and_noindex(): void
    {
        $admin = $this->admin();
        $first = $this->seoPage(['slug' => '/page/one']);
        $second = $this->seoPage(['slug' => '/page/two']);
        $ids = [$first->id, $second->id];

        $this->actingAs($admin)->post(route('seo.pages.bulk'), ['action' => 'noindex', 'selected' => $ids]);
        $this->assertFalse($first->fresh()->robots_index);
        $this->assertFalse($second->fresh()->robots_index);

        $this->actingAs($admin)->post(route('seo.pages.bulk'), ['action' => 'index', 'selected' => $ids]);
        $this->assertTrue($first->fresh()->robots_index);
    }

    public function test_bulk_without_selection_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('seo.pages.bulk'), ['action' => 'delete'])
            ->assertSessionHasErrors('selected');
    }

    // ── Синхронизация ─────────────────────────────────────────────────────

    public function test_sync_creates_records_with_real_urls(): void
    {
        // Синхронизация писала страницам slug вида /{slug}, а страница живёт по
        // /page/{slug} — в sitemap.xml уезжали ссылки, отдающие 404
        $page = Page::create([
            'title' => 'О проекте',
            'slug' => 'o-proekte',
            'content' => 'Текст',
            'published' => true,
        ]);

        News::create([
            'title' => 'Новость',
            'slug' => 'novost',
            'content' => 'Текст',
            'template' => 'default',
            'published' => true,
        ]);

        $this->actingAs($this->admin())->post(route('seo.pages.sync'))->assertRedirect();

        $this->assertDatabaseHas('seo_pages', ['slug' => '/page/o-proekte']);
        $this->assertDatabaseHas('seo_pages', ['slug' => '/news/novost']);
        $this->assertDatabaseMissing('seo_pages', ['slug' => '/o-proekte']);

        // адрес из выдачи должен реально открываться
        $this->get(route('frontend.pages.show', $page->slug))->assertStatus(200);
    }

    // ── Влияние на сайт ───────────────────────────────────────────────────

    public function test_seo_record_is_applied_to_the_page(): void
    {
        // Раздел вообще не влиял на сайт: title/description брались из самой
        // страницы, robots был прибит как «index, follow», canonical — текущий URL
        Page::create([
            'title' => 'О проекте',
            'slug' => 'o-proekte',
            'content' => 'Текст',
            'published' => true,
        ]);

        $this->seoPage([
            'slug' => '/page/o-proekte',
            'title' => 'Заголовок из раздела SEO',
            'description' => 'Описание из раздела SEO',
            'robots_index' => false,
            'robots_follow' => false,
            'canonical' => 'https://example.com/canonical',
        ]);

        $response = $this->get('/page/o-proekte');

        $response->assertStatus(200);
        $response->assertSee('Заголовок из раздела SEO', false);
        $response->assertSee('noindex, nofollow', false);
        $response->assertSee('https://example.com/canonical', false);
    }

    public function test_page_without_seo_record_keeps_previous_behaviour(): void
    {
        // Страницы без SEO-записи должны вести себя как раньше
        Page::create([
            'title' => 'Без SEO',
            'slug' => 'bez-seo',
            'content' => 'Текст',
            'published' => true,
            'meta_title' => 'Мета-заголовок страницы',
        ]);

        $response = $this->get('/page/bez-seo');

        $response->assertStatus(200);
        $response->assertSee('Мета-заголовок страницы', false);
        $response->assertSee('index, follow', false);
    }

    public function test_jsonld_is_rendered_on_the_page(): void
    {
        Page::create([
            'title' => 'Со схемой',
            'slug' => 'so-shemoy',
            'content' => 'Текст',
            'published' => true,
        ]);

        $this->seoPage([
            'slug' => '/page/so-shemoy',
            'jsonld' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
        ]);

        $this->get('/page/so-shemoy')
            ->assertStatus(200)
            ->assertSee('application/ld+json', false);
    }
}
