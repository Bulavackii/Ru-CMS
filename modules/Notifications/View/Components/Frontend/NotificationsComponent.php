<?php

namespace Modules\Notifications\View\Components\Frontend;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\Notifications\Models\Notification;

class NotificationsComponent extends Component
{
    // 📦 Коллекция отфильтрованных уведомлений
    public $notifications;

    public function __construct()
    {
        $user = Auth::user();
        $currentPath = '/' . ltrim(Request::path(), '/');

        $this->notifications = Notification::query()
            ->where('enabled', true)
            ->get()
            ->filter(function (Notification $notification) use ($user, $currentPath) {
                // ✅ Аудитория: только для админов
                if ($notification->target === 'admin') {
                    if (!$user || !boolval($user->is_admin)) {
                        return false;
                    }
                }

                // ✅ Аудитория: только для пользователей
                if ($notification->target === 'user') {
                    if (!$user || boolval($user->is_admin)) {
                        return false;
                    }
                }

                // ✅ Аудитория: all — проходит всегда (в том числе без авторизации)

                // ✅ Проверка маршрута
                return $this->matchesRouteFilter($notification->route_filter, $currentPath);
            })
            ->values(); // 🔄 Сброс ключей
    }

    /**
     * 🔍 Проверяет, подходит ли route_filter под текущий путь
     */
    protected function matchesRouteFilter(?string $filter, string $currentPath): bool
    {
        $filter = trim($filter ?? '');

        // 🚫 Пустой фильтр — не показывать
        if ($filter === '') {
            return false;
        }

        // 🧹 Нормализация путей
        $filterPath = '/' . ltrim($filter, '/');
        $currentPath = '/' . ltrim($currentPath, '/');

        // 🏠 Главная страница
        if ($filterPath === '/') {
            return $currentPath === '/';
        }

        // 🌟 Wildcard-поддержка: /news/* и т.п.
        if (str_contains($filterPath, '*')) {
            $pattern = '#^' . str_replace('\*', '.*', preg_quote($filterPath, '#')) . '$#i';
            return (bool) preg_match($pattern, $currentPath);
        }

        // 🔁 Строгое сравнение
        return $currentPath === $filterPath;
    }

    /**
     * 📄 Рендер компонента
     */
    public function render()
    {
        return view('Notifications::frontend.list');
    }
}
