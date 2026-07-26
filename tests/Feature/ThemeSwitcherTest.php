<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Visual\Models\Theme;
use Tests\TestCase;

/**
 * 🎚️ Переключатель темы в шапке сайта.
 *
 * Список берётся из таблицы visual_themes, а выбор посетителя живёт в сессии
 * и не меняет активную тему сайта. Раньше на этом месте была кнопка «Тёмная»
 * (localStorage + класс .dark), никак не связанная с модулем Темы.
 */
class ThemeSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private function theme(string $slug, string $title, string $primary, bool $default = false, string $bg = '#ffffff'): Theme
    {
        return Theme::create([
            'slug' => $slug,
            'title' => $title,
            'tokens' => [
                'colors' => [
                    'bg' => $bg, 'text' => '#111827',
                    'primary' => $primary, 'accent' => '#8b5cf6',
                    'header' => '#ffffff', 'footer' => '#ffffff',
                ],
                'radius' => ['md' => '12px'],
                'font' => ['base' => 'Inter, sans-serif'],
            ],
            'config' => ['icon_mode' => 'lucide'],
            'is_default' => $default,
        ]);
    }

    public function test_switcher_lists_themes_from_the_table(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);
        $this->theme('mint', 'Мята', '#0f766e');

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Индиго', false);
        $response->assertSee('Мята', false);
    }

    public function test_added_theme_appears_and_removed_disappears(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);

        $extra = $this->theme('probnaya', 'Пробная', '#ff00ff');
        $this->get('/')->assertSee('Пробная', false);

        // Кеш списка сбрасывается хуками модели — отдельного механизма нет
        $extra->delete();
        $this->get('/')->assertDontSee('Пробная', false);
    }

    public function test_visitor_choice_changes_page_but_not_active_theme(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);
        $this->theme('terracotta', 'Терракота', '#c2410c');

        $this->get(route('frontend.theme.set', 'terracotta'))->assertRedirect();

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('--color-primary: #c2410c', false);

        // Активная тема сайта осталась прежней — выбор личный
        $this->assertSame('indigo', Theme::where('is_default', true)->value('slug'));
        $this->assertSame('terracotta', session('site_theme'));
    }

    public function test_reset_returns_to_site_theme(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);
        $this->theme('terracotta', 'Терракота', '#c2410c');

        $this->get(route('frontend.theme.set', 'terracotta'));
        $this->get(route('frontend.theme.set', 'reset'))->assertRedirect();

        $this->assertNull(session('site_theme'));
        $this->get('/')->assertSee('--color-primary: #6366f1', false);
    }

    public function test_deleted_theme_in_session_falls_back_to_active(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);
        $doomed = $this->theme('doomed', 'Обречённая', '#123456');

        $this->get(route('frontend.theme.set', 'doomed'));
        $doomed->delete();

        // Страница не должна падать и остаётся с оформлением сайта
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('--color-primary: #6366f1', false);
    }

    public function test_unknown_slug_is_ignored(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);

        $this->get(route('frontend.theme.set', 'takoy-temy-net'))->assertRedirect();

        $this->assertNull(session('site_theme'));
    }

    public function test_guest_without_choice_gets_active_theme(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);
        $this->theme('mint', 'Мята', '#0f766e');

        $this->get('/')->assertSee('--color-primary: #6366f1', false);
    }

    public function test_dark_theme_chosen_by_visitor_marks_the_page(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);
        $this->theme('graphite', 'Графит', '#38bdf8', false, '#0f172a');

        $this->get(route('frontend.theme.set', 'graphite'));

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('fx-theme-dark', false)
            ->assertSee('--color-bg: #0f172a', false);
    }

    public function test_switcher_is_hidden_when_there_are_no_themes(): void
    {
        $this->assertSame(0, Theme::count());

        // Проверяем отсутствие ссылок переключателя, а не CSS-класса:
        // правило .hdr-theme-dot объявлено в стилях шапки всегда
        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('/theme/reset', false)
            ->assertDontSee(route('frontend.theme.set', 'indigo'), false);
    }
}
