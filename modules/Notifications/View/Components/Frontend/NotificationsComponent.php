<?php

namespace Modules\Notifications\View\Components\Frontend;

use Illuminate\View\Component;
use Modules\Notifications\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class NotificationsComponent extends Component
{
    public $notifications = [];

    public function __construct()
    {
        $user = Auth::user();
        $currentPath = '/' . ltrim(Request::path(), '/');

        $query = Notification::query()
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
            ->where(function ($q) use ($currentPath) {
                $q->whereNull('route_filter')
                    ->orWhere('route_filter', '/')
                    ->orWhere('route_filter', $currentPath);
            });

        $notifications = $query->orderByDesc('created_at')->get();

        // ❗ Отфильтровываем уведомления с cookie, если уже установлено
        foreach ($notifications as $n) {
            if ($n->type === 'cookie' && $n->cookie_key) {
                $cookie = Request::cookie($n->cookie_key);
                if (!$cookie) {
                    $this->notifications[] = $n;
                }
            } else {
                $this->notifications[] = $n;
            }
        }
    }

    public function render()
    {
        return view('Notifications::frontend.list');
    }
}
