<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🌍 Перевод панели: навигация и общие подписи.
 *
 * Переключатель языка в шапке существовал, но менял в панели почти ничего:
 * названия разделов, группы и синонимы для поиска были русскими литералами
 * прямо в App\Support\AdminSections, а подписи общих компонентов — в
 * разметке. Со стороны это выглядело как сломанный переключатель.
 *
 * Здесь закреплён первый транш: всё, что видно на КАЖДОЙ странице панели.
 * Разделы по-прежнему содержат много непереведённого текста внутри —
 * это следующие транши, см. CLAUDE.md.
 */
class AdminTranslationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function sidebar(string $html): string
    {
        return preg_match('~<aside.*?</aside>~s', $html, $m) ? $m[0] : '';
    }

    public function test_sidebar_switches_with_the_interface_language(): void
    {
        $admin = $this->admin();

        $russian = $this->sidebar(
            $this->actingAs($admin)->withSession(['app_locale' => 'ru'])
                ->get(route('admin.dashboard'))->getContent()
        );

        $this->assertStringContainsString('Новости', $russian);
        $this->assertStringContainsString('Контент', $russian);

        $english = $this->sidebar(
            $this->actingAs($admin)->withSession(['app_locale' => 'en'])
                ->get(route('admin.dashboard'))->getContent()
        );

        $this->assertStringContainsString('News', $english);
        $this->assertStringContainsString('Content', $english);
        $this->assertStringNotContainsString('Новости', $english);
        $this->assertStringNotContainsString('Контент', $english);
    }

    public function test_section_labels_are_translated_in_every_locale(): void
    {
        foreach (available_locales() as $locale) {
            app()->setLocale($locale);

            foreach (AdminSections::all() as $section) {
                $this->assertNotSame(
                    'admin.sections.' . $section['key'],
                    $section['label'],
                    "Для локали {$locale} нет перевода раздела {$section['key']}"
                );
                $this->assertNotSame('', trim($section['label']));
            }
        }
    }

    public function test_search_keywords_are_translated_too(): void
    {
        // Без перевода синонимов поиск разделов на других языках слепнет:
        // «appearance» не нашёл бы «Themes»
        app()->setLocale('en');
        $found = AdminSections::search('appearance');

        $this->assertNotEmpty($found, 'Поиск по английскому синониму ничего не нашёл');
        $this->assertSame('themes', $found[0]['key']);

        app()->setLocale('ru');
        $found = AdminSections::search('оформление');

        $this->assertNotEmpty($found);
        $this->assertSame('themes', $found[0]['key']);
    }

    public function test_search_result_types_are_translated(): void
    {
        $admin = $this->admin();

        $ru = $this->actingAs($admin)->withSession(['app_locale' => 'ru'])
            ->getJson(route('admin.search.global', ['q' => 'новост']))->json('results');

        $en = $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->getJson(route('admin.search.global', ['q' => 'news']))->json('results');

        $this->assertSame('Раздел', $ru[0]['type'] ?? null);
        $this->assertSame('Section', $en[0]['type'] ?? null);
    }

    public function test_breadcrumb_in_the_header_follows_the_language(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.news.index'))
            ->assertSee('News', false);
    }

    public function test_shared_components_use_the_dictionary(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.dashboard'))->getContent();

        // Центр уведомлений — на каждой странице
        $this->assertStringContainsString('Mark all as read', $html);
        $this->assertStringNotContainsString('Отметить все как прочитанные', $html);
    }

    public function test_dictionaries_have_the_same_keys_everywhere(): void
    {
        $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
            $keys = [];
            foreach ($items as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                $keys = array_merge($keys, is_array($value) ? $flatten($value, $path) : [$path]);
            }
            return $keys;
        };

        $reference = $flatten(require base_path('resources/lang/ru/admin.php'));
        sort($reference);

        foreach (available_locales() as $locale) {
            $file = base_path("resources/lang/{$locale}/admin.php");
            $this->assertFileExists($file);

            $keys = $flatten(require $file);
            sort($keys);

            $this->assertSame($reference, $keys, "Словарь admin.php локали {$locale} разошёлся с эталоном");
        }
    }

    public function test_news_and_pages_screens_are_translated(): void
    {
        // Транш 2: разделы, куда заходят каждый день. Проверяем именно
        // интерфейс — заголовки, подписи полей и кнопки, а не содержимое
        // из базы: названия новостей и категорий остаются как есть.
        $admin = $this->admin();

        $news = $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.news.create'))->getContent();

        $this->assertStringContainsString('Create article', $news);
        $this->assertStringContainsString('Publish immediately', $news);
        $this->assertStringNotContainsString('Создание новости', $news);
        $this->assertStringNotContainsString('Опубликовать сразу', $news);

        $pages = $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.pages.create'))->getContent();

        $this->assertStringContainsString('Save page', $pages);
        $this->assertStringNotContainsString('Сохранить страницу', $pages);
    }

    public function test_field_labels_and_hints_switch_too(): void
    {
        // Подписи и подсказки полей — самая массовая часть форм; без них
        // страница на другом языке остаётся наполовину русской
        $html = $this->actingAs($this->admin())->withSession(['app_locale' => 'en'])
            ->get(route('admin.news.create'))->getContent();

        $this->assertStringContainsString('Title', $html);
        $this->assertStringContainsString('Keywords', $html);
        $this->assertStringNotContainsString('Ключевые слова', $html);
    }

    public function test_menu_screens_are_translated(): void
    {
        // Транш 3. Проверяем конкретные подписи, а не «нет русского вообще»:
        // на странице есть <script> со своими строками, и подсчёт русских
        // фрагментов по всей разметке ловил бы куски JS, а не интерфейс.
        $admin = $this->admin();

        $en = $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.menus.create'))->getContent();

        $this->assertStringContainsString('Create menu', $en);
        $this->assertStringContainsString('Menu position', $en);
        $this->assertStringContainsString('Activate the menu', $en);
        $this->assertStringNotContainsString('Создать меню', $en);
        $this->assertStringNotContainsString('Позиция меню', $en);
        $this->assertStringNotContainsString('Активировать меню', $en);

        $ru = $this->actingAs($admin)->withSession(['app_locale' => 'ru'])
            ->get(route('admin.menus.create'))->getContent();

        $this->assertStringContainsString('Создать меню', $ru);
        $this->assertStringNotContainsString('Create menu', $ru);
    }

    public function test_item_counter_uses_plural_forms(): void
    {
        // Склонение было захардкожено по-русски (1 пункт / 2–4 пункта /
        // 5+ пунктов) прямо во вьюхе — на других языках форма была неверной
        app()->setLocale('ru');
        $this->assertSame('1 пункт', trans_choice('admin.menu.items_plural', 1));
        $this->assertSame('3 пункта', trans_choice('admin.menu.items_plural', 3));
        $this->assertSame('7 пунктов', trans_choice('admin.menu.items_plural', 7));

        app()->setLocale('en');
        $this->assertSame('1 item', trans_choice('admin.menu.items_plural', 1));
        $this->assertSame('7 items', trans_choice('admin.menu.items_plural', 7));
    }

    public function test_users_screens_are_translated(): void
    {
        // Транш 4. В разделе Пользователи остаются русскими только названия
        // ролей и их описания — это строки из таблицы roles, то есть данные.
        $admin = $this->admin();

        $en = $this->actingAs($admin)->withSession(['app_locale' => 'en'])
            ->get(route('admin.users.create'))->getContent();

        $this->assertStringContainsString('New user', $en);
        $this->assertStringContainsString('Repeat the password', $en);
        $this->assertStringNotContainsString('Новый пользователь', $en);
        $this->assertStringNotContainsString('Повторите пароль', $en);

        $ru = $this->actingAs($admin)->withSession(['app_locale' => 'ru'])
            ->get(route('admin.users.create'))->getContent();

        $this->assertStringContainsString('Новый пользователь', $ru);
        $this->assertStringNotContainsString('New user', $ru);
    }

    public function test_rights_counter_uses_plural_forms(): void
    {
        // Склонение «право/права/прав» было захардкожено во вьюхе с прямым
        // комментарием «trans_choice зависит от локали и на en дал бы неверную
        // форму». Это ровно то поведение, которое нужно: форма обязана
        // следовать языку.
        app()->setLocale('ru');
        $this->assertSame('1 право', trans_choice('admin.users.rights_plural', 1));
        $this->assertSame('3 права', trans_choice('admin.users.rights_plural', 3));
        $this->assertSame('17 прав', trans_choice('admin.users.rights_plural', 17));

        app()->setLocale('en');
        $this->assertSame('1 permission', trans_choice('admin.users.rights_plural', 1));
        $this->assertSame('17 permissions', trans_choice('admin.users.rights_plural', 17));
    }

    public function test_technical_identifiers_are_not_translated(): void
    {
        // Названия драйверов, слаги и коды локалей переводить нельзя —
        // это идентификаторы, а не подписи
        foreach (['en'] as $locale) {
            app()->setLocale($locale);

            $this->assertSame('SEO', __('admin.sections.seo'));
            $this->assertSame('Slideshow', __('admin.sections.slideshow'));
        }
    }

    public function test_admin_controllers_have_no_hardcoded_flash_messages(): void
    {
        // Флеш-сообщения видны пользователю после каждого действия в панели.
        // Захардкоженный русский литерал в ->with('success'|'error', …)
        // означает, что на другом языке сообщение не переведётся.
        $files = array_merge(
            glob(base_path('app/Http/Controllers/Admin/*.php')) ?: [],
            glob(base_path('modules/*/Controllers/Admin/*.php')) ?: [],
        );

        $this->assertNotEmpty($files, 'Контроллеры панели не найдены — проверка бессмысленна');

        $found = [];

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);

            // Флеш после редиректа и ошибки формы — оба видны пользователю.
            $patterns = [
                '~->with\(\s*\'(?:success|error|warning|info)\'\s*,\s*[\'"]([^\'"]*[А-Яа-яЁё][^\'"]*)[\'"]~u',
                '~withErrors\(\s*\[\s*[\'"][a-z_]+[\'"]\s*=>\s*[\'"]([^\'"\n]*[А-Яа-яЁё][^\'"\n]*)[\'"]~u',
            ];

            foreach ($patterns as $pattern) {
                $matches = [];
                preg_match_all($pattern, $source, $matches);

                foreach ($matches[1] as $literal) {
                    $found[] = basename($file) . ': ' . $literal;
                }
            }
        }

        $this->assertSame([], $found, "Русские литералы во флеш-сообщениях:\n" . implode("\n", $found));
    }

    public function test_flash_messages_substitute_named_parameters(): void
    {
        // Сообщения с подстановками переведены на именованные параметры
        // (:name, :count), а не на интерполяцию "{$переменных}" — иначе
        // строка не может жить в словаре.
        app()->setLocale('ru');
        $this->assertSame('Модуль «Seo» включён.', __('admin.flash.module_enabled', ['name' => 'Seo']));
        $this->assertSame('Заархивировано. Сообщений: 7', __('admin.flash.msg_archived', ['count' => 7]));

        app()->setLocale('en');
        $this->assertSame('The “Seo” module has been enabled.', __('admin.flash.module_enabled', ['name' => 'Seo']));
        $this->assertSame('Archived. Messages: 7', __('admin.flash.msg_archived', ['count' => 7]));
    }
}
