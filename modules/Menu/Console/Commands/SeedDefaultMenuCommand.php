<?php

namespace Modules\Menu\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

/**
 * Первичное автозаполнение / сброс меню-хедера к состоянию «как после установки».
 *
 * Единый источник дефолтного меню: этой командой пользуется и мастер установки
 * (InstallController::seedDefaultMenu → self::seed(false)), и ручной сброс
 * `php artisan menu:seed-default --reset`.
 *
 *   php artisan menu:seed-default            # дозаполнить недостающие пункты (идемпотентно)
 *   php artisan menu:seed-default --reset    # удалить текущие пункты «Главного меню» и создать заново
 */
class SeedDefaultMenuCommand extends Command
{
    protected $signature = 'menu:seed-default {--reset : Пересоздать пункты «Главного меню» заново (удалит текущие)}';

    protected $description = 'Автозаполнение/сброс меню-хедера к дефолту (Главная/О нас/Вопросы/Контакты)';

    /**
     * Канонический набор пунктов меню-хедера по умолчанию.
     * Иконки — валидные имена Lucide (дефолтный режим темы): home/info/help-circle/mail.
     */
    public static function defaultItems(): array
    {
        return [
            ['title' => 'Главная',  'url' => '/',         'icon' => 'home',        'order' => 1],
            ['title' => 'О нас',    'url' => '/about',    'icon' => 'info',        'order' => 2],
            ['title' => 'Вопросы',  'url' => '/faq',      'icon' => 'help-circle', 'order' => 3],
            ['title' => 'Контакты', 'url' => '/contacts', 'icon' => 'mail',        'order' => 4],
        ];
    }

    /**
     * Гарантирует «Главное меню» (header) с дефолтными пунктами.
     * $reset=true — предварительно удаляет текущие пункты этого меню (полный
     * сброс). Идемпотентно; возвращает id меню. Не трогает другие меню.
     */
    public static function seed(bool $reset = false): int
    {
        return DB::transaction(function () use ($reset) {
            $menu = Menu::firstOrCreate(
                ['title' => 'Главное меню', 'position' => 'header'],
                ['active' => true]
            );
            if (! $menu->active) {
                $menu->update(['active' => true]);
            }

            if ($reset) {
                MenuItem::where('menu_id', $menu->id)->delete();
            }

            foreach (self::defaultItems() as $item) {
                MenuItem::firstOrCreate(
                    ['menu_id' => $menu->id, 'url' => $item['url']],
                    [
                        'title'     => $item['title'],
                        'type'      => 'url',
                        'icon'      => $item['icon'],
                        'order'     => $item['order'],
                        'active'    => true,
                        'parent_id' => null,
                    ]
                );
            }

            return $menu->id;
        });
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        $menuId = self::seed($reset);

        $this->info(($reset
                ? 'Меню-хедер сброшено к дефолту'
                : 'Меню-хедер по умолчанию проверено/дозаполнено')
            . " (menu #{$menuId}).");

        return self::SUCCESS;
    }
}
