<?php

namespace Modules\Notifications\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Notifications\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Получает список актуальных уведомлений для отображения
     */
    public function getActiveNotifications(Request $request)
    {
        $user = Auth::user();
        $route = Route::currentRouteName();
        $url = $request->path();

        $notifications = Notification::query()
            ->where('active', true)
            ->where(function ($query) use ($user) {
                $query->where('audience', 'all');

                if ($user && $user->is_admin) {
                    $query->orWhere('audience', 'admin');
                } elseif ($user) {
                    $query->orWhere('audience', 'user');
                }
            })
            ->where(function ($query) use ($route, $url) {
                $query->whereNull('url')
                      ->orWhere('url', $url)
                      ->orWhere('url', Route::currentRouteName());
            })
            ->latest()
            ->get();

        return response()->json($notifications);
    }
}
