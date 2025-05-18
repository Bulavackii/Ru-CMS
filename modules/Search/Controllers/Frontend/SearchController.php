<?php

namespace Modules\Search\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Product;

class SearchController extends Controller
{
    /**
     * 🔍 Обработка запроса поиска на клиентской части сайта
     */
    public function index(Request $request)
    {
        // 📥 Получаем поисковый запрос из строки запроса
        $query = $request->input('q');

        // 📰 Поиск по статьям (таблица posts)
        $posts = Post::where('title', 'like', "%$query%")
            ->orWhere('content', 'like', "%$query%")
            ->get();

        // 🛒 Поиск по товарам (таблица products)
        $products = Product::where('name', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->get();

        // 📄 Возвращаем результат в Blade-шаблон с переменными
        return view('Search::frontend.index', compact('query', 'posts', 'products'));
    }
}
