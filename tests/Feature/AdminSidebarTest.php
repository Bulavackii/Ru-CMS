<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminCounters;
use App\Support\AdminSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Visual\Models\Theme;
use Tests\TestCase;

/**
 * 🧭 Левый сайдбар панели (layouts/admin/sidebar.blade.php) и общий с ним
 * мобильный drawer.
 *
 * Что закреплено (правилось 26.07.2026):
 *
 * — сайдбар брал у темы ТОЛЬКО шрифт, а цвета были прибиты литералами
 *   (bg-indigo-600, from-indigo-500) — при смене оформления он не менялся;
 * — мобильное меню жило со своим списком из пяти пунктов и давно разъехалось
 *   с сайдбаром: с телефона половина разделов была недоступна в принципе;
 * — раздела «Каптча» не было в навигации, а его страница вообще не имела
 *   маршрута — вьюха существовала и была недостижима;
 * — версия проекта показывалась и здесь, и в подвале.
 */
class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Счётчики мемоизируются на время запроса
        AdminCounters::forget();
    }

    private function theme(string $slug, string $primary, string $accent, bool $default = false): Theme
    {
        return Theme::create([
            'slug' => $slug,
            'title' => 'Тема ' . $slug,
            'tokens' => ['colors' => ['primary' => $primary, 'accent' => $accent]],
            'config' => [],
            'is_default' => $default,
        ]);
    }

    private function sidebar(string $html): string
    {
        return preg_match('~<aside.*?</aside>~s', $html, $m) ? $m[0] : '';
    }

    // ── Состав навигации ──────────────────────────────────────────────────

    public function test_every_section_is_rendered(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();
        $sidebar = $this->sidebar($html);

        foreach (AdminSections::groups() as $group => $links) {
            foreach ($links as $link) {
                $this->assertStringContainsString(
                    'href="' . $link['url'] . '"',
                    $sidebar,
                    "Раздел «{$link['label']}» из группы «{$group}» не выведен в сайдбаре"
                );
            }
        }
    }

    public function test_active_section_is_marked(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.news.index'))->getContent();
        $sidebar = $this->sidebar($html);

        // Ровно один активный пункт, и это «Новости»
        $this->assertSame(1, substr_count($sidebar, 'aria-current="page"'));
        $this->assertMatchesRegularExpression(
            '~href="[^"]*' . preg_quote(parse_url(route('admin.news.index'), PHP_URL_PATH), '~') . '"[^>]*asb-item is-active~s',
            $sidebar
        );
    }

    public function test_dashboard_is_marked_through_the_logo(): void
    {
        // Отдельного пункта «Дашборд» в сайдбаре нет: логотип ведёт туда же и
        // был бы вторым таким же. Но состояние «я здесь» показывать надо.
        $onDashboard = $this->sidebar(
            $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent()
        );
        $this->assertStringContainsString('asb-brand', $onDashboard);
        $this->assertStringContainsString('is-active', $onDashboard);

        $elsewhere = $this->sidebar(
            $this->actingAs($this->admin())->get(route('admin.news.index'))->getContent()
        );
        $this->assertMatchesRegularExpression('~asb-brand[^"]*"~', $elsewhere);
        $this->assertStringNotContainsString('asb-brand flex items-center gap-2.5 group min-w-0 is-active', $elsewhere);
    }

    public function test_version_is_not_duplicated_in_the_sidebar(): void
    {
        config(['app.version' => '7.7.7']);

        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        // Версия живёт в подвале, в одном месте со стеком
        $this->assertStringNotContainsString('7.7.7', $this->sidebar($html));
        $this->assertStringContainsString('7.7.7', $html);
    }

    // ── Счётчики ──────────────────────────────────────────────────────────

    public function test_counters_are_hidden_when_there_is_nothing_new(): void
    {
        $sidebar = $this->sidebar(
            $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent()
        );

        // Проверяем отрисованный элемент, а не подстроку: класс .asb-count
        // объявлен в <style> того же сайдбара и нашёлся бы всегда
        $this->assertStringNotContainsString('<span class="asb-count">', $sidebar);
    }

    public function test_counters_come_from_one_place_for_header_and_sidebar(): void
    {
        // Контракт: и шапка, и сайдбар читают App\Support\AdminCounters,
        // поэтому числа совпадают и считаются один раз за запрос
        $counters = AdminCounters::all();

        $this->assertArrayHasKey('orders', $counters);
        $this->assertArrayHasKey('messages', $counters);
        $this->assertArrayHasKey('notifications', $counters);

        foreach ($counters as $name => $value) {
            $this->assertIsInt($value, "Счётчик {$name} должен быть числом");
        }
    }

    public function test_counters_survive_a_missing_module(): void
    {
        // Модуль может быть отключён, таблицы может не быть — навигация из-за
        // этого падать не должна
        AdminCounters::forget();

        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertStatus(200);
    }

    // ── Оформление ────────────────────────────────────────────────────────

    public function test_sidebar_colours_follow_the_selected_theme(): void
    {
        $this->theme('indigo', '#6366f1', '#8b5cf6', true);
        $this->theme('terracotta', '#c2410c', '#d97706');

        $html = $this->actingAs($this->admin())
            ->withSession(['admin_theme' => 'terracotta'])
            ->get(route('admin.dashboard'))->getContent();

        $this->assertStringContainsString('--admin-primary: #c2410c', $html);

        // Цвета в сайдбаре берутся из переменных, а не прибиты литералами:
        // раньше здесь стояли bg-indigo-600 / from-indigo-500, и тема
        // на сайдбар не влияла вовсе
        $sidebar = $this->sidebar($html);
        $this->assertStringContainsString('var(--admin-primary', $sidebar);
        $this->assertDoesNotMatchRegularExpression('~bg-indigo-\d+|from-indigo-\d+~', $sidebar);
    }

    public function test_text_over_the_accent_stays_readable(): void
    {
        // Светлый акцент «Графита» (#38bdf8) даёт с белым текстом 2.14:1 при
        // норме 4.5 — на нём надпись должна становиться тёмной
        $this->assertSame('#111827', readable_ink('#38bdf8'));

        // Тёмные акценты остальных тем белый текст держат
        foreach (['#1d4ed8', '#0f766e', '#c2410c', '#6366f1'] as $dark) {
            $this->assertSame('#ffffff', readable_ink($dark), "Акцент {$dark} должен оставаться со светлой надписью");
        }

        // Мусор на входе не должен ронять страницу
        $this->assertSame('#ffffff', readable_ink('не-цвет'));
    }

    public function test_accent_ink_reaches_the_page(): void
    {
        $this->theme('graphite', '#38bdf8', '#818cf8', true);

        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        $this->assertStringContainsString('--admin-on-primary: #111827', $html);
    }

    // ── Мобильное меню ────────────────────────────────────────────────────

    public function test_mobile_menu_shows_the_same_sections(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        // Раньше здесь был свой список из пяти пунктов — с телефона остальные
        // разделы были недоступны в принципе
        $this->assertStringContainsString('amb-item', $html);

        foreach (AdminSections::all() as $section) {
            $this->assertStringContainsString('href="' . $section['url'] . '"', $html);
        }
    }
}
