<?php

namespace Modules\Menu\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

/**
 * Первичное автозаполнение / сброс демо-меню (шапка + два столбца подвала + сайдбар).
 *
 * Единый источник дефолтных меню: этой командой пользуется и мастер установки
 * (InstallController::seedDefaultMenu → self::seed(false)), и ручной запуск
 * `php artisan menu:seed-default [--reset]`. После установки на сайте сразу
 * активны и заполнены меню — для демонстрации всех позиций.
 *
 *   php artisan menu:seed-default            # дозаполнить недостающее (идемпотентно)
 *   php artisan menu:seed-default --reset    # пересоздать пункты всех дефолтных меню
 */
class SeedDefaultMenuCommand extends Command
{
    protected $signature = 'menu:seed-default {--reset : Пересоздать пункты всех дефолтных меню заново (удалит текущие)}';

    protected $description = 'Автозаполнение/сброс демо-меню (шапка/подвал/сайдбар)';

    /**
     * Канонические дефолтные меню — ПЛОСКИЙ список (позиций может быть несколько на
     * одну область: напр. два footer-меню = два столбца подвала, по одному меню на
     * столбец). Иконки — валидные имена Lucide (дефолтный режим темы).
     */
    public static function definitions(): array
    {
        return [
            [
                // Шапка и боковая панель показывают ОДИН И ТОТ ЖЕ набор
                // разделов: посетитель не должен гадать, почему сбоку пунктов
                // больше, чем сверху. Меняете здесь — поменяйте и в sidebar.
                'position' => 'header',
                'title'    => 'Главное меню',
                'items'    => [
                    ['title' => 'Главная',  'url' => '/',         'icon' => 'home'],
                    ['title' => 'Новости',  'url' => '/news',     'icon' => 'newspaper'],
                    ['title' => 'О нас',    'url' => '/about',    'icon' => 'info'],
                    ['title' => 'Вопросы',  'url' => '/faq',      'icon' => 'help-circle'],
                    ['title' => 'Контакты', 'url' => '/contacts', 'icon' => 'mail'],
                ],
            ],
            // Подвал — три коротких столбца по 3 пункта (title = шапка столбца).
            //
            // Третий столбец добавлен намеренно: сетка подвала держит три
            // колонки в одну строку (замер: по 169px при шаге 28px), а
            // справочные ссылки логичнее собрать отдельно от «Информации»
            // и «Участия». Столбцов может быть любое число — добавили меню
            // с позицией footer в панели, появилась ещё одна колонка.
            [
                'position' => 'footer',
                'title'    => 'Информация',
                'items'    => [
                    ['title' => 'О проекте',         'url' => '/about',   'icon' => 'info'],
                    ['title' => 'Соглашение',        'url' => '/terms',   'icon' => 'file-text'],
                    ['title' => 'Конфиденциальность', 'url' => '/privacy', 'icon' => 'shield'],
                    ['title' => 'Карта сайта',       'url' => '/sitemap', 'icon' => 'map'],
                ],
            ],
            [
                'position' => 'footer',
                'title'    => 'Участие',
                'items'    => [
                    ['title' => 'Разработчикам',     'url' => '/developers',  'icon' => 'code'],
                    ['title' => 'Сотрудничество',    'url' => '/partnership', 'icon' => 'users'],
                    ['title' => 'Поддержать проект', 'url' => '/donate',      'icon' => 'heart'],
                ],
            ],
            [
                'position' => 'footer',
                'title'    => 'Помощь',
                'items'    => [
                    ['title' => 'Частые вопросы', 'url' => '/faq',      'icon' => 'help-circle'],
                    ['title' => 'Контакты',       'url' => '/contacts', 'icon' => 'mail'],
                    ['title' => 'Поиск по сайту', 'url' => '/search',   'icon' => 'search'],
                ],
            ],
            // Боковая панель — быстрый доступ к основным разделам сайта.
            [
                'position' => 'sidebar',
                'title'    => 'Боковое меню',
                'items'    => [
                    ['title' => 'Главная',  'url' => '/',         'icon' => 'home'],
                    ['title' => 'Новости',  'url' => '/news',     'icon' => 'newspaper'],
                    ['title' => 'О нас',    'url' => '/about',    'icon' => 'info'],
                    ['title' => 'Вопросы',  'url' => '/faq',      'icon' => 'help-circle'],
                    ['title' => 'Контакты', 'url' => '/contacts', 'icon' => 'mail'],
                ],
            ],
        ];
    }

    /**
     * Гарантирует дефолтные меню с их пунктами. $reset=true — предварительно удаляет
     * текущие пункты каждого меню (полный сброс). Идемпотентно: меню переиспользуются
     * по паре title+position, пункты — по паре menu_id+url.
     */
    public static function seed(bool $reset = false): void
    {
        DB::transaction(function () use ($reset) {
            foreach (self::definitions() as $def) {
                $menu = Menu::firstOrCreate(
                    ['title' => $def['title'], 'position' => $def['position']],
                    ['active' => true]
                );
                if (! $menu->active) {
                    $menu->update(['active' => true]);
                }

                if ($reset) {
                    MenuItem::where('menu_id', $menu->id)->delete();
                }

                foreach ($def['items'] as $i => $item) {
                    MenuItem::firstOrCreate(
                        ['menu_id' => $menu->id, 'url' => $item['url']],
                        [
                            'title'     => $item['title'],
                            'type'      => 'url',
                            'icon'      => $item['icon'],
                            'order'     => $i + 1,
                            'active'    => true,
                            'parent_id' => null,
                        ]
                    );
                }
            }
        });
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        self::seed($reset);

        $this->info($reset
            ? 'Демо-меню сброшены к дефолту (шапка + 2 столбца подвала + сайдбар).'
            : 'Демо-меню проверены/дозаполнены (шапка + 2 столбца подвала + сайдбар).');

        return self::SUCCESS;
    }
}
