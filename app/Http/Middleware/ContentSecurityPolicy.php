<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔒 Content Security Policy Middleware
 * 
 * Защита от XSS атак через CSP заголовки
 */
class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // CSP политика
        $policy = $this->buildPolicy($request);
        
        if (!empty($policy)) {
            $response->headers->set('Content-Security-Policy', $policy);
            
            // Отчеты о нарушениях (только в development)
            if (app()->environment('local')) {
                try {
                    if (\Illuminate\Support\Facades\Route::has('csp.report')) {
                        $reportUri = route('csp.report');
                        $response->headers->set('Content-Security-Policy-Report-Only', $policy . "; report-uri {$reportUri}");
                    }
                } catch (\Exception $e) {
                    // Игнорируем, если маршрут не определен
                }
            }
        }

        return $response;
    }

    /**
     * Построить CSP политику
     */
    protected function buildPolicy(Request $request): string
    {
        $policies = [];

        // Для админки - более мягкие правила (нужен inline JS для редакторов)
        if ($request->is('admin/*')) {
            $policies = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https: blob:",
                "font-src 'self' data:",
                "connect-src 'self' https://api.telegram.org",
                "frame-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ];
        } else {
            // Для фронтенда - строгие правила
            $policies = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "frame-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ];
        }

        // Добавить кастомные политики из конфига
        $customPolicies = config('csp.policies', []);
        if (!empty($customPolicies)) {
            $policies = array_merge($policies, $customPolicies);
        }

        return implode('; ', $policies);
    }
}

