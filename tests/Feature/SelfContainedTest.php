<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🔴 Самодостаточность: страница не должна ничего тянуть с чужих хостов.
 *
 * Проект продаётся как автономный — он обязан работать в закрытом контуре и не
 * рассказывать чужим службам, кто и когда зашёл на сайт.
 *
 * ⚠️ Заслон `APP_STANDALONE` держит только СЕРВЕРНЫЕ запросы
 * (`Http::globalRequestMiddleware`). Шрифт, счётчик и карта подключаются тегом
 * в разметке — их запрашивает БРАУЗЕР ПОСЕТИТЕЛЯ, и серверный middleware этого
 * не видит вовсе. Поэтому такие места проверяются отдельно, по разметке.
 */
class SelfContainedTest extends TestCase
{
    use RefreshDatabase;

    /** Хосты, которые разрешено упоминать: это ССЫЛКИ, а не загрузка. */
    private const ССЫЛКИ = [
        'vk.com', 'github.com', 'max.ru', 'rutube.ru', 'yandex.ru',
        't.me', 'wa.me', 'instagram.com', 'twitter.com', 'tiktok.com',
        'w3.org', 'schema.org',
    ];

    /**
     * Из разметки достаём только то, что браузер РЕАЛЬНО загрузит:
     * link/script/img/iframe/source. Обычный `<a href>` — не загрузка.
     */
    private function внешниеРесурсы(string $html): array
    {
        preg_match_all(
            '~<(?:link|script|img|iframe|source)\b[^>]*\b(?:src|href)=["\'](https?://[^"\']+)~i',
            $html,
            $m
        );

        $чужие = [];

        foreach ($m[1] as $адрес) {
            $хост = strtolower((string) parse_url($адрес, PHP_URL_HOST));

            if ($хост === '' || str_contains($хост, '127.0.0.1') || str_contains($хост, 'localhost')) {
                continue;
            }

            foreach (self::ССЫЛКИ as $разрешён) {
                if (str_ends_with($хост, $разрешён)) {
                    continue 2;
                }
            }

            $чужие[$хост] = true;
        }

        return array_keys($чужие);
    }

    /** Сайт ничего не грузит с чужих хостов. */
    public function test_frontend_loads_nothing_external(): void
    {
        $ответ = $this->get('/')->assertOk();

        $this->assertSame(
            [],
            $this->внешниеРесурсы($ответ->getContent()),
            'Главная страница тянет ресурсы с чужих хостов'
        );
    }

    /** Панель — тоже. */
    public function test_admin_loads_nothing_external(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true]));

        $ответ = $this->get('/admin')->assertOk();

        $this->assertSame(
            [],
            $this->внешниеРесурсы($ответ->getContent()),
            'Панель тянет ресурсы с чужих хостов'
        );
    }

    /**
     * 🔴 Автономный режим гасит и ШРИФТ ИЗ ТЕМЫ.
     *
     * Тема позволяет выбрать Google Fonts или Bunny — панель честно подписывает
     * их «запрос наружу». Но с включённым `APP_STANDALONE` такой выбор обязан
     * перестать действовать: иначе автономный сайт при каждом заходе отдавал бы
     * адрес посетителя в Google, а серверный заслон этого даже не заметил бы.
     */
    public function test_standalone_forces_local_fonts(): void
    {
        // Без автономного режима выбор темы уважается
        config(['app.standalone' => false]);
        $this->assertSame('google', theme_font_provider(['font_provider' => 'google']));
        $this->assertSame('bunny', theme_font_provider(['font_provider' => 'bunny']));

        // С автономным — только локальный
        config(['app.standalone' => true]);
        $this->assertSame('local', theme_font_provider(['font_provider' => 'google']));
        $this->assertSame('local', theme_font_provider(['font_provider' => 'bunny']));

        // Локальный и пустой остаются как были
        $this->assertSame('local', theme_font_provider(['font_provider' => 'local']));
        $this->assertNull(theme_font_provider([]));
    }

    /**
     * Решение о шрифте живёт в ОДНОМ месте.
     *
     * Вторая копия условия рано или поздно разошлась бы, и одна из половин
     * (сайт или панель) снова начала бы ходить наружу.
     */
    public function test_both_layouts_use_the_same_helper(): void
    {
        foreach ([
            'resources/views/layouts/partials/theme-head.blade.php',
            'resources/views/layouts/admin/header.blade.php',
        ] as $файл) {
            $текст = file_get_contents(base_path($файл));

            $this->assertStringContainsString(
                'theme_font_provider(',
                $текст,
                "{$файл} снова решает про шрифт сам"
            );

            $this->assertStringNotContainsString(
                "data_get(\$config, 'font_provider')",
                $текст,
                "{$файл} снова читает провайдера напрямую, минуя заслон"
            );
        }
    }

    /**
     * Шрифты и значки лежат локально.
     *
     * Если файла нет, вьюха молча отдаст пустую ссылку — и страница поедет
     * системным шрифтом, а заметят это не сразу.
     */
    public function test_local_fonts_and_icons_are_present(): void
    {
        $this->assertNotSame('', local_font_css('inter'), 'Шрифт по умолчанию не зарегистрирован');

        $this->assertFileExists(
            public_path('assets/fonts/inter/inter.css'),
            'Нет локального Inter — сайт уедет на системный шрифт'
        );

        // Набор значков по умолчанию
        $this->assertFileExists(
            public_path('assets/js/lucide.min.js'),
            'Нет локального набора значков'
        );
    }

    /**
     * Выгрузки наружу по умолчанию выключены.
     *
     * ⚠️ Проверяется значение ПО УМОЛЧАНИЮ (когда переменной нет в окружении),
     * а не то, что стоит на машине владельца: он вправе включить их осознанно.
     */
    public function test_outbound_features_are_off_by_default(): void
    {
        // ⚠️ Проверяем ИСХОДНИКИ конфига и пример окружения, а не `env()`:
        // тесты читают реальный `.env` разработчика, где что-то может быть
        // включено осознанно. Покупателю уезжает именно то, что здесь.
        $конфиг = file_get_contents(base_path('modules/Seo/Config/seo.php'));

        foreach (['INDEXNOW', 'WEBMASTER', 'METRICA'] as $возможность) {
            $this->assertMatchesRegularExpression(
                "~env\('SEO_{$возможность}_ENABLED',\s*false\)~",
                $конфиг,
                "Выгрузка {$возможность} включена по умолчанию — свежая установка пойдёт наружу без спроса"
            );
        }

        $пример = file_get_contents(base_path('.env.example'));

        foreach (['SEO_INDEXNOW_ENABLED', 'SEO_WEBMASTER_ENABLED', 'SEO_METRICA_ENABLED'] as $ключ) {
            $this->assertStringContainsString(
                "{$ключ}=false",
                $пример,
                "В .env.example {$ключ} не выключен"
            );
        }

        // Сервер обновлений: пусто — запроса нет вовсе
        $приложение = file_get_contents(config_path('app.php'));

        $this->assertMatchesRegularExpression(
            "~'update_server_url'\s*=>\s*env\('UPDATE_SERVER_URL',\s*''\)~",
            $приложение,
            'У сервера обновлений появился адрес по умолчанию — панель начнёт стучаться наружу'
        );
    }
}
