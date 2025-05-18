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
        $currentPath = '/' . ltrim(Request::path(), '/'); // 🔍 Текущий путь, например: /news/123

        // 🧠 Получаем уведомления, отфильтрованные по включённости, цели и маршруту
        $this->notifications = Notification::query()
            ->where('enabled', true)

            // 🎯 Учитываем целевую аудиторию
            ->where(function ($query) use ($user) {
                if (!$user) {
                    $query->where('target', 'all');
                } elseif ($user->is_admin) {
                    $query->whereIn('target', ['all', 'admin']);
                } else {
                    $query->whereIn('target', ['all', 'user']);
                }
            })

            ->get()

            // 🌐 Фильтруем по маршруту
            ->filter(function (Notification $notification) use ($currentPath) {
                return $this->matchesRouteFilter($notification->route_filter, $currentPath);
            })

            ->values(); // 🔄 Сбрасываем индексы коллекции
    }

    /**
     * 🔍 Проверяет соответствие фильтра маршруту
     */
    protected function matchesRouteFilter(?string $filter, string $currentPath): bool
    {
        $filter = trim($filter ?? '', '/');
        $cleanPath = trim($currentPath, '/');

        // 📌 Если маршрут пустой — отображаем только на главной
        if ($filter === '' || $filter === '/') {
            return $cleanPath === '';
        }

        // 🌟 Wildcard-поддержка: например, /news/* будет соответствовать /news/123
        if ($filter === '*') {
            return true;
        }

        if (str_contains($filter, '*')) {
            $pattern = '#^' . str_replace('\*', '.*', preg_quote($filter, '#')) . '$#i';
            return (bool)preg_match($pattern, $cleanPath);
        }

        // 📎 Строгое совпадение
        return $cleanPath === $filter;
    }

    /**
     * 🧾 Отображаем Blade-представление
     */
    public function render()
    {
        return view('Notifications::frontend.list');
    }
}
