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

        // Версия в ключе — чтобы правка уведомления сразу доходила до посетителя
        // (см. Notification::flushCache()). Раньше кеш жил свои 5 минут.
        $cacheKey = 'notifications_component_v' . Notification::cacheVersion()
            . '_' . ($target ?? 'guest') . '_' . md5($route . $currentPath);

        $this->notifications = Cache::remember($cacheKey, 300, function () use ($user, $target, $route, $currentPath) {
            // Фильтр по странице считается в PHP (matchesRouteFilter), а не в SQL:
            // scope forRoute сравнивал route_filter с адресом на точное равенство и
            // отбрасывал записи с маской (/news/*) ещё до проверки — маски не работали
            // в принципе. Уведомлений немного, выборка кешируется.
            return Notification::query()
                ->active()
                ->forTarget($target, $user)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function (Notification $notification) use ($currentPath) {
                    return $this->matchesRouteFilter($notification->route_filter, $currentPath);
                })
                ->values();
        });

        // Уже отвеченные баннеры не отдаём вовсе.
        //
        // Отбор идёт ПОСЛЕ кеша и на каждый запрос: ответ хранится в cookie
        // посетителя, а кеш общий на всех — внутри него это решение принимать
        // нельзя. Скрипт баннера и так убрал бы его на клиенте, но тогда показ
        // засчитывался бы тому, кто ничего не увидел, а разметка ездила бы
        // впустую в каждом ответе.
        $this->notifications = $this->notifications
            ->reject(function (Notification $notification) {
                $key = $notification->type === 'cookie'
                    ? ($notification->cookie_key ?: 'notif_' . $notification->id)
                    : null;

                return $key && request()->cookie($key) !== null;
            })
            ->values();

        // 👁️ Счётчик показов: поле views_count было, но никто его не считал
        Notification::countViews($this->notifications->pluck('id')->all());
    }

    /**
     * Подходит ли уведомление текущей странице.
     *
     * ⚠️ Пустой фильтр означает «на всех страницах» — именно так его описывает
     * админка. Раньше здесь стоял return false, и уведомление без указанного
     * маршрута (самый обычный случай) не показывалось вообще никому: SQL-выборка
     * его находила, а этот фильтр молча выбрасывал.
     */
    protected function matchesRouteFilter(?string $filter, string $currentPath): bool
    {
        $filter = trim($filter ?? '');

        if ($filter === '') {
            return true;
        }

        $currentPath = '/' . ltrim($currentPath, '/');

        // Допускаем несколько путей через запятую: /about, /news/*
        foreach (explode(',', $filter) as $part) {
            $filterPath = '/' . ltrim(trim($part), '/');

            if ($filterPath === '/') {
                if ($currentPath === '/') {
                    return true;
                }
                continue;
            }

            if (str_contains($filterPath, '*')) {
                $pattern = '#^' . str_replace('\*', '.*', preg_quote($filterPath, '#')) . '$#i';
                if (preg_match($pattern, $currentPath)) {
                    return true;
                }
                continue;
            }

            if ($currentPath === $filterPath) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        return view('Notifications::frontend.list');
    }
}
