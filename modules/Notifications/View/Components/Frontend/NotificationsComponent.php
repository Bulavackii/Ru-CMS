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
        $currentPath = '/' . Request::path();

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
            ->where(function ($q) use ($currentPath) {
                $q->whereNull('route_filter')
                  ->orWhere('route_filter', '/')
                  ->orWhere('route_filter', $currentPath);
            })
            ->orderByDesc('created_at')
            ->get();
    }

    public function render()
    {
        return view('Notifications::frontend.list');
    }
}
