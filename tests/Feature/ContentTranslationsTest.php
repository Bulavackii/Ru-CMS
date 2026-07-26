<?php

namespace Tests\Feature;

use App\Models\ContentTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Localization\Console\Commands\SeedContentTranslationsCommand;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * 🌐 Переводы контента из базы.
 *
 * Словари resources/lang переводят интерфейс, но заголовки новостей, страниц и
 * пунктов меню лежат в БД — до 26.07.2026 их перевода не существовало вовсе,
 * и при выборе языка контент оставался русским.
 */
class ContentTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private function news(array $overrides = []): News
    {
        return News::create(array_merge([
            'title' => 'Модульная архитектура',
            'content' => '<p>Русский текст</p>',
            'slug' => 'modular-architecture',
            'template' => 'default',
            'published' => true,
        ], $overrides));
    }

    // ── Трейт ─────────────────────────────────────────────────────────────

    public function test_translation_is_returned_for_current_locale(): void
    {
        $news = $this->news();
        $news->saveTranslations(['en' => ['title' => 'Modular architecture']]);

        app()->setLocale('en');
        $this->assertSame('Modular architecture', $news->t('title'));

        app()->setLocale('ru');
        $this->assertSame('Модульная архитектура', $news->t('title'));
    }

    public function test_missing_translation_falls_back_to_original(): void
    {
        // Пустых мест на сайте быть не должно
        $news = $this->news();
        $news->saveTranslations(['en' => ['title' => 'Modular architecture']]);

        app()->setLocale('de');
        $this->assertSame('Модульная архитектура', $news->t('title'));

        // Пустой перевод равносилен отсутствующему
        $news->saveTranslations(['fr' => ['title' => '   ']]);
        app()->setLocale('fr');
        $this->assertSame('Модульная архитектура', $news->fresh()->t('title'));
    }

    public function test_original_locale_does_not_depend_on_current_locale(): void
    {
        // config('app.locale') Laravel переписывает при setLocale, поэтому язык
        // оригинала берётся из отдельного ключа content_locale
        app()->setLocale('fr');

        $this->assertSame('ru', News::originalLocale());
    }

    public function test_empty_value_removes_the_translation(): void
    {
        $news = $this->news();
        $news->saveTranslations(['en' => ['title' => 'Modular architecture']]);
        $this->assertSame(['en'], $news->translatedLocales());

        $news->saveTranslations(['en' => ['title' => '']]);
        $this->assertSame([], $news->fresh()->translatedLocales());
    }

    public function test_only_translatable_fields_are_stored(): void
    {
        $news = $this->news();
        $news->saveTranslations(['en' => ['title' => 'Title', 'slug' => 'hacked-slug']]);

        $this->assertDatabaseHas('content_translations', ['locale' => 'en', 'field' => 'title']);
        $this->assertDatabaseMissing('content_translations', ['locale' => 'en', 'field' => 'slug']);
    }

    public function test_deleting_record_removes_its_translations(): void
    {
        $page = Page::create(['title' => 'О проекте', 'slug' => 'o-proekte', 'content' => 'Текст', 'published' => true]);
        $page->saveTranslations(['en' => ['title' => 'About']]);

        $this->assertSame(1, ContentTranslation::count());

        $page->delete();

        $this->assertSame(0, ContentTranslation::count());
    }

    // ── Вывод на сайте ────────────────────────────────────────────────────

    public function test_news_page_shows_translated_title(): void
    {
        $news = $this->news();
        $news->saveTranslations(['fr' => ['title' => 'Architecture modulaire', 'content' => '<p>Texte français</p>']]);

        $this->withSession(['app_locale' => 'fr', 'locale' => 'fr'])
            ->get('/news/modular-architecture')
            ->assertStatus(200)
            ->assertSee('Architecture modulaire', false)
            ->assertSee('Texte français', false);
    }

    public function test_menu_is_translated_on_the_site(): void
    {
        $menu = Menu::create(['title' => 'Главное меню', 'position' => 'header', 'active' => true]);
        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Главная',
            'type' => 'url',
            'url' => '/',
            'active' => true,
            'order' => 0,
        ]);
        $item->saveTranslations(['en' => ['title' => 'Home']]);

        $this->withSession(['app_locale' => 'en', 'locale' => 'en'])
            ->get('/')
            ->assertStatus(200)
            ->assertSee('Home', false);
    }

    public function test_russian_site_is_unchanged(): void
    {
        $news = $this->news();
        $news->saveTranslations(['en' => ['title' => 'Modular architecture']]);

        // Локаль задаём явно: в тестовой среде приложение стартует с en,
        // а проверяем мы именно поведение русской версии сайта
        $this->withSession(['app_locale' => 'ru', 'locale' => 'ru'])
            ->get('/news/modular-architecture')
            ->assertStatus(200)
            ->assertSee('Модульная архитектура', false)
            ->assertDontSee('Modular architecture', false);
    }

    // ── Ввод в админке ────────────────────────────────────────────────────

    public function test_admin_form_shows_translations_block(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $news = $this->news();

        $this->actingAs($admin)
            ->get(route('admin.news.edit', $news))
            ->assertStatus(200)
            ->assertSee('Переводы', false);
    }

    // ── Демо-переводы ─────────────────────────────────────────────────────

    public function test_seeder_translates_demo_content(): void
    {
        $menu = Menu::create(['title' => 'Главное меню', 'position' => 'header', 'active' => true]);
        MenuItem::create(['menu_id' => $menu->id, 'title' => 'Главная', 'type' => 'url', 'url' => '/', 'active' => true, 'order' => 0]);
        $this->news();

        SeedContentTranslationsCommand::seed();

        app()->setLocale('en');
        $this->assertSame('Home', MenuItem::first()->t('title'));
        $this->assertSame('Modular architecture', News::first()->t('title'));
    }

    public function test_seeder_does_not_overwrite_manual_translations(): void
    {
        $this->news();
        News::first()->saveTranslations(['en' => ['title' => 'My own title']]);

        SeedContentTranslationsCommand::seed();

        app()->setLocale('en');
        $this->assertSame('My own title', News::first()->t('title'));
    }
}
