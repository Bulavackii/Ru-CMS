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
        // ⛔ Блокировка доступа к /install, если CMS уже установлена
        if (file_exists(storage_path('install.lock'))) {
            abort(404, 'Установка уже завершена');
        }

        return $next($request);
    }
}

