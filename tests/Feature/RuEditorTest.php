<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Свой редактор содержимого — везде, где он встроен.
 *
 * Проверяется то, что ломается молча: кнопка, которой нет в реестре, просто не
 * рисуется; страница без редактора выглядит как «поле пропало»; правило вёрстки,
 * заданное строкой стиля прямо в скрипте, нельзя подрезать медиазапросом.
 *
 * Живое поведение (нажатия, диалоги, чистка вставки) проверялось браузером —
 * здесь закреплены договорённости, которые обязаны пережить любую правку.
 */
class RuEditorTest extends TestCase
{
    use RefreshDatabase;

    /** Все наборы кнопок из компонента: имя набора => строка спецификации. */
    private const НАБОРЫ = [
        'full'   => 37,   // новости и страницы
        'page'   => 28,   // фрагменты
        'simple' => 12,   // ни одной вьюхой не используется, оставлен как готовый вариант
        'mail'   => 11,   // уведомления и письма
    ];

    private function исходникиРедактора(): string
    {
        static $кеш = null;

        if ($кеш === null) {
            $кеш = '';
            foreach (glob(public_path('assets/js/ru-editor*.js')) as $файл) {
                $кеш .= file_get_contents($файл);
            }
        }

        return $кеш;
    }

    private function спецификации(): array
    {
        $вьюха = file_get_contents(resource_path('views/components/ru-editor.blade.php'));

        preg_match("~'presets'?\s*=\s*\[(.+?)\n    \];~s", $вьюха, $m)
            || preg_match('~\$presets = \[(.+?)\n    \];~s', $вьюха, $m);

        $наборы = [];

        foreach (['full', 'page', 'simple', 'mail'] as $имя) {
            // Строка набора склеена из нескольких кусков через точку
            preg_match("~'{$имя}'\s*=>\s*((?:'[^']*'\s*\.?\s*)+),~", $вьюха, $куски);

            if (empty($куски[1])) {
                continue;
            }

            preg_match_all("~'([^']*)'~", $куски[1], $строки);
            $наборы[$имя] = implode('', $строки[1]);
        }

        return $наборы;
    }

    /**
     * 🔴 Каждая кнопка каждого набора известна редактору.
     *
     * Опечатка в наборе («bulllist») ничего не ломает и ничего не сообщает —
     * кнопка просто не появляется, и заметить это можно только глазами на той
     * самой странице.
     */
    public function test_every_button_of_every_preset_is_registered(): void
    {
        $исходники = $this->исходникиРедактора();
        $наборы = $this->спецификации();

        $this->assertNotEmpty($наборы, 'Не удалось разобрать наборы кнопок из компонента');

        foreach ($наборы as $имя => $спец) {
            $кнопки = array_values(array_filter(preg_split('~[\s|]+~', $спец)));

            $this->assertCount(
                self::НАБОРЫ[$имя],
                $кнопки,
                "Набор «{$имя}» изменился: было " . self::НАБОРЫ[$имя] . ' кнопок, стало ' . count($кнопки)
            );

            foreach ($кнопки as $кнопка) {
                // ⚠️ Сравнение БЕЗ учёта регистра: часть кнопок заводится
                // циклом из массива, и ядро приводит имя к нижнему регистру
                // само («strikeThrough» в исходнике → «strikethrough» в наборе).
                $this->assertMatchesRegularExpression(
                    "~['\"]" . preg_quote($кнопка, '~') . "['\"]~i",
                    $исходники,
                    "Кнопка «{$кнопка}» из набора «{$имя}» нигде не зарегистрирована — она молча не появится"
                );
            }
        }
    }

    /**
     * Редактор действительно встроен во все шесть мест.
     *
     * Список не выдуман: он собран грепом по `x-ru-editor`. Раздел, потерявший
     * редактор, выглядит как «пропало поле содержимого».
     */
    public function test_editor_is_embedded_in_every_place(): void
    {
        $места = [
            'modules/News/Views/admin/create.blade.php',
            'modules/News/Views/admin/edit.blade.php',
            'modules/Menu/Views/admin/pages/create.blade.php',
            'modules/Menu/Views/admin/pages/edit.blade.php',
            'modules/Notifications/Resources/views/admin/_form.blade.php',
            'modules/Visual/Resources/views/admin/fragments/editor.blade.php',
        ];

        foreach ($места as $файл) {
            $путь = base_path($файл);

            $this->assertFileExists($путь, "Вьюха {$файл} исчезла");
            $this->assertStringContainsString(
                'x-ru-editor',
                file_get_contents($путь),
                "В {$файл} больше нет редактора — поле содержимого пропало"
            );
        }
    }

    /** Страницы новостей и материалов открываются с поднятым редактором. */
    public function test_editor_pages_open(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $новость = News::create([
            'title' => 'Материал', 'slug' => 'material-dlya-redaktora',
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => true,
        ]);

        $страница = Page::create([
            'title' => 'Страница', 'slug' => 'stranica-dlya-redaktora',
            'content' => '<p>Текст</p>', 'published' => true,
        ]);

        $адреса = [
            route('admin.news.create'),
            route('admin.news.edit', $новость->id),
            route('admin.pages.create'),
            route('admin.pages.edit', $страница->id),
        ];

        foreach ($адреса as $адрес) {
            $ответ = $this->get($адрес)->assertOk();

            // Поле, настройки и скрипт — без любого из трёх редактор не встанет
            $ответ->assertSee('ru-ed-target', false);
            $ответ->assertSee('data-ru-editor-config', false);
            $ответ->assertSee('assets/js/ru-editor.js', false);
        }
    }

    /** Содержимое материала доезжает до редактора, а не теряется по дороге. */
    public function test_existing_content_reaches_the_editor(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $новость = News::create([
            'title' => 'Материал', 'slug' => 'material-s-soderzhimym',
            'content' => '<p>Особая строка для поиска</p>',
            'template' => 'default', 'published' => true,
        ]);

        $this->get(route('admin.news.edit', $новость->id))
            ->assertOk()
            ->assertSee('Особая строка для поиска', false);
    }

    /**
     * 🔴 Высота рамки задаётся ПЕРЕМЕННОЙ, а не строкой стиля.
     *
     * Инлайновый `height` перебивает таблицы стилей, поэтому подрезать рамку
     * под узкий экран было нечем: на телефоне панель инструментов (217
     * пикселей) плюс рамка (520) занимали ровно весь экран, и до кнопки
     * «Сохранить» приходилось долго крутить.
     */
    public function test_frame_height_can_be_capped_on_small_screens(): void
    {
        $ядро = file_get_contents(public_path('assets/js/ru-editor.js'));
        $стили = file_get_contents(public_path('assets/css/ru-editor.css'));

        $this->assertStringContainsString(
            "'--ru-ed-h:'",
            $ядро,
            'Высота рамки снова задаётся строкой стиля — медиазапросом её не подрезать'
        );

        $this->assertStringContainsString(
            'height: min(var(--ru-ed-h, 420px), 60vh)',
            $стили,
            'Пропал предел высоты рамки для узких экранов'
        );
    }

    /**
     * 🔴 Ручка изменения размера работает пальцем.
     *
     * Прежний обработчик слушал только `mousedown`: на телефоне и планшете
     * рамку нельзя было потянуть вообще — ручка рисовалась, отзывалась на
     * нажатие видом и не делала ничего.
     */
    public function test_resize_grip_supports_touch(): void
    {
        $ядро = file_get_contents(public_path('assets/js/ru-editor.js'));
        $стили = file_get_contents(public_path('assets/css/ru-editor.css'));

        $this->assertStringContainsString('pointerdown', $ядро, 'Ручка снова слушает только мышь');
        $this->assertStringContainsString('setPointerCapture', $ядро, 'Без захвата жест обрывается на границе ручки');

        // ⚠️ Одного обработчика мало: без `touch-action:none` браузер считает
        // движение по ручке прокруткой страницы, и `pointermove` не приходит.
        $this->assertStringContainsString('touch-action: none', $стили, 'Пропал touch-action у ручки');
    }

    /**
     * Полоса «найден черновик» переносится, а не вылезает за край.
     *
     * Элемент флекса не сжимается ниже содержимого: без переноса кнопка
     * «Удалить черновик» выходила за правый край и страница получала
     * горизонтальную прокрутку на 360.
     */
    public function test_draft_bar_wraps(): void
    {
        $инструменты = file_get_contents(public_path('assets/js/ru-editor-tools.js'));
        $стили = file_get_contents(public_path('assets/css/ru-editor.css'));

        $this->assertStringNotContainsString(
            "style: 'display:flex;align-items:center;gap:10px",
            $инструменты,
            'Оформление полосы снова задано строкой стиля — медиазапросом не поправить'
        );

        $this->assertMatchesRegularExpression(
            '~\.ru-ed-note--bar\s*\{[^}]*flex-wrap:\s*wrap~s',
            $стили,
            'У полосы черновика пропал перенос'
        );
    }

    /**
     * Диалоги помещаются в экран телефона.
     *
     * Окно вставки видео на 360 было 717 пикселей высотой при экране 740 и
     * уходило за нижний край вместе с кнопкой «Вставить».
     */
    public function test_dialogs_fit_a_phone_screen(): void
    {
        $стили = file_get_contents(public_path('assets/css/ru-editor.css'));

        $this->assertStringContainsString('max-height: 92vh', $стили, 'Пропал предел высоты диалога');

        // Поля набираются шестнадцатым: мельче Safari на iPhone приближает
        // страницу при фокусе и обратно её не отпускает.
        $this->assertMatchesRegularExpression(
            '~@media \(max-width: 1024px\).*?\.ru-ed-field textarea \{\s*font-size: 16px~s',
            $стили,
            'Поля диалога снова мельче 16 — Safari будет приближать страницу'
        );
    }

    /**
     * Строки редактора берутся из словаря, а не зашиты в скрипт.
     *
     * У каждой строки в скрипте есть запасное значение на случай отсутствия
     * ключа, но показываться должно переведённое.
     */
    public function test_editor_strings_come_from_the_dictionary(): void
    {
        foreach (['ru', 'en'] as $язык) {
            $словарь = (include resource_path("lang/{$язык}/admin.php"))['editor']['js'] ?? [];

            $this->assertNotEmpty($словарь, "У языка {$язык} нет строк редактора");
            $this->assertArrayHasKey('toolbar', $словарь, "В словаре {$язык} нет подписи панели");
        }
    }
}
