<?php

namespace Modules\Menu\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\Menu\Models\Page;

/**
 * 🌐 Контроллер отображения страниц на клиентской части сайта
 *
 * 🔹 Отображает опубликованные страницы по slug
 * 🔹 Передаёт SEO-мета-данные во view
 */
class PageController extends Controller
{
    /**
     * 📄 Метод show()
     *
     * 🔍 Находит страницу по `slug` и выводит её на фронтенде
     *
     * @param string $slug — уникальный адрес страницы
     */
    public function show($slug)
    {
        // 🔎 Ищем опубликованную страницу по slug
        $page = Page::where('slug', $slug)
                    ->where('published', true)
                    ->firstOrFail();

        // 📤 Передаём страницу и SEO-данные в шаблон
        return view('Menu::frontend.page', [
            'page' => $page,
            'title' => $page->meta_title ?? $page->title, // 🧠 Заголовок страницы
            'meta_description' => $page->meta_description, // 📝 Meta Description
            'meta_keywords' => $page->meta_keywords,       // 🏷️ Meta Keywords
        ]);
    }
}
