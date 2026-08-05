<?php

namespace Modules\Notifications\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Models\Notification;

/**
 * Уведомления, которые заводятся при установке.
 *
 * Их два, и они разные по смыслу:
 *
 *  1. Согласие на cookie и обработку персональных данных — ВКЛЮЧЕНО.
 *     Оно нужно любому сайту в России по закону 152-ФЗ, и оставлять его
 *     выключенным «на всякий случай» значит выпустить сайт с нарушением.
 *     Заодно это единственный выключатель счётчиков: пока посетитель не
 *     ответил, Яндекс.Метрика не запускается вовсе (см. layouts/frontend).
 *
 *  2. Демонстрация техработ — ВЫКЛЮЧЕНО. Образец для правки, а не сообщение,
 *     которое должно всплыть у посетителей свежего сайта.
 *
 * Вызывается мастером установки (InstallController::finish → self::seed(false)).
 *
 *   php artisan notifications:seed-default            # создать недостающие
 *   php artisan notifications:seed-default --reset    # перезаписать оба
 */
class SeedDefaultNotificationCommand extends Command
{
    protected $signature = 'notifications:seed-default {--reset : Перезаписать демо-уведомление}';

    protected $description = 'Согласие на cookie (включено) и демо о техработах (выключено)';

    /** Заголовки служат ключами идемпотентности. */
    public const DEMO_TITLE = 'Плановые технические работы';

    public const CONSENT_TITLE = 'Cookie и персональные данные';

    /**
     * Ключ cookie согласия. Тот же читает layouts/frontend перед запуском
     * счётчиков — менять только в двух местах сразу.
     */
    public const CONSENT_COOKIE = 'ru_consent';

    /**
     * Согласие на cookie и обработку персональных данных.
     *
     * Внизу, а не сверху: шапку сайта закрывать нечем, а нижняя полоса не
     * мешает читать. Без срока показа и без таймера — ответ должен быть
     * осознанным, молчание согласием не считается.
     */
    public static function consentDefinition(): array
    {
        return [
            'title'   => self::CONSENT_TITLE,
            'message' => '<p>Мы используем cookie, чтобы сайт работал и был удобнее, '
                . 'и обрабатываем обезличенные данные о посещении. Подробности — '
                . 'в <a href="/privacy">политике конфиденциальности</a>.</p>',
            'type'         => 'cookie',
            'target'       => 'all',
            'position'     => 'bottom',
            // Ноль: таймера у согласия нет, оно ждёт ответа.
            'duration'     => 0,
            'icon'         => '🍪',
            'route_filter' => null,
            'cookie_key'   => self::CONSENT_COOKIE,
            'bg_color'     => '#FFFFFF',
            'text_color'   => '#111827',
            // Выше демо: если однажды включат оба, согласие должно быть первым.
            'priority'     => 100,
            'starts_at'    => null,
            'ends_at'      => null,
            'enabled'      => true,
        ];
    }

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
            foreach ([self::consentDefinition(), self::definition()] as $definition) {
                $existing = Notification::withTrashed()
                    ->where('title', $definition['title'])
                    ->first();

                if (! $existing) {
                    Notification::create($definition);
                    continue;
                }

                if ($reset) {
                    $existing->restore();
                    $existing->update($definition);
                }
            }
        });
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        self::seed($reset);

        $this->info($reset
            ? 'Согласие на cookie и демо о техработах перезаписаны.'
            : 'Согласие на cookie (включено) и демо о техработах (выключено) проверены.');

        return self::SUCCESS;
    }
}
