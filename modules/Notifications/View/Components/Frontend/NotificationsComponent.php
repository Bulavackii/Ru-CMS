<?php

namespace Modules\Notifications\View\Components\Frontend;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Modules\Notifications\Models\Notification;

class NotificationsComponent extends Component
{
    public $notifications;

    public function __construct()
    {
        $user = Auth::user();
        $currentPath = '/' . ltrim(Request::path(), '/');
        $route = Route::currentRouteName();
        $target = $user ? ($user->is_admin ? 'admin' : 'user') : null;

        // Кэширование на 5 минут
        $cacheKey = 'notifications_component_' . ($target ?? 'guest') . '_' . md5($route . $currentPath);
        
        $this->notifications = Cache::remember($cacheKey, 300, function () use ($user, $target, $route, $currentPath) {
            return Notification::query()
                ->active()
                ->forTarget($target, $user)
                ->forRoute($route, $currentPath)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function (Notification $notification) use ($currentPath) {
                    return $this->matchesRouteFilter($notification->route_filter, $currentPath);
                })
                ->values();
        });
    }

    protected function matchesRouteFilter(?string $filter, string $currentPath): bool
    {
        $filter = trim($filter ?? '');

        if ($filter === '') {
            return false;
        }

        $filterPath = '/' . ltrim($filter, '/');
        $currentPath = '/' . ltrim($currentPath, '/');

        if ($filterPath === '/') {
            return $currentPath === '/';
        }

        if (str_contains($filterPath, '*')) {
            $pattern = '#^' . str_replace('\*', '.*', preg_quote($filterPath, '#')) . '$#i';
            return (bool) preg_match($pattern, $currentPath);
        }

        return $currentPath === $filterPath;
    }

    public function render()
    {
        return view('Notifications::frontend.list');
    }
}
