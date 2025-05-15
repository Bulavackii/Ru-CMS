<?php

namespace Modules\Notifications\View\Components\Frontend;

use Illuminate\View\Component;
use Modules\Notifications\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class NotificationsComponent extends Component
{
    public $notifications;

    public function __construct()
    {
        $user = Auth::user();
        $currentPath = '/' . trim(Request::path(), '/'); // например: /news/123

        $this->notifications = Notification::query()
            ->where('enabled', true)
            ->where(function ($q) use ($user) {
                if (!$user) {
                    $q->where('target', 'all');
                } elseif ($user->is_admin) {
                    $q->whereIn('target', ['all', 'admin']);
                } else {
                    $q->whereIn('target', ['all', 'user']);
                }
            })
            ->get()
            ->filter(function ($notification) use ($currentPath) {
                $filter = trim($notification->route_filter ?? '', '/');

                if ($filter === '' || $filter === '/') {
                    return $currentPath === '';
                }

                // wildcard (поддержка /news/* и т.п.)
                if (str_contains($filter, '*')) {
                    $pattern = '#^' . str_replace('\*', '.*', preg_quote($filter, '#')) . '$#i';
                    return (bool)preg_match($pattern, trim($currentPath, '/'));
                }

                return trim($currentPath, '/') === $filter;
            })
            ->values(); // сброс индексов
    }

    public function render()
    {
        return view('Notifications::frontend.list');
    }
}
