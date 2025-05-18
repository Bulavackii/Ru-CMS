<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\News\Models\News;

/**
 * 🔍 FrontendSearchController
 *
 * Контроллер глобального поиска на клиентской части сайта.
 *
 * Позволяет:
 * - искать опубликованные новости по заголовку и содержимому
 * - отображать результаты в виде пагинируемого списка
 */
class FrontendSearchController extends Controller
{
    /**
     * 🔎 index()
     *
     * Обрабатывает GET-запрос с параметром `q` и выполняет поиск.
     *
     * 🔍 Ищет по:
     * - заголовку (`title`)
     * - содержимому (`content`)
     *
     * ❗ Только опубликованные записи (published = true)
     * 📄 Возвращает представление с результатами
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 📝 Запрос пользователя (поисковая строка)
        $query = $request->input('q');

        // 📦 Инициализируем пустую коллекцию результатов
        $results = collect();

        // 🔍 Выполняем поиск только если есть строка запроса
        if ($query) {
            $results = News::where('published', true)
                ->where(function ($qB) use ($query) {
                    $qB->where('title', 'like', '%' . $query . '%')
                       ->orWhere('content', 'like', '%' . $query . '%');
                })
                ->orderByDesc('created_at')
                ->paginate(12); // 📄 Пагинация: 12 записей на страницу
        }

        // 🖼️ Отображаем результаты в шаблоне
        return view('frontend.search.results', compact('results', 'query'));
    }
}
