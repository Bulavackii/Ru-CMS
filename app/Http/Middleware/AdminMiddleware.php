<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🛡️ AdminMiddleware
 *
 * Ограничивает доступ к маршрутам только для администраторов.
 *
 * 🔐 Проверяет:
 * - авторизован ли пользователь (`auth()->check()`)
 * - является ли он администратором (`is_admin = true`)
 *
 * Если условия не выполнены — генерирует ошибку 403 (доступ запрещён).
 */
class AdminMiddleware
{
    /**
     * 🚦 handle()
     *
     * Обрабатывает входящий HTTP-запрос:
     * - ✅ Если пользователь администратор — пропускает дальше
     * - ❌ Иначе — возвращает ошибку 403 (доступ запрещён)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 🔐 Проверка: авторизован ли пользователь и админ ли он
        if (!auth()->check() || !auth()->user()->is_admin) {
            abort(403); // 🚫 Доступ запрещён
        }

        // ✅ Пропускаем дальше
        return $next($request);
    }
}
