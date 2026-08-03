<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Visual\Console\Commands\SeedDefaultFragmentsCommand;
use Modules\Visual\Models\Fragment;
use Modules\Visual\Models\Revision;
use Modules\Visual\Support\FragmentRenderer;
use Tests\TestCase;

/**
 * 🧩 Модуль «Фрагменты» (/admin/visual/fragments).
 *
 * По модулю не было ни одного теста. До 26.07.2026 введённое в редакторе
 * содержимое затиралось заглушкой при каждом сохранении, страница истории
 * версий отдавала 500, а сами фрагменты выводились лишь в layouts/app.blade.php
 * — лейауте трёх второстепенных вьюх.
 */
class FragmentsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function fragment(array $overrides = []): Fragment
    {
        return Fragment::create(array_merge([
            'slug' => 'test-fragment',
            'title' => 'Тестовый фрагмент',
            'zone' => 'frontend.header',
            'type' => 'html',
            'html_cached' => '<p>Содержимое фрагмента</p>',
            'schema' => [],
            'data' => [],
            'is_active' => true,
        ], $overrides));
    }

    // ── Сидер ─────────────────────────────────────────────────────────────

    public function test_seeder_creates_enabled_fragments(): void
    {
        SeedDefaultFragmentsCommand::seed(false);

        $this->assertSame(6, Fragment::count());
        // Включены намеренно: новый администратор должен увидеть блоки на
        // страницах и понять, что это редактируемые фрагменты
        $this->assertSame(6, Fragment::where('is_active', true)->count());

        foreach (Fragment::all() as $fragment) {
            $this->assertNotEmpty($fragment->zone, "У фрагмента {$fragment->slug} нет зоны");
            $this->assertNotEmpty($fragment->html_cached, "У фрагмента {$fragment->slug} пустое содержимое");
        }
    }

    public function test_seeder_is_idempotent_and_keeps_disabled_state(): void
    {
        SeedDefaultFragmentsCommand::seed(false);

        // Выключенный владельцем блок повторная установка не включает обратно
        $fragment = Fragment::where('slug', 'frontend-topbar')->firstOrFail();
        $fragment->update(['is_active' => false]);

        SeedDefaultFragmentsCommand::seed(false);

        $this->assertSame(6, Fragment::count());
        $this->assertFalse($fragment->fresh()->is_active);
    }

    public function test_seeded_fragments_are_visible_on_the_site_and_in_admin(): void
    {
        SeedDefaultFragmentsCommand::seed(false);

        // Сайт: блоки выглядят как обычные элементы оформления
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Работаем ежедневно', false)
            ->assertSee('Остались вопросы', false);

        // Панель: блок прямо объясняет, что это фрагмент и где он правится
        $this->actingAs($this->admin())
            ->get(route('admin.visual.fragments.index'))
            ->assertStatus(200)
            ->assertSee('Первые шаги', false)
            ->assertSee('Памятка редактора', false);
    }

    // ── Вывод в зонах ─────────────────────────────────────────────────────

    public function test_active_fragment_is_rendered_in_its_zone(): void
    {
        $this->fragment([
            'zone' => 'frontend.topbar',
            'slug' => 'promo',
            'html_cached' => '<p>Объявление для посетителей</p>',
        ]);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Объявление для посетителей', false);
    }

    public function test_disabled_fragment_changes_nothing(): void
    {
        $this->fragment([
            'zone' => 'frontend.topbar',
            'slug' => 'promo',
            'html_cached' => '<p>Скрытое объявление</p>',
            'is_active' => false,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Скрытое объявление', false);
        $response->assertDontSee('fragment-zone', false);
    }

    public function test_empty_fragment_does_not_add_markup(): void
    {
        // Пустой фрагмент раньше выводил заглушку с заголовком
        $this->fragment(['zone' => 'frontend.footer', 'slug' => 'pusto', 'html_cached' => '']);

        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('fragment-zone', false);
    }

    public function test_renderer_returns_null_when_nothing_to_show(): void
    {
        // Контракт: null вместо HTML-комментария, по которому раньше искали подстроку
        $this->assertNull(FragmentRenderer::render(['slug' => 'nesushchestvuyushchiy']));
        $this->assertNull(FragmentRenderer::render(['zone' => 'frontend.header']));
        $this->assertNull(FragmentRenderer::render([]));
    }

    public function test_zone_cache_is_dropped_on_change(): void
    {
        $fragment = $this->fragment([
            'zone' => 'frontend.topbar',
            'slug' => 'promo',
            'html_cached' => '<p>Первая версия</p>',
        ]);

        $this->get('/')->assertSee('Первая версия', false);

        $fragment->update(['html_cached' => '<p>Вторая версия</p>']);

        $this->get('/')
            ->assertSee('Вторая версия', false)
            ->assertDontSee('Первая версия', false);
    }

    // ── Админка ───────────────────────────────────────────────────────────

    public function test_admin_pages_open(): void
    {
        $admin = $this->admin();
        $fragment = $this->fragment();

        $this->actingAs($admin)->get(route('admin.visual.fragments.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.visual.fragments.create'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.visual.fragments.edit', $fragment))->assertStatus(200);
        // Страница истории версий раньше падала: вьюхи не существовало
        $this->actingAs($admin)->get(route('admin.visual.fragments.history', $fragment))->assertStatus(200);
    }

    public function test_content_from_the_form_is_kept(): void
    {
        // renderToCache() затирал введённое содержимое заглушкой с заголовком
        $this->actingAs($this->admin())->post(route('admin.visual.fragments.store'), [
            'title' => 'Свой блок',
            'slug' => 'svoy-blok',
            'zone' => 'frontend.footer',
            'html_cached' => '<p>Мой текст</p>',
            'is_active' => '1',
        ])->assertRedirect();

        $fragment = Fragment::where('slug', 'svoy-blok')->firstOrFail();
        $this->assertSame('<p>Мой текст</p>', $fragment->html_cached);

        $this->actingAs($this->admin())->put(route('admin.visual.fragments.update', $fragment), [
            'title' => 'Свой блок',
            'slug' => 'svoy-blok',
            'zone' => 'frontend.footer',
            'html_cached' => '<p>Изменённый текст</p>',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame('<p>Изменённый текст</p>', $fragment->fresh()->html_cached);
    }

    public function test_new_zones_are_accepted(): void
    {
        $this->actingAs($this->admin())->post(route('admin.visual.fragments.store'), [
            'title' => 'Панельный блок',
            'slug' => 'panelnyy-blok',
            'zone' => 'admin.footer',
            'html_cached' => '<p>Служебное</p>',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame('admin.footer', Fragment::where('slug', 'panelnyy-blok')->value('zone'));
    }

    public function test_revision_is_saved_and_can_be_reverted(): void
    {
        $admin = $this->admin();
        $fragment = $this->fragment(['html_cached' => '<p>Версия 1</p>']);

        $this->actingAs($admin)->put(route('admin.visual.fragments.update', $fragment), [
            'title' => 'Тестовый фрагмент',
            'slug' => $fragment->slug,
            'zone' => $fragment->zone,
            'html_cached' => '<p>Версия 2</p>',
            'is_active' => '1',
        ]);

        $revision = Revision::where('target_type', Fragment::class)
            ->where('target_id', $fragment->id)
            ->latest()
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.visual.fragments.revert', [$fragment, $revision->id]))
            ->assertRedirect();

        $this->assertNotNull($fragment->fresh());
    }

    public function test_duplicate_creates_disabled_copy(): void
    {
        $fragment = $this->fragment();

        $this->actingAs($this->admin())
            ->post(route('admin.visual.fragments.duplicate', $fragment))
            ->assertRedirect();

        $copy = Fragment::where('id', '!=', $fragment->id)->firstOrFail();
        $this->assertFalse($copy->is_active);
        $this->assertSame($fragment->html_cached, $copy->html_cached);
    }

    public function test_bulk_toggle_skips_system_fragments(): void
    {
        $admin = $this->admin();
        $system = $this->fragment(['slug' => 'site-header', 'zone' => 'header', 'is_active' => false]);
        $usual = $this->fragment(['slug' => 'obychnyy', 'is_active' => false]);

        $this->actingAs($admin)->post(route('admin.visual.fragments.bulkToggle'), [
            'ids' => [$system->id, $usual->id],
            'action' => 'enable',
        ])->assertRedirect();

        $this->assertTrue($usual->fresh()->is_active);
        $this->assertFalse($system->fresh()->is_active, 'Системный фрагмент не должен переключаться массово');
    }

    public function test_styles_are_moved_out_of_content_on_save(): void
    {
        // Стили обязаны жить в css_inline: это поле не переводится, поэтому
        // блок выглядит одинаково на всех языках. Если оставить <style> внутри
        // содержимого, перевод получит только разметку и потеряет оформление.
        $this->actingAs($this->admin())->post(route('admin.visual.fragments.store'), [
            'title' => 'Промо',
            'slug' => 'promo-block',
            'zone' => 'frontend.topbar',
            'html_cached' => '<div class="promo">Скидка</div><style>.promo{color:red}</style>',
            'is_active' => '1',
        ])->assertRedirect();

        $fragment = Fragment::where('slug', 'promo-block')->firstOrFail();

        $this->assertStringNotContainsString('<style', $fragment->html_cached);
        $this->assertStringContainsString('.promo{color:red}', $fragment->css_inline);
    }

    public function test_translated_fragment_keeps_its_styles(): void
    {
        $fragment = $this->fragment([
            'zone' => 'frontend.topbar',
            'slug' => 'promo',
            'html_cached' => '<div class="promo">Скидка</div>',
            'css_inline' => '.promo{color:red}',
        ]);
        $fragment->saveTranslations(['en' => ['html_cached' => '<div class="promo">Discount</div>']]);

        $this->withSession(['app_locale' => 'en', 'locale' => 'en'])
            ->get('/')
            ->assertStatus(200)
            ->assertSee('Discount', false)
            ->assertSee('.promo{color:red}', false);
    }

    public function test_search_and_zone_filters_work_on_server(): void
    {
        $this->fragment(['slug' => 'pervyy', 'title' => 'Полоса объявления', 'zone' => 'frontend.topbar']);
        $this->fragment(['slug' => 'vtoroy', 'title' => 'Сноска в подвале', 'zone' => 'frontend.footer']);

        $byZone = $this->actingAs($this->admin())
            ->get(route('admin.visual.fragments.index', ['zone' => 'frontend.footer']));
        $this->assertCount(1, $byZone->viewData('fragments'));

        $bySearch = $this->actingAs($this->admin())
            ->get(route('admin.visual.fragments.index', ['search' => 'Полоса']));
        $this->assertCount(1, $bySearch->viewData('fragments'));
    }
}
