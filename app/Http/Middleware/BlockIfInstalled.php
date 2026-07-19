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
        if (file_exists(storage_path('install.lock'))) {
            return redirect()->route('login')->with(
                'status',
                'Установка уже завершена. Мастер установки больше недоступен — войдите в панель управления.'
            );
        }

        return $next($request);
    }
}

