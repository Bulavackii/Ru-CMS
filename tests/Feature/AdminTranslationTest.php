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

        $this->actingAs($admin)->withSession(['app_locale' => 'de'])
            ->get(route('admin.news.index'))
            ->assertSee('Beiträge', false);
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

    public function test_technical_identifiers_are_not_translated(): void
    {
        // Названия драйверов, слаги и коды локалей переводить нельзя —
        // это идентификаторы, а не подписи
        foreach (['en', 'de'] as $locale) {
            app()->setLocale($locale);

            $this->assertSame('SEO', __('admin.sections.seo'));
            $this->assertSame('Slideshow', __('admin.sections.slideshow'));
        }
    }
}
