<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SkipDatabaseForInstall
{
    public function handle(Request $request, Closure $next)
    {
        // 💡 Можно оставить эту проверку, если нужно обходить DB только на этапе /install
        if ($request->is('install*')) {
            // Тут можно настроить специфические параметры, если потребуется
            // Например, отключить проверки моделей, инициализировать env-переменные и т.п.
        }

        return $next($request);
    }
}
