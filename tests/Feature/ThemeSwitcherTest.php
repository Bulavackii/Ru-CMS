<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Visual\Models\Theme;
use Tests\TestCase;

/**
 * 🎨 Оформление сайта.
 *
 * Переключателя тем в шапке сайта больше нет, и личного выбора в сессии
 * тоже: оформление задаётся в панели («Темы» → «Применить») и применяется
 * сразу и к сайту, и к панели.
 *
 * Раньше выбор жил в сессии и ПЕРЕКРЫВАЛ применённую тему — из-за этого
 * кнопка «Применить» выглядела нерабочей: в базе тема менялась, а на
 * экране оставалось прежнее оформление.
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

    /** Применить тему так же, как это делает кнопка в разделе «Темы». */
    private function apply(Theme $theme): void
    {
        Theme::where('id', '!=', $theme->id)->update(['is_default' => false]);
        $theme->is_default = true;
        $theme->save();

        // Кеш обязателен: без него сайт продолжит отдавать прежнюю тему.
        Cache::forever('active_theme_id', $theme->id);
        Cache::forget('active_theme');
    }

    public function test_site_uses_the_applied_theme(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);
        $terracotta = $this->theme('terracotta', 'Терракота', '#c2410c');

        $this->get('/')->assertOk()->assertSee('--color-primary: #6366f1', false);

        $this->apply($terracotta);

        $this->get('/')->assertOk()->assertSee('--color-primary: #c2410c', false);
    }

    public function test_stale_session_choice_does_not_override_the_applied_theme(): void
    {
        // Ровно тот случай, из-за которого «Применить» выглядела нерабочей:
        // в браузере оставался старый выбор и перекрывал тему сайта.
        $indigo = $this->theme('indigo', 'Индиго', '#6366f1', true);
        $this->theme('terracotta', 'Терракота', '#c2410c');

        $this->apply($indigo);

        $this->withSession(['site_theme' => 'terracotta', 'admin_theme' => 'terracotta'])
            ->get('/')
            ->assertOk()
            ->assertSee('--color-primary: #6366f1', false);
    }

    public function test_switcher_is_gone_from_the_site_header(): void
    {
        $this->theme('indigo', 'Индиго', '#6366f1', true);

        $this->get('/')->assertOk()->assertDontSee('/theme/indigo', false);
    }

    public function test_deleted_theme_falls_back_to_the_active_one(): void
    {
        $indigo = $this->theme('indigo', 'Индиго', '#6366f1', true);
        $mint = $this->theme('mint', 'Мята', '#0d9488');

        $this->apply($indigo);
        $mint->delete();

        $this->get('/')->assertOk()->assertSee('--color-primary: #6366f1', false);
    }

    public function test_admin_panel_follows_the_same_theme(): void
    {
        $terracotta = $this->theme('terracotta', 'Терракота', '#c2410c');
        $this->apply($terracotta);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('--admin-primary: #c2410c', false);
    }
}
