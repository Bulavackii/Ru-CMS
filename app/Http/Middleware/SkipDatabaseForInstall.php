<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * 🔧 Middleware для пропуска проверок БД во время установки
 * 
 * Переключает драйвер сессий на 'file' для маршрутов установки,
 * чтобы не требовалось подключение к БД до завершения установки.
 * 
 * ВАЖНО: Этот middleware должен выполняться ДО StartSession middleware.
 */
class SkipDatabaseForInstall
{
    public function handle(Request $request, Closure $next)
    {
        // 💡 Если это маршрут установки, переключаем драйвер сессий на file
        if ($request->is('install*')) {
            // Переключаем драйвер сессий на file, чтобы не требовать БД
            // Это должно быть сделано до того, как StartSession попытается подключиться к БД
            Config::set('session.driver', 'file');
            
            // Также переопределяем connection, чтобы избежать попыток подключения
            Config::set('session.connection', null);
        }

        return $next($request);
    }
}
