<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🎉 Middleware для показа приветственной страницы после установки
 */
class ShowWelcomePage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Показывать приветственную страницу только:
        // 1. На главной странице
        // 2. Если установка завершена
        // 3. Если пользователь не авторизован
        // 4. Если нет контента на главной странице
        
        if ($request->is('/') && 
            file_exists(storage_path('install.lock')) && 
            !auth()->check() &&
            $this->shouldShowWelcome()) {
            
            return response()->view('frontend.welcome', [
                'showWelcome' => true,
            ]);
        }

        return $next($request);
    }

    /**
     * Проверить, нужно ли показывать приветственную страницу
     */
    private function shouldShowWelcome(): bool
    {
        // Проверяем, есть ли опубликованный контент
        $hasContent = \Modules\News\Models\News::where('published', true)->exists();
        $hasPages = \Modules\Menu\Models\Page::where('published', true)->where('show_on_homepage', true)->exists();
        
        // Показываем приветствие, если нет контента
        return !$hasContent && !$hasPages;
    }
}

