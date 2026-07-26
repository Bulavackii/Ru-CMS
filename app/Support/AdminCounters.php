<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * 🔢 Счётчики «есть новое» для навигации панели.
 *
 * Шапка и сайдбар показывают одни и те же числа (новые заказы, непрочитанные
 * сообщения, уведомления). Считаются они здесь один раз за запрос: если бы
 * каждый шаблон делал свои запросы, на каждой странице панели выполнялось бы
 * вдвое больше COUNT(*), причём по тем же таблицам.
 *
 * Каждый счётчик под своим try/catch: модуль может быть отключён, а таблицы —
 * ещё не созданы (например, во время установки). Навигация из-за этого падать
 * не должна, поэтому недоступный счётчик просто равен нулю.
 */
class AdminCounters
{
    /** @var array<string,int>|null Мемо на время запроса */
    private static ?array $cache = null;

    /**
     * @return array{orders:int,messages:int,notifications:int}
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        return self::$cache = [
            'orders'        => self::count(fn () => \Modules\Payments\Models\Order::where('is_new', true)->count(),
                \Modules\Payments\Models\Order::class),
            'messages'      => self::unreadMessages(),
            'notifications' => self::count(fn () => \Modules\Notifications\Models\Notification::where('enabled', 1)->count(),
                \Modules\Notifications\Models\Notification::class),
        ];
    }

    /** Сброс мемо — нужен тестам, которые меняют данные внутри одного запроса. */
    public static function forget(): void
    {
        self::$cache = null;
    }

    private static function unreadMessages(): int
    {
        return self::count(function () {
            if (! Auth::check()) {
                return 0;
            }

            // Колонка называется is_read (а не read — на этом уже обжигались
            // в DashboardController: там запрос падал на PostgreSQL)
            return \Modules\Messages\Models\Message::where('to_user_id', Auth::id())
                ->where('is_read', false)
                ->notArchived()
                ->count();
        }, \Modules\Messages\Models\Message::class);
    }

    /**
     * Безопасно считает значение: модуль может быть отключён (класса нет),
     * а таблицы может ещё не существовать.
     */
    private static function count(callable $callback, string $modelClass): int
    {
        try {
            if (! class_exists($modelClass)) {
                return 0;
            }

            return (int) $callback();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
