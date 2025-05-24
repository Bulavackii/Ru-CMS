<?php

namespace Modules\Notifications\View\Components\Frontend;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\Notifications\Models\Notification;

class NotificationsComponent extends Component
{
    public $notifications;

    public function __construct()
    {
        $currentPath = '/' . ltrim(Request::path(), '/');

        $this->notifications = Notification::query()
            ->where('enabled', true)
            ->get()
            ->filter(function (Notification $notification) use ($currentPath) {
                return $this->matchesRouteFilter($notification->route_filter, $currentPath);
            })
            ->values();
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
