<?php

namespace Modules\Notifications\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Models\Notification;

/**
 * Демонстрационное уведомление сразу после установки.
 *
 * Вызывается мастером установки (InstallController::finish → self::seed(false)),
 * чтобы раздел «Уведомления» не встречал пустой таблицей, а показывал готовый
 * пример со всеми заполненными полями.
 *
 * ⚠️ Уведомление создаётся ВЫКЛЮЧЕННЫМ (enabled = false): это образец для правки,
 * а не сообщение, которое должно всплыть у посетителей свежего сайта. Включается
 * одним переключателем в списке.
 *
 *   php artisan notifications:seed-default            # создать, если его нет
 *   php artisan notifications:seed-default --reset    # перезаписать образец
 */
class SeedDefaultNotificationCommand extends Command
{
    protected $signature = 'notifications:seed-default {--reset : Перезаписать демо-уведомление}';

    protected $description = 'Демо-уведомление о техработах (выключенное, как пример)';

    /** Заголовок служит ключом идемпотентности. */
    public const DEMO_TITLE = 'Плановые технические работы';

    public static function definition(): array
    {
        return [
            'title'   => self::DEMO_TITLE,
            'message' => '<p>Сегодня с 22:00 до 23:00 возможны кратковременные перебои в работе '
                . 'сайта — мы обновляем оборудование. Приносим извинения за неудобства.</p>',
            'type'         => 'html',
            'target'       => 'all',
            'position'     => 'top',
            'duration'     => 0,
            'icon'         => '🔧',
            'route_filter' => null,
            'cookie_key'   => null,
            'bg_color'     => '#EEF2FF',
            'text_color'   => '#111827',
            'priority'     => 10,
            'starts_at'    => null,
            'ends_at'      => null,
            // Выключено намеренно — образец для правки, а не живое объявление
            'enabled'      => false,
        ];
    }

    public static function seed(bool $reset = false): void
    {
        DB::transaction(function () use ($reset) {
            $definition = self::definition();

            $existing = Notification::withTrashed()
                ->where('title', self::DEMO_TITLE)
                ->first();

            if (! $existing) {
                Notification::create($definition);
                return;
            }

            if ($reset) {
                $existing->restore();
                $existing->update($definition);
            }
        });
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        self::seed($reset);

        $this->info($reset
            ? 'Демо-уведомление перезаписано (выключено).'
            : 'Демо-уведомление проверено/создано (выключено).');

        return self::SUCCESS;
    }
}
