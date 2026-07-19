<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔧 Middleware для редиректа на установку, если система не установлена
 */
class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Если система не установлена и это не маршрут установки
        if (!File::exists(storage_path('install.lock')) && !$request->is('install*')) {
            return redirect('/install');
        }

        return $next($request);
    }
}







