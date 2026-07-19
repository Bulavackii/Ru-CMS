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

        // Кэширование на 5 минут
        $cacheKey = 'notifications_active_' . ($user ? ($user->is_admin ? 'admin' : 'user') : 'guest') . '_' . md5($route . $url);
        
        $notifications = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($user, $route, $url) {
            $target = $user ? ($user->is_admin ? 'admin' : 'user') : null;
            
            return Notification::query()
                ->active()
                ->forTarget($target, $user)
                ->forRoute($route, $url)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });

        return response()->json($notifications);
    }
}
