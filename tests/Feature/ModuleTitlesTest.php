<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\System\Models\Module;
use Tests\TestCase;

/**
 * Названия модулей и подписи разделов в левом меню — одно и то же для человека.
 *
 * Раздел «Модули» и навигация показывают одни и те же вещи, и если подписи
 * разъезжаются, один раздел приходится узнавать по двум разным названиям.
 * Так уже было: System назывался «Система» при пункте меню «Модули», Payments
 * — «Платежи» при пункте «Оплата», а Visual остался «Визуальным редактором»
 * после того, как редактор из него убрали.
 *
 * Проверка нужна ещё и потому, что названия живут в двух местах сразу:
 * в modules/<Name>/module.json и в словаре admin.sections. Правку одного из
 * них легко не заметить.
 */
class ModuleTitlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Модуль → ключ раздела в словаре. Модулей без пункта меню здесь нет:
     * Сообщения, Отзывы, Комментарии и Установка в навигацию не выведены.
     */
    private const MENU = [
        'System'        => 'modules',
        'Menu'          => 'menus',
        'News'          => 'news',
        'Categories'    => 'categories',
        'Slideshow'     => 'slideshow',
        'Files'         => 'files',
        'NewsIO'        => 'newsio',
        'Users'         => 'users',
        'Search'        => 'search',
        'Notifications' => 'notifications',
        'Seo'           => 'seo',
        'Localization'  => 'localization',
        'Captcha'       => 'captcha',
        'Accessibility' => 'accessibility',
        'Payments'      => 'payments',
        'Delivery'      => 'delivery',
    ];

    /** @test */
    public function module_titles_match_the_sidebar_labels(): void
    {
        $sections = include base_path('resources/lang/ru/admin.php');
        $sections = $sections['sections'];

        foreach (self::MENU as $module => $key) {
            $path = base_path("modules/{$module}/module.json");

            $this->assertFileExists($path, "У модуля {$module} нет module.json");

            $manifest = json_decode(file_get_contents($path), true);
            $title = (string) ($manifest['title'] ?? '');

            $this->assertNotSame('', $title, "У модуля {$module} пустое название");

            // Значок в начале — украшение списка, а не часть названия:
            // сверяем слово после него.
            $word = trim(preg_replace('~^[^\p{L}]+~u', '', $title));

            $this->assertSame(
                $sections[$key],
                $word,
                "Название модуля {$module} («{$word}») не совпадает с пунктом меню «{$sections[$key]}»"
            );
        }
    }

    /** @test */
    public function a_fresh_install_fills_titles_right_away(): void
    {
        // Мастер установки зовёт modules:sync. Раньше команда название НЕ
        // писала, и сразу после установки таблица стояла с пустыми строками
        // до первого захода в панель — заполнял их уже провайдер.
        $this->assertSame(0, Module::count(), 'Таблица модулей должна быть пуста до синхронизации');

        $this->artisan('modules:sync')->assertSuccessful();

        foreach (self::MENU as $module => $key) {
            $row = Module::where('name', $module)->first();

            $this->assertNotNull($row, "Модуль {$module} не попал в таблицу");
            $this->assertNotNull($row->title, "У модуля {$module} после установки пустое название");
        }
    }
}
