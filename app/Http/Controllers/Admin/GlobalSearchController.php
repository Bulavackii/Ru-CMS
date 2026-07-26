<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminSections;
use Illuminate\Http\Request;
use Modules\News\Models\News;
use Modules\Menu\Models\Page;
use App\Models\User;
use Modules\Categories\Models\Category;
use Modules\Menu\Models\Menu;
use Illuminate\Support\Facades\Log;

/**
 * 🔍 GlobalSearchController - Глобальный поиск в админке
 */
class GlobalSearchController extends Controller
{
    /**
     * 🔍 Глобальный поиск
     */
    public function search(Request $request)
    {
        try {
            $query = trim($request->input('q', ''));

            // mb_strlen, а не strlen: одна кириллическая буква — это ДВА байта,
            // поэтому по запросу «т» проверка «минимум 2 символа» проходила и
            // сервер честно искал по одной букве, отдавая мусор
            if (mb_strlen($query) < 2) {
                return response()->json(['results' => []]);
            }

            $results = [];

            // ILIKE на Postgres, иначе LIKE: без этого поиск был регистрозависимым
            $like = search_like();

            // Разделы панели — первыми: перейти в «Темы» или «Локализацию»
            // нужно чаще, чем найти запись с таким словом в заголовке.
            // Список общий с сайдбаром (App\Support\AdminSections).
            foreach (AdminSections::search($query) as $section) {
                $results[] = [
                    'type' => 'Раздел',
                    'title' => $section['label'],
                    'subtitle' => $section['group'],
                    'url' => $section['url'],
                    'icon' => 'fas fa-compass text-indigo-500',
                ];
            }

            // Поиск новостей
            try {
                $news = News::where('title', $like, "%{$query}%")
                    ->orWhere('content', $like, "%{$query}%")
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
                Log::warning('Ошибка поиска новостей: ' . $e->getMessage());
            }

            // Поиск страниц
            try {
                $pages = Page::where('title', $like, "%{$query}%")
                    ->orWhere('content', $like, "%{$query}%")
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
                Log::warning('Ошибка поиска страниц: ' . $e->getMessage());
            }

            // Поиск пользователей
            try {
                $users = User::where('name', $like, "%{$query}%")
                    ->orWhere('email', $like, "%{$query}%")
                    ->limit(5)
                    ->get();
                
                foreach ($users as $item) {
                    $results[] = [
                        'type' => 'Пользователь',
                        'title' => $item->name,
                        // E-mail — подпись, а не часть заголовка: так видно, кого
                        // именно нашли, когда имена совпадают
                        'subtitle' => $item->email,
                        // Раньше вело на список с ?search= — то есть «найди сам
                        // ещё раз». Ведём сразу на карточку найденного.
                        'url' => route('admin.users.edit', $item->id),
                        'icon' => 'fas fa-user text-purple-500',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка поиска пользователей: ' . $e->getMessage());
            }

            // Поиск категорий
            try {
                $categories = Category::where('title', $like, "%{$query}%")
                    ->limit(5)
                    ->get();

                foreach ($categories as $item) {
                    $results[] = [
                        'type' => 'Категория',
                        'title' => $item->title,
                        'url' => route('admin.categories.edit', $item->id),
                        'icon' => 'fas fa-folder text-orange-500',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка поиска категорий: ' . $e->getMessage());
            }

            // Поиск меню
            try {
                $menus = Menu::where('title', $like, "%{$query}%")
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
                Log::warning('Ошибка поиска меню: ' . $e->getMessage());
            }

            return response()->json(['results' => $results]);
        } catch (\Exception $e) {
            Log::error('Ошибка глобального поиска: ' . $e->getMessage());
            return response()->json(['results' => [], 'error' => 'Произошла ошибка при поиске'], 500);
        }
    }
}

