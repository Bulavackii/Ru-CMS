<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;

/**
 * 📝 Middleware для аудит-лога
 */
class AuditLog
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем маршруты установки
        if ($request->is('install*')) {
            return $next($request);
        }

        $response = $next($request);

        // Логировать только важные действия в админке
        if ($request->is('admin/*') && auth()->check()) {
            $this->logAction($request, $response);
        }

        return $response;
    }

    private function logAction(Request $request, Response $response): void
    {
        // Логировать только POST, PUT, DELETE запросы
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        // Исключить некоторые маршруты
        $excluded = ['admin/notifications', 'admin/search'];
        foreach ($excluded as $exclude) {
            if (str_contains($request->path(), $exclude)) {
                return;
            }
        }

        try {
            ActivityLog::log(
                'admin_action',
                auth()->user(),
                "{$request->method()} {$request->path()}",
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => $response->getStatusCode(),
                ]
            );
        } catch (\Exception $e) {
            // Не прерывать выполнение при ошибке логирования
            \Log::warning('Failed to log audit action', ['error' => $e->getMessage()]);
        }
    }
}

