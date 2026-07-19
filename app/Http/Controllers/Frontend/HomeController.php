<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Categories\Models\Category;
use Modules\News\Models\News;
use Modules\Slideshow\Models\Slideshow;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\Page;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Показать приветственную страницу, если установка завершена и нет контента
        if (file_exists(storage_path('install.lock')) && 
            !auth()->check() && 
            !\Modules\News\Models\News::where('published', true)->exists() &&
            !\Modules\Menu\Models\Page::where('published', true)->where('show_on_homepage', true)->exists() &&
            !$request->cookie('rucms_welcome_hidden')) {
            return view('frontend.welcome');
        }

        $user = Auth::user();

        // Кэшируем статические данные на 1 час с оптимизацией запросов
        $categories = Cache::remember('home_categories', 3600, function () {
            return Category::select('id', 'title', 'slug')->get();
        });

        $slideshows = Cache::remember('home_slideshows', 3600, function () {
            return Slideshow::with(['items' => function ($q) {
                $q->select('id', 'slideshow_id', 'file_path', 'media_type', 'caption', 'link', 'order')
                  ->orderBy('order');
            }])
            ->where('published', true)
            ->select('id', 'title', 'position', 'slug')
            ->get();
        });

        $menus = Cache::remember('home_menus', 3600, function () {
            return Menu::with(['items' => function ($q) {
                $q->whereNull('parent_id')
                  ->select('id', 'menu_id', 'title', 'url', 'order', 'parent_id')
                  ->orderBy('order')
                  ->with(['children' => function ($q) {
                      $q->select('id', 'menu_id', 'title', 'url', 'order', 'parent_id')
                        ->orderBy('order');
                  }]);
            }])->select('id', 'title', 'position', 'active')
               ->where('active', true)
               ->get();
        });

        $homePages = Cache::remember('home_pages', 3600, function () {
            return Page::select('id', 'title', 'slug', 'content', 'homepage_order')
                ->where('published', true)
                ->where('show_on_homepage', true)
                ->orderBy('homepage_order')
                ->get();
        });

        // Загрузка шаблонов с оптимизированными запросами
        $templates = $this->loadTemplates($request);

        return view('frontend.home', [
            'user' => $user,
            'categories' => $categories,
            'templates' => $templates,
            'slideshows' => $slideshows,
            'homePages' => $homePages,
            'menus' => $menus,
        ]);
    }

    private function loadTemplates(Request $request): array
    {
        $templateKeys = [
            'about', 'default', 'ourworks', 'release',
            'base-php', 'base-html', 'base-css', 'base-js',
            'products', 'reviews', 'faq', 'gallery', 'slideshow', 'test'
        ];

        $cart = session('cart', []);
        $templates = [];

        foreach ($templateKeys as $key) {
            // Используем кэширование для каждого шаблона
            $cacheKey = "template_{$key}_" . md5(serialize($request->all()));

            $templates[$key] = Cache::remember($cacheKey, 300, function () use ($key, $cart, $request) {
                // Оптимизация: выбираем только нужные поля и используем eager loading
                $query = News::with(['categories' => function ($q) {
                        $q->select('categories.id', 'categories.title', 'categories.slug');
                    }])
                    ->select('id', 'title', 'slug', 'content', 'template', 'price', 'stock', 'is_promo', 'created_at', 'updated_at')
                    ->where('published', true)
                    ->where('template', $key);

                // Фильтрация по категории
                if ($request->filled("category_$key")) {
                    $query->whereHas('categories', function ($q) use ($key, $request) {
                        $q->where('categories.id', $request->input("category_$key"));
                    });
                }

                // Получаем все items за один запрос
                $allItems = $query->orderByDesc('id')->get();

                // Обновляем stock с учетом корзины
                $allItems->transform(function ($item) use ($cart) {
                    $cartQty = isset($cart[$item->id]) ? $cart[$item->id]['qty'] : 0;
                    $item->stock = is_null($item->stock) ? null : max($item->stock - $cartQty, 0);
                    return $item;
                });

                // Пагинация
                $perPage = 9;
                $currentPage = LengthAwarePaginator::resolveCurrentPage($key . '_page');
                $offset = ($currentPage - 1) * $perPage;
                $items = $allItems->slice($offset, $perPage)->values();

                return new LengthAwarePaginator(
                    $items,
                    $allItems->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'pageName' => $key . '_page']
                );
            });
        }

        return $templates;
    }
}
