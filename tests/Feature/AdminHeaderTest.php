<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Categories\Models\Category;
use Modules\Visual\Models\Theme;
use Tests\TestCase;

/**
 * 🎛️ Верхняя панель админки (layouts/admin/header.blade.php).
 *
 * По шапке не было ни одного теста, хотя она рендерится на КАЖДОЙ странице
 * панели. Что тут закреплено (всё это до 26.07.2026 было сломано или отсутствовало):
 *
 * — поиск в шапке был кнопкой, стилизованной под поле: печатать в него было
 *   нельзя, и без JS он не работал вовсе;
 * — глобальный поиск не знал про разделы панели — «Темы» или «Локализацию»
 *   найти было негде;
 * — переключателя языка не было: на его месте стоял селект СТРАН с одной
 *   строкой в базе, то есть список с единственным вариантом;
 * — оформление панели не переключалось; кнопка-луна вешала класс .dark,
 *   которого в собранном tailwind.min.css почти ничем не поддержан.
 */
class AdminHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'name' => 'Денис']);
    }

    /** Тема с узнаваемым акцентом — по нему видно, что панель перекрасилась. */
    private function theme(string $slug, string $primary, bool $default = false): Theme
    {
        return Theme::create([
            'slug' => $slug,
            'title' => 'Тема ' . $slug,
            'tokens' => ['colors' => ['primary' => $primary, 'accent' => '#000000']],
            'config' => [],
            'is_default' => $default,
        ]);
    }

    // ── Каркас шапки ──────────────────────────────────────────────────────

    public function test_header_renders_for_admin(): void
    {
        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ags-input', false);         // поле поиска
        $response->assertSee('>На сайт</span>', false);   // переход на сайт
        $response->assertSee('Администратор', false);  // роль в блоке профиля
        $response->assertSee('Денис', false);          // имя в блоке профиля
    }

    public function test_header_does_not_fall_without_any_theme(): void
    {
        // Тем в базе нет вовсе: панель обязана открыться на дефолтном акценте,
        // а переключатель оформления — просто не показаться
        $this->assertSame(0, Theme::count());

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('--admin-primary: #6366f1', false);
    }

    public function test_go_to_site_link_opens_in_new_tab(): void
    {
        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'));

        // Ссылка на сайт — самостоятельное действие, а не переход внутри панели
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener"', false);
    }

    public function test_country_select_is_no_longer_in_the_header(): void
    {
        // Селект стран выглядел языковым переключателем («RU RU»), но менял
        // часовой пояс и форматы. Страны настраиваются в «Локализации».
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertDontSee('name="country_code"', false);
        $response->assertDontSee('country-switcher-form', false);
    }

    public function test_dark_mode_flag_is_cleaned_up_and_toggle_is_gone(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        // Кнопка-луна убрана, но флаг прежних пользователей надо снять —
        // иначе выключить «тёмную тему» стало бы нечем
        $response->assertSee("localStorage.removeItem('darkMode')", false);
        $response->assertDontSee("localStorage.setItem('darkMode'", false);
    }

    public function test_alpine_is_loaded_exactly_once(): void
    {
        // Второй экземпляр Alpine инициализировал разметку повторно:
        // каждый x-for в панели рендерился дважды
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        $this->assertSame(0, substr_count($html, 'alpine.min.js'), 'Alpine подключён вторым файлом поверх сборки');
        $this->assertStringContainsString('build/assets/', $html);
    }

    // ── Поиск ─────────────────────────────────────────────────────────────

    public function test_search_is_a_real_form_and_works_without_javascript(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        // Раньше это была <button>, открывавшая модалку: без JS — ничего.
        // Теперь обычная GET-форма на полноценную страницу поиска.
        $response->assertSee('action="' . route('admin.search.index') . '"', false);
        $response->assertSee('name="q"', false);
        $response->assertSee('type="search"', false);
    }

    public function test_global_search_finds_admin_sections(): void
    {
        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->getJson(route('admin.search.global', ['q' => 'оформление']));

        $response->assertStatus(200);

        $sections = collect($response->json('results'))->where('type', 'Раздел');

        // «Оформление» — синоним раздела «Темы»: искать надо и по смыслу,
        // а не только по точному названию
        $this->assertTrue($sections->contains('title', 'Темы'));
    }

    public function test_exact_section_is_not_pushed_out_by_partial_matches(): void
    {
        // Регресс: раньше в поиск попадало название ГРУППЫ, и запрос «тем»
        // вытаскивал половину разделов через «Сис-ТЕМ-а», занимал весь лимит
        // и выталкивал настоящие «Темы» из выдачи
        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->getJson(route('admin.search.global', ['q' => 'тем']));

        $first = $response->json('results.0');

        $this->assertSame('Раздел', $first['type']);
        $this->assertSame('Темы', $first['title']);
    }

    public function test_global_search_finds_content_and_links_to_its_card(): void
    {
        $category = Category::create(['title' => 'Путешествия', 'slug' => 'puteshestviya']);
        $user = User::factory()->create(['name' => 'Путешественник', 'email' => 'traveler@example.com']);

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->getJson(route('admin.search.global', ['q' => 'Путешес']));

        $results = collect($response->json('results'));

        $found = $results->firstWhere('type', 'Категория');
        $this->assertNotNull($found);
        $this->assertSame(route('admin.categories.edit', $category->id), $found['url']);

        // Пользователь раньше вёл на список с ?search= — «найди сам ещё раз»
        $foundUser = $results->firstWhere('type', 'Пользователь');
        $this->assertNotNull($foundUser);
        $this->assertSame(route('admin.users.edit', $user->id), $foundUser['url']);
        $this->assertSame('traveler@example.com', $foundUser['subtitle']);
    }

    public function test_global_search_ignores_too_short_queries(): void
    {
        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.search.global', ['q' => 'т']));

        $response->assertStatus(200);
        $this->assertSame([], $response->json('results'));
    }

    public function test_global_search_is_closed_for_guests(): void
    {
        // JSON-запрос от гостя auth-middleware заворачивает 401-м, а не редиректом
        $this->getJson(route('admin.search.global', ['q' => 'тема']))->assertStatus(401);
        $this->get(route('admin.search.global', ['q' => 'тема']))->assertRedirect(route('login'));
    }

    // ── Разделы: один список на сайдбар, шапку и поиск ─────────────────────

    public function test_sections_list_is_shared_with_the_sidebar(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        foreach (AdminSections::all() as $section) {
            $this->assertStringContainsString(
                'href="' . $section['url'] . '"',
                $html,
                "Раздел «{$section['label']}» есть в списке, но не выведен в навигации"
            );
        }
    }

    public function test_header_has_no_breadcrumb_and_opens_the_site(): void
    {
        // Хлебной крошки «Панель /» нет с 26.07.2026: она называлась
        // «Панель», а вела на /admin/news. Подпись текущего раздела и кнопка
        // «Создать» убраны 11.08.2026: раздел назван заголовком страницы и
        // подсвечен в меню слева, а создание живёт в самих разделах — на
        // каждой странице списка своя кнопка «Добавить». Единственное
        // действие в левой части полосы — переход на сайт.
        $html = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.news.index'))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringNotContainsString('>Панель<', $html);
        $this->assertStringNotContainsString('ahd-section', $html);

        // Переход на сайт: новая вкладка и без утечки реферера.
        $this->assertMatchesRegularExpression(
            '~target="_blank" rel="noopener"\s+class="ahd-action ahd-action--primary~u',
            $html,
            'В шапке нет кнопки перехода на сайт'
        );
    }

    // ── Язык интерфейса ───────────────────────────────────────────────────

    public function test_header_offers_every_available_locale(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        foreach (available_locales() as $code) {
            $this->assertStringContainsString(
                route('frontend.locale.set', $code),
                $html,
                "В переключателе нет локали {$code}"
            );
        }
    }

    public function test_language_switch_changes_the_panel_language(): void
    {
        $admin = $this->admin();

        // Проверяем именно подпись кнопки, а не вхождение строки куда угодно:
        // в <style> шапки есть комментарии, где та же фраза упомянута текстом
        $this->actingAs($admin)->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'))
            ->assertSee('>На сайт</span>', false);

        // Переключатель кладёт выбор в сессию — как и на сайте
        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->get(route('frontend.locale.set', 'en'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.dashboard'))
            ->assertSee('>View site</span>', false)
            ->assertDontSee('>На сайт</span>', false);
    }

    // ── Оформление панели ─────────────────────────────────────────────────

    public function test_theme_switch_repaints_the_panel_accent(): void
    {
        $this->theme('indigo', '#6366f1', true);
        $this->theme('terracotta', '#c2410c');

        $admin = $this->admin();

        // По умолчанию — акцент активной темы сайта
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('--admin-primary: #6366f1', false);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->get(route('admin.theme.set', 'terracotta'))
            ->assertRedirect(route('admin.dashboard'));

        // Никакой сессии: выбор ушёл в базу, поэтому виден в любом запросе.
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('--admin-primary: #c2410c', false);
    }

    public function test_panel_shows_the_site_theme(): void
    {
        // Личного выбора темы больше нет: он жил в сессии и перекрывал
        // применённую тему сайта, из-за чего кнопка «Применить» в разделе
        // «Темы» выглядела нерабочей. Панель показывает то же оформление,
        // что и сайт.
        $this->theme('indigo', '#6366f1', true);
        $this->theme('terracotta', '#c2410c');

        // Ключ сессии оставлен намеренно: он больше ни на что не влияет, и
        // тест это фиксирует — панель показывает применённую тему сайта.
        $this->actingAs($this->admin())->withSession(['admin_theme' => 'terracotta'])
            ->get(route('admin.dashboard'))
            ->assertSee('--admin-primary: #6366f1', false);
    }

    /**
     * Главное свойство: применённая тема переживает перезаход и видна ВСЕМ.
     *
     * Прежний тест на этом месте проверял «сброс личного выбора» — понятия,
     * которого больше нет: выбор жил в сессии и умирал вместе с ней. Тест
     * переписан вместе с закрытием пробела, а не подогнан под новый ответ.
     */
    public function test_applied_theme_survives_a_fresh_session(): void
    {
        $this->theme('indigo', '#6366f1', true);
        $this->theme('mint', '#0d9488');

        $this->actingAs($this->admin())
            ->from(route('admin.dashboard'))
            ->get(route('admin.theme.set', 'mint'))
            ->assertRedirect(route('admin.dashboard'));

        // Полный разрыв: ни входа, ни сессии, ни кеша — как после перезахода.
        auth()->logout();
        session()->flush();
        \Illuminate\Support\Facades\Cache::flush();

        $this->assertSame(
            'mint',
            \Modules\Visual\Models\Theme::getActive()->slug,
            'Применённая тема обязана пережить перезаход: она хранится в базе.'
        );

        // И у обычного посетителя — то же оформление.
        $this->get('/')->assertOk();
        $this->assertTrue(
            \Modules\Visual\Models\Theme::where('slug', 'mint')->value('is_default'),
            'Тема применяется для всех, а не лично для администратора.'
        );
    }

    /**
     * Кеш не должен спорить с базой.
     *
     * Здесь и был корень: getActive() читал ключ active_theme_id ПЕРЕД базой,
     * а писался он через forever и сам не протухал. Стоило значениям
     * разойтись — и сайт показывал тему, которой не применяли.
     */
    public function test_stale_cache_key_does_not_override_the_database(): void
    {
        $this->theme('indigo', '#6366f1', true);
        $mint = $this->theme('mint', '#0d9488');

        \Modules\Visual\Models\Theme::apply($mint);

        // Подкладываем ключ прежней схемы, указывающий на ДРУГУЮ тему.
        \Illuminate\Support\Facades\Cache::forever(
            'active_theme_id',
            \Modules\Visual\Models\Theme::where('slug', 'indigo')->value('id')
        );
        \Illuminate\Support\Facades\Cache::forget('active_theme');

        $this->assertSame(
            'mint',
            \Modules\Visual\Models\Theme::getActive()->slug,
            'Источник правды — колонка is_default, а не ключ в кеше.'
        );
    }

    public function test_theme_switch_ignores_an_unknown_slug(): void
    {
        $this->theme('indigo', '#6366f1', true);

        $this->actingAs($this->admin())
            ->from(route('admin.dashboard'))
            ->get(route('admin.theme.set', 'nesushchestvuyushchaya'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(
            'indigo',
            \Modules\Visual\Models\Theme::getActive()->slug,
            'Неизвестный слаг не должен менять применённую тему.'
        );
    }

    public function test_theme_switch_is_closed_for_guests(): void
    {
        $this->get(route('admin.theme.set', 'indigo'))->assertStatus(302);
        $this->assertGuest();
    }

    // ── Профиль ───────────────────────────────────────────────────────────

    public function test_profile_menu_collects_account_actions(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.dashboard'));

        $response->assertSee(route('admin.account.settings'), false);
        $response->assertSee(route('admin.users.password.edit', $admin->id), false);
        $response->assertSee(route('logout'), false);
        // Страны и форматы переехали из шапки сюда
        $response->assertSee(route('admin.localization.index'), false);
    }

    public function test_profile_block_shows_initial_when_there_is_no_avatar(): void
    {
        // Колонки avatar в users нет вовсе — в блоке профиля показывается буква
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Ольга']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('ahd-user-ava', false);
        $response->assertSee('О', false);
    }
}
