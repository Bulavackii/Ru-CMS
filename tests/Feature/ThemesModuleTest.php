<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Visual\Console\Commands\SeedDefaultThemesCommand;
use Modules\Visual\Models\Theme;
use Tests\TestCase;

/**
 * 🎨 Модуль «Темы» (/admin/visual/themes).
 *
 * По модулю не было ни одного теста. До 26.07.2026 таблица visual_themes была
 * пустой после установки, а дизайн-слой сайта и панели игнорировал токены темы:
 * акценты были прибиты литералами, и смена темы ничего не меняла.
 */
class ThemesModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function theme(array $overrides = []): Theme
    {
        return Theme::create(array_merge([
            'slug' => 'test-theme',
            'title' => 'Тестовая',
            'tokens' => [
                'colors' => [
                    'bg' => '#ffffff', 'text' => '#111827',
                    'primary' => '#ff0000', 'accent' => '#00ff00',
                    'header' => '#ffffff', 'footer' => '#ffffff',
                ],
                'radius' => ['md' => '8px'],
                'font' => ['base' => 'Inter, sans-serif'],
            ],
            'config' => ['icon_mode' => 'lucide'],
            'is_default' => false,
        ], $overrides));
    }

    // ── Сидер ─────────────────────────────────────────────────────────────

    public function test_seeder_creates_all_themes_with_one_active(): void
    {
        SeedDefaultThemesCommand::seed(false);

        // Число берём из самого описания набора, а не прибиваем: тем стало
        // одиннадцать, и с каждой новой пришлось бы править цифру в тесте.
        $expected = count(SeedDefaultThemesCommand::definitions());

        $this->assertGreaterThanOrEqual(5, $expected, 'Набор тем не должен усыхать.');
        $this->assertSame($expected, Theme::count());
        $this->assertSame(1, Theme::where('is_default', true)->count());
        $this->assertSame(
            SeedDefaultThemesCommand::DEFAULT_SLUG,
            Theme::where('is_default', true)->value('slug')
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        SeedDefaultThemesCommand::seed(false);
        SeedDefaultThemesCommand::seed(false);

        $this->assertSame(count(SeedDefaultThemesCommand::definitions()), Theme::count());
    }

    public function test_seeder_does_not_override_user_choice(): void
    {
        // Пользователь выбрал свою тему — повторная установка не должна её сбить
        $own = $this->theme(['is_default' => true]);

        SeedDefaultThemesCommand::seed(false);

        $this->assertTrue($own->fresh()->is_default);
        $this->assertSame(1, Theme::where('is_default', true)->count());
    }

    public function test_every_default_theme_fills_tokens_read_by_the_site(): void
    {
        SeedDefaultThemesCommand::seed(false);

        foreach (Theme::all() as $theme) {
            foreach (['bg', 'text', 'primary', 'accent', 'header', 'footer'] as $color) {
                $this->assertNotEmpty(
                    data_get($theme->tokens, "colors.{$color}"),
                    "Тема {$theme->slug}: не заполнен цвет {$color}"
                );
            }
            $this->assertNotEmpty(data_get($theme->tokens, 'radius.md'));
            $this->assertNotEmpty(data_get($theme->tokens, 'font.base'));
            $this->assertNotEmpty(data_get($theme->config, 'icon_mode'));
        }
    }

    // ── Админка ───────────────────────────────────────────────────────────

    public function test_admin_pages_open(): void
    {
        $admin = $this->admin();
        $theme = $this->theme();

        $this->actingAs($admin)->get(route('admin.visual.themes.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.visual.themes.create'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.visual.themes.edit', $theme))->assertStatus(200);
    }

    public function test_store_and_update_keep_tokens(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.visual.themes.store'), [
            'title' => 'Новая',
            'slug' => 'novaya',
            'tokens' => ['colors' => ['primary' => '#123456']],
        ])->assertRedirect();

        $theme = Theme::where('slug', 'novaya')->firstOrFail();
        $this->assertSame('#123456', data_get($theme->tokens, 'colors.primary'));

        // Частичное обновление не должно затирать остальные токены
        $this->actingAs($admin)->put(route('admin.visual.themes.update', $theme), [
            'title' => 'Новая',
            'slug' => 'novaya',
            'tokens' => ['colors' => ['accent' => '#abcdef']],
        ])->assertRedirect();

        $theme->refresh();
        $this->assertSame('#123456', data_get($theme->tokens, 'colors.primary'));
        $this->assertSame('#abcdef', data_get($theme->tokens, 'colors.accent'));
    }

    public function test_apply_switches_active_theme_and_resets_cache(): void
    {
        $admin = $this->admin();
        $first = $this->theme(['slug' => 'first', 'is_default' => true]);
        $second = $this->theme(['slug' => 'second']);

        // Прогреваем кеш активной темы
        Theme::getActive();

        $this->actingAs($admin)->patch(route('admin.visual.themes.apply', $second))->assertRedirect();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame('second', Theme::getActive()->slug);

        // Ключа active_theme_id больше нет намеренно: он был ВТОРЫМ
        // источником правды рядом с колонкой is_default и, записанный через
        // forever, перебивал базу. Проверка на его наличие стояла здесь же —
        // тест закреплял ровно тот механизм, который ломал применение темы.
        $this->assertNull(
            Cache::get('active_theme_id'),
            'Второй источник правды об активной теме заводить нельзя.'
        );
    }

    public function test_active_theme_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $theme = $this->theme(['is_default' => true]);

        $this->actingAs($admin)->delete(route('admin.visual.themes.destroy', $theme));

        $this->assertNotNull(Theme::find($theme->id));
    }

    // ── Влияние на сайт ───────────────────────────────────────────────────

    public function test_active_theme_tokens_reach_the_site(): void
    {
        $this->theme(['is_default' => true]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('--color-primary: #ff0000', false);
        $response->assertSee('--radius-md: 8px', false);
        // Дизайн-слой .fx-* должен брать акцент из темы, а не из литерала
        $response->assertSee('--fx-a: var(--color-primary', false);
    }

    public function test_dark_theme_marks_the_page(): void
    {
        // У тёмных тем светлые «стеклянные» карточки нечитаемы, поэтому
        // лейаут помечает страницу отдельным классом
        $this->theme([
            'slug' => 'dark-one',
            'is_default' => true,
            'tokens' => [
                'colors' => ['bg' => '#0f172a', 'text' => '#e2e8f0', 'primary' => '#38bdf8', 'accent' => '#818cf8'],
                'radius' => ['md' => '10px'],
                'font' => ['base' => 'Inter, sans-serif'],
            ],
        ]);

        $this->get('/')->assertStatus(200)->assertSee('fx-theme-dark', false);
    }

    public function test_site_without_theme_keeps_previous_look(): void
    {
        // Ни одной темы нет — страница должна выглядеть как до модуля
        $this->assertSame(0, Theme::count());

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('--color-primary: #2563eb', false); // прежний дефолт лейаута

        // Проверяем именно класс на <body>: строка fx-themed есть и в тексте
        // CSS-правила, поэтому простой assertDontSee по ней срабатывал бы зря
        preg_match('~<body class="([^"]*)"~', $response->getContent(), $m);
        $this->assertStringNotContainsString('fx-themed', $m[1] ?? '');
    }

    public function test_admin_accent_follows_the_theme(): void
    {
        $this->theme(['is_default' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.visual.themes.index'))
            ->assertStatus(200)
            ->assertSee('--admin-primary: #ff0000', false);
    }
}
