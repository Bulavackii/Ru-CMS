<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 📦 CompressResponse - Сжатие HTTP ответов
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем маршруты установки
        if ($request->is('install*')) {
            return $next($request);
        }

        $response = $next($request);

        // Сжимаем только HTML, CSS, JS, JSON, XML
        $contentType = $response->headers->get('Content-Type', '');
        $compressibleTypes = [
            'text/html',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml',
            'text/xml',
        ];

        $shouldCompress = false;
        foreach ($compressibleTypes as $type) {
            if (str_contains($contentType, $type)) {
                $shouldCompress = true;
                break;
            }
        }

        if (!$shouldCompress || $response->getContent() === false) {
            return $response;
        }

        // Проверяем поддержку gzip
        if (str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            $content = $response->getContent();
            $compressed = gzencode($content, 6); // Уровень сжатия 6 (баланс)

            if ($compressed !== false && strlen($compressed) < strlen($content)) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Content-Length', strlen($compressed));
                $response->headers->set('Vary', 'Accept-Encoding');
            }
        }

        return $response;
    }
}

