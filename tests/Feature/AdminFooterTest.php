<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Visual\Models\Fragment;
use Tests\TestCase;

/**
 * 🧱 Подвал панели (layouts/admin/footer.blade.php).
 *
 * Тестов по нему не было. Что закреплено (всё это правилось 26.07.2026):
 *
 * — колонка «Быстрые ссылки» убрана, а вместе с ней мёртвая кнопка
 *   «Поддержка и помощь» → /admin/help: такого маршрута в проекте нет;
 * — версия проекта показывалась и в сайдбаре, и в подвале — осталась в одном
 *   месте;
 * — подпись «Обновлено: <время>» печатала момент отрисовки страницы, то есть
 *   всегда «сейчас», и не значила ничего;
 * — соцсети рисовались самодельными SVG вместо брендовых глифов.
 */
class AdminFooterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Объём медиатеки кешируется на час — в тестах кеш не должен
        // протекать между проверками
        Cache::forget('admin_footer_media_size');
    }

    public function test_footer_renders_with_version_from_config(): void
    {
        config(['app.version' => '9.9.9']);

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Nexum Core', false);
        $response->assertSee('9.9.9', false);
    }

    /**
     * Версии не должно быть в САЙДБАРЕ — там она дублировала подвал.
     *
     * Раньше тест требовал ровно одного вхождения на всю страницу. С тех пор
     * на главной появился блок «Обновление», где текущая версия — суть
     * содержимого, а не повтор: рядом с ней стоит доступная и состояние
     * проверки. Это осмысленное второе место, а не та беда, ради которой
     * тест писался, поэтому проверка сужена до исходной: сайдбар молчит,
     * подвал показывает версию один раз.
     */
    public function test_version_is_not_duplicated_in_the_sidebar(): void
    {
        config(['app.version' => '9.9.9']);

        $html = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))->getContent();

        $sidebar = $this->between($html, '<aside', '</aside>');
        $this->assertStringNotContainsString('9.9.9', $sidebar);
        $this->assertStringNotContainsString('Версия', $sidebar);

        $footer = $this->between($html, '<footer', '</footer>');
        $this->assertSame(
            1,
            substr_count($footer, '9.9.9'),
            'В подвале версия должна стоять ровно один раз.'
        );
    }

    public function test_dead_quick_links_are_gone(): void
    {
        $html = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))->getContent();

        // Маршрута admin.help в проекте нет — кнопка вела в никуда
        $this->assertStringNotContainsString('/admin/help', $html);
        $this->assertStringNotContainsString('Быстрые ссылки', $html);
        $this->assertStringNotContainsString('Поддержка и помощь', $html);
    }

    public function test_scroll_to_top_button_is_gone(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString("window.scrollTo({top:0", $html);
    }

    public function test_meaningless_updated_at_stamp_is_gone(): void
    {
        // Печаталось время отрисовки страницы — то есть всегда «сейчас»
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString('admin-footer-time', $html);
    }

    public function test_socials_use_real_brand_glyphs(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        // Telegram и WhatsApp убраны по решению владельца, на их месте MAX и
        // Rutube. У всех четырёх сервисов теперь собственные цветные SVG:
        // фирменных глифов MAX и Rutube нет ни в Font Awesome, ни в Simple
        // Icons, а ВК и GitHub владелец просил показывать в цвете.
        foreach (['ВКонтакте', 'MAX', 'Rutube', 'GitHub'] as $label) {
            $this->assertStringContainsString('aria-label="' . $label . '"', $html, "Нет ссылки {$label}");
        }

        foreach (['Telegram', 'WhatsApp'] as $gone) {
            $this->assertStringNotContainsString('aria-label="' . $gone . '"', $html);
        }

        // Ссылки наружу открываются в новой вкладке и без утечки referrer-окна
        $this->assertStringContainsString('rel="noopener"', $html);
    }

    public function test_technical_summary_shows_real_configuration(): void
    {
        // Значения берём из конфигурации как есть и НЕ подменяем её:
        // подмена database.default переключила бы соединение прямо посреди
        // прогона, и все следующие тесты класса упали бы на реальный pgsql
        $html = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))->getContent();

        $footer = $this->between($html, '<footer', '</footer>');

        $this->assertStringContainsString(PHP_VERSION, $footer);
        $this->assertStringContainsString(app()->version(), $footer);
        $this->assertStringContainsString(config('database.default'), $footer);
        $this->assertStringContainsString(config('queue.default'), $footer);
        $this->assertStringContainsString(config('app.timezone'), $footer);
        $this->assertStringContainsString(app()->environment(), $footer);
    }

    public function test_enabled_debug_mode_is_highlighted(): void
    {
        config(['app.debug' => true]);
        $on = $this->actingAs($this->admin())->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))->getContent();

        // Включённая отладка на боевом сервере — предупреждение, а не строка мелким шрифтом
        $this->assertStringContainsString('adm-f-chip is-warn', $on);
        $this->assertStringContainsString('включена', $on);

        config(['app.debug' => false]);
        $off = $this->actingAs($this->admin())->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString('adm-f-chip is-warn', $off);
        $this->assertStringContainsString('выключена', $off);
    }

    public function test_content_summary_counts_real_rows(): void
    {
        User::factory()->count(3)->create();

        $html = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))->getContent();

        $footer = $this->between($html, '<footer', '</footer>');

        // 3 созданных + сам администратор
        // Значение обёрнуто в <b>: моноширинный набор применяется только к
        // нему, а подпись рядом идёт обычным шрифтом.
        $this->assertMatchesRegularExpression('~Пользователей</span>\s*<b>\s*4\s*</b>~u', $footer);
    }

    public function test_fragment_zone_above_the_footer_still_works(): void
    {
        Fragment::create([
            'slug' => 'pamyatka',
            'title' => 'Памятка',
            'zone' => 'admin.footer',
            'type' => 'html',
            'html_cached' => '<p>Текст памятки редактора</p>',
            'schema' => [],
            'data' => [],
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Текст памятки редактора', false)
            ->assertSee('fragment-zone--admin-footer', false);
    }

    public function test_media_size_is_cached_between_requests(): void
    {
        // Обход каталога не должен повторяться на каждой странице панели
        $this->assertNull(Cache::get('admin_footer_media_size'));

        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertStatus(200);

        $this->assertTrue(Cache::has('admin_footer_media_size'));
    }

    public function test_footer_is_translated_with_the_interface(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))
            ->assertSee('Разработчик', false);

        $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.dashboard'))
            ->assertSee('Developer', false)
            ->assertDontSee('Разработчик', false);
    }

    /** Вырезает кусок HTML между двумя маркерами — чтобы искать в подвале, а не по всей странице. */
    private function between(string $html, string $from, string $to): string
    {
        $start = strpos($html, $from);
        $end = strpos($html, $to, $start === false ? 0 : $start);

        if ($start === false || $end === false) {
            return '';
        }

        return substr($html, $start, $end - $start);
    }
}
