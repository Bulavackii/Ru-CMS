<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🚦 ApiRateLimit - Ограничение частоты запросов к API
 */
class ApiRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'api:' . $request->ip();
        $maxAttempts = 60; // 60 запросов
        $decayMinutes = 1; // в минуту

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Слишком много запросов',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Добавляем заголовки с информацией о лимитах
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $maxAttempts - RateLimiter::attempts($key));

        return $response;
    }
}

