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
     * 📡 Получить список активных уведомлений для текущего пользователя и маршрута
     *
     * 🔍 Учитываются:
     * - статус уведомления (`active = true`)
     * - тип пользователя (все / админ / пользователь)
     * - фильтрация по URL или названию маршрута
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveNotifications(Request $request)
    {
        $user = Auth::user();
        $route = Route::currentRouteName();
        $url = '/' . ltrim($request->path(), '/');

        $notifications = Notification::query()
            ->where('enabled', true)
            ->where(function ($query) use ($user) {
                $query->where('target', 'all');
                if ($user && $user->is_admin) {
                    $query->orWhere('target', 'admin');
                } elseif ($user) {
                    $query->orWhere('target', 'user');
                }
            })
            ->where(function ($query) use ($route, $url) {
                $query->whereNull('route_filter')
                    ->orWhere('route_filter', $url)
                    ->orWhere('route_filter', $route);
            })
            ->latest()
            ->get();

        return response()->json($notifications);
    }
}
