<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SecurityService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🛡️ SecurityMiddleware - Комплексная защита запросов
 */
class SecurityMiddleware
{
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем маршруты установки
        if ($request->is('install*')) {
            return $next($request);
        }

        $ip = $request->ip();

        // Проверка блокировки IP
        if ($this->securityService->isIpBlocked($ip)) {
            Log::warning('Blocked IP attempt', ['ip' => $ip]);
            abort(403, 'Ваш IP адрес заблокирован');
        }

        // Проверка входных данных на SQL injection
        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                if ($this->securityService->detectSqlInjection($value)) {
                    $this->securityService->blockIp($ip, 60);
                    Log::warning('SQL injection blocked', [
                        'ip' => $ip,
                        'key' => $key,
                        'value' => substr($value, 0, 100)
                    ]);
                    abort(403, 'Обнаружена попытка SQL инъекции');
                }

                // Проверка на XSS
                if ($this->securityService->detectXss($value)) {
                    Log::warning('XSS attempt blocked', [
                        'ip' => $ip,
                        'key' => $key
                    ]);
                    abort(403, 'Обнаружена попытка XSS атаки');
                }
            }
        }

        return $next($request);
    }
}

