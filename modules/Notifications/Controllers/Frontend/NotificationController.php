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
        // 👤 Получаем текущего пользователя
        $user = Auth::user();

        // 🌐 Текущий маршрут и URL
        $route = Route::currentRouteName(); // пример: news.show
        $url = $request->path();            // пример: news/3

        // 📦 Запрос к базе данных
        $notifications = Notification::query()
            ->where('active', true) // ✅ Только активные уведомления

            // 🎯 Фильтрация по целевой аудитории
            ->where(function ($query) use ($user) {
                $query->where('audience', 'all');

                if ($user && $user->is_admin) {
                    $query->orWhere('audience', 'admin');
                } elseif ($user) {
                    $query->orWhere('audience', 'user');
                }
            })

            // 🌐 Фильтрация по URL или названию маршрута
            ->where(function ($query) use ($route, $url) {
                $query->whereNull('url')                  // если не указан маршрут — показываем везде
                      ->orWhere('url', $url)              // точное совпадение с URL
                      ->orWhere('url', $route);           // или с именем маршрута
            })

            ->latest() // 🕓 По убыванию даты
            ->get();

        // 🔄 Ответ в формате JSON
        return response()->json($notifications);
    }
}
