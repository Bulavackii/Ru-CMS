<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔒 Middleware для блокировки доступа к /install, если CMS уже установлена
 */
class BlockIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // ⛔ Блокировка доступа к /install, если CMS уже установлена.
        // Раньше здесь был голый abort(404) — человек, случайно обновивший
        // страницу мастера (или вернувшийся на неё по старой вкладке/закладке)
        // после успешного завершения установки, упирался в пустой "Не найдено"
        // без единой подсказки, куда идти дальше. Мягкий редирект туда, где
        // реально есть смысл оказаться, гораздо дружелюбнее и не теряет
        // саму защиту (повторно пройти мастер всё равно нельзя).
        if (file_exists(install_lock_path())) {
            // Тот, кто только что закончил установку, возвращается сюда
            // сам: браузер переспрашивает оборванный GET /install/finish.
            // Отправлять его на форму входа нельзя — он уже вошёл, и
            // форма увела бы его в личный кабинет вместо итога.
            if (is_array($request->session()->get('install.summary'))) {
                return redirect()->route('install.done');
            }

            return redirect()->route('login')->with('status', __('install.errors.already_installed'));
        }

        return $next($request);
    }
}

