<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🌐 Локализация: словари и перевод публичной части.
 *
 * До 26.07.2026 в шаблонах фронта было 7 вызовов __() против ~570
 * захардкоженных русских строк: переключатель языка менял locale, но текст
 * оставался русским. Плюс словари de/fr/it были неполными.
 */
class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /** Локаль-эталон: структуру ключей задаёт она */
    private const REFERENCE = 'ru';

    /** Словари, обязательные для всех локалей */
    private const REQUIRED_GROUPS = ['frontend', 'admin', 'messages', 'pagination', 'validation'];

    private function locales(): array
    {
        return array_values(array_diff(available_locales(), [self::REFERENCE]));
    }

    private function keys(string $locale, string $group): array
    {
        $file = app()->langPath() . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $group . '.php';

        if (! is_file($file)) {
            return [];
        }

        $data = include $file;

        return is_array($data) ? $this->flatten($data) : [];
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $keys = [];

        foreach ($data as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            $keys = is_array($value)
                ? array_merge($keys, $this->flatten($value, $full))
                : array_merge($keys, [$full]);
        }

        return $keys;
    }

    // ── Словари ───────────────────────────────────────────────────────────

    public function test_required_dictionaries_match_the_reference(): void
    {
        // Ловит расхождения при будущих правках: добавили ключ в ru —
        // добавьте и в остальные (или увидите падение здесь)
        foreach (self::REQUIRED_GROUPS as $group) {
            $reference = $this->keys(self::REFERENCE, $group);
            $this->assertNotEmpty($reference, "Эталонный словарь {$group}.php пуст");

            foreach ($this->locales() as $locale) {
                $keys = $this->keys($locale, $group);

                $this->assertNotEmpty($keys, "У локали {$locale} нет словаря {$group}.php");
                $this->assertSame(
                    [],
                    array_values(array_diff($reference, $keys)),
                    "В {$locale}/{$group}.php не хватает ключей"
                );
                $this->assertSame(
                    [],
                    array_values(array_diff($keys, $reference)),
                    "В {$locale}/{$group}.php есть ключи, которых нет в эталоне"
                );
            }
        }
    }

    public function test_frontend_dictionary_has_no_empty_values(): void
    {
        foreach (available_locales() as $locale) {
            $file = app()->langPath() . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'frontend.php';
            $data = include $file;

            array_walk_recursive($data, function ($value, $key) use ($locale) {
                $this->assertNotSame('', trim((string) $value), "Пустое значение {$key} в {$locale}/frontend.php");
            });
        }
    }

    // ── Перевод сайта ─────────────────────────────────────────────────────

    public function test_every_locale_renders_the_site(): void
    {
        foreach (available_locales() as $locale) {
            $this->withSession(['app_locale' => $locale, 'locale' => $locale])
                ->get('/')
                ->assertStatus(200)
                ->assertSee('lang="' . $locale . '"', false);
        }
    }

    public function test_switching_locale_changes_the_text(): void
    {
        $expectations = [
            'ru' => 'Искать',
            'en' => 'Search',
        ];

        foreach ($expectations as $locale => $needle) {
            $this->withSession(['app_locale' => $locale, 'locale' => $locale])
                ->get('/')
                ->assertSee($needle, false);
        }
    }

    public function test_locale_route_stores_choice(): void
    {
        $this->get(route('frontend.locale.set', 'en'))->assertRedirect();
        $this->assertSame('en', session('app_locale'));

        // Недоступная локаль игнорируется
        $this->get(route('frontend.locale.set', 'zz'))->assertRedirect();
        $this->assertSame('en', session('app_locale'));
    }

    public function test_install_dictionary_exists_for_every_locale(): void
    {
        // Раньше здесь проверялся откат на английский: словаря install.php
        // у de/fr/it не было. Теперь он есть у всех семи локалей, и ценность
        // представляет обратное — что наборы ключей совпадают с эталоном.
        $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
            $out = [];

            foreach ($items as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                $out += is_array($value) ? $flatten($value, $path) : [$path => $value];
            }

            return $out;
        };

        $reference = $flatten(require lang_path('ru/install.php'));
        $this->assertNotEmpty($reference);

        // Языков интерфейса два: русский эталонный, английский — fallback.
        foreach (['en'] as $locale) {
            $path = lang_path($locale . '/install.php');
            $this->assertFileExists($path, "Нет словаря установки для локали {$locale}");

            $keys = $flatten(require $path);

            $this->assertSame([], array_keys(array_diff_key($reference, $keys)),
                "В локали {$locale} не хватает ключей установки");
            $this->assertSame([], array_keys(array_diff_key($keys, $reference)),
                "В локали {$locale} есть лишние ключи установки");
        }
    }

    public function test_fallback_locale_is_english(): void
    {
        // Отсутствующий ключ обязан уезжать в английский, а не в русский:
        // на этом держатся частичные наборы переводов.
        $this->assertSame('en', config('app.fallback_locale'));
    }

    // ── SEO-разметка ──────────────────────────────────────────────────────

    public function test_og_locale_and_hreflang_follow_the_language(): void
    {
        $response = $this->withSession(['app_locale' => 'en', 'locale' => 'en'])->get('/');

        $response->assertSee('og:locale" content="en_', false);
        // Ссылка на другой язык присутствует, на текущий — нет
        $response->assertSee('hreflang="ru"', false);
        $response->assertDontSee('hreflang="en"', false);
        $response->assertSee('hreflang="x-default"', false);
    }

    // ── Раздел в админке ──────────────────────────────────────────────────

    public function test_localization_admin_pages_open(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.localization.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.localization.create'))->assertStatus(200);
    }

    public function test_import_modal_is_not_bootstrap_dependent(): void
    {
        // Кнопка «Импорт» была на data-bs-toggle, а Bootstrap JS в проекте нет —
        // модал не открывался вовсе
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.localization.index'));

        $response->assertDontSee('data-bs-toggle', false);
        $response->assertSee('importOpen', false);
    }
}
