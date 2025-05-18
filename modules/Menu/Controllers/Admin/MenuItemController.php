<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

/**
 * 📦 Контроллер управления пунктами меню
 *
 * 🔹 Добавление и удаление пунктов меню
 * 🔹 Используется внутри конкретного меню
 */
class MenuItemController extends Controller
{
    /**
     * ➕ Метод store()
     *
     * 📌 Добавляет новый пункт в меню
     */
    public function store(Request $request, Menu $menu)
    {
        // 🧾 Валидация данных нового пункта
        $request->validate([
            'title' => 'required|string|max:255', // Название пункта
            'type' => 'required|in:url,page,category', // Тип: внешняя ссылка / страница / категория
            'url' => 'nullable|string|max:255', // Ссылка (если тип — url)
            'linked_id' => 'nullable|integer', // ID связанной сущности (если тип page/category)
            'meta_title' => 'nullable|string|max:255', // SEO-заголовок
            'meta_description' => 'nullable|string|max:255', // SEO-описание
            'meta_keywords' => 'nullable|string|max:255', // SEO-ключи
        ]);

        // 💾 Сохраняем пункт меню
        $menu->items()->create([
            'title' => $request->title,
            'type' => $request->type,
            'url' => $request->url,
            'linked_id' => $request->linked_id,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
        ]);

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('success', 'Пункт меню добавлен.');
    }

    /**
     * 🗑️ Метод destroy()
     *
     * ❌ Удаляет указанный пункт меню по ID
     */
    public function destroy(Request $request, Menu $menu, $itemId)
    {
        // 🔍 Проверка принадлежности пункта к текущему меню
        $item = MenuItem::where('menu_id', $menu->id)
                        ->where('id', $itemId)
                        ->firstOrFail();

        // 🧹 Удаляем пункт меню
        $item->delete();

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('success', 'Пункт меню удалён.');
    }
}
