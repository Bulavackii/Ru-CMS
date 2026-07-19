<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\News\Models\News;
use Modules\Menu\Models\Page;
use App\Models\User;
use Modules\Categories\Models\Category;
use Modules\Menu\Models\Menu;

/**
 * 🔍 GlobalSearchController - Глобальный поиск в админке
 */
class GlobalSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 🔍 Глобальный поиск
     */
    public function search(Request $request)
    {
        try {
            $query = trim($request->input('q', ''));
            
            if (strlen($query) < 2) {
                return response()->json(['results' => []]);
            }

            $results = [];

            // Поиск новостей
            try {
                $news = News::where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->limit(5)
                    ->get();
                
                foreach ($news as $item) {
                    $results[] = [
                        'type' => 'Новость',
                        'title' => $item->title,
                        'url' => route('admin.news.edit', $item->id),
                        'icon' => 'fas fa-newspaper text-blue-500',
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Ошибка поиска новостей: ' . $e->getMessage());
            }

            // Поиск страниц
            try {
                $pages = Page::where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->limit(5)
                    ->get();
                
                foreach ($pages as $item) {
                    $results[] = [
                        'type' => 'Страница',
                        'title' => $item->title,
                        'url' => route('admin.pages.edit', $item->id),
                        'icon' => 'fas fa-file text-green-500',
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Ошибка поиска страниц: ' . $e->getMessage());
            }

            // Поиск пользователей
            try {
                $users = User::where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->limit(5)
                    ->get();
                
                foreach ($users as $item) {
                    $results[] = [
                        'type' => 'Пользователь',
                        'title' => $item->name . ' (' . $item->email . ')',
                        'url' => route('admin.users.index') . '?search=' . urlencode($query),
                        'icon' => 'fas fa-user text-purple-500',
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Ошибка поиска пользователей: ' . $e->getMessage());
            }

            // Поиск категорий
            try {
                $categories = Category::where('name', 'like', "%{$query}%")
                    ->limit(5)
                    ->get();
                
                foreach ($categories as $item) {
                    $results[] = [
                        'type' => 'Категория',
                        'title' => $item->name,
                        'url' => route('admin.categories.edit', $item->id),
                        'icon' => 'fas fa-folder text-orange-500',
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Ошибка поиска категорий: ' . $e->getMessage());
            }

            // Поиск меню
            try {
                $menus = Menu::where('title', 'like', "%{$query}%")
                    ->limit(5)
                    ->get();
                
                foreach ($menus as $item) {
                    $results[] = [
                        'type' => 'Меню',
                        'title' => $item->title,
                        'url' => route('admin.menus.edit', $item->id),
                        'icon' => 'fas fa-bars text-indigo-500',
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Ошибка поиска меню: ' . $e->getMessage());
            }

            return response()->json(['results' => $results]);
        } catch (\Exception $e) {
            \Log::error('Ошибка глобального поиска: ' . $e->getMessage());
            return response()->json(['results' => [], 'error' => 'Произошла ошибка при поиске'], 500);
        }
    }
}

