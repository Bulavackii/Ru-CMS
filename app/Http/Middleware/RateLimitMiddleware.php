<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Rate limiting для админки
        if ($request->is('admin/*')) {
            $key = 'admin:' . ($request->user()?->id ?? $request->ip());
            $executed = RateLimiter::attempt(
                $key,
                60, // 60 запросов в минуту
                function () {
                    // Логика при превышении лимита
                },
                1 // время блокировки в минутах
            );

            if ($executed > 60) {
                return response()->json([
                    'message' => 'Превышен лимит запросов. Попробуйте позже.'
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }
        }

        // Rate limiting для публичных форм
        if ($request->is('login', 'register', 'forgot-password')) {
            $key = 'auth:' . $request->ip();
            $executed = RateLimiter::attempt(
                $key,
                5, // 5 попыток в минуту
                function () {
                    // Логика при превышении лимита
                },
                1 // время блокировки в минутах
            );

            if ($executed > 5) {
                return response()->json([
                    'message' => 'Слишком много попыток. Попробуйте через минуту.'
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }
        }

        return $next($request);
    }
}
