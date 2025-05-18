<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

/**
 * 📂 Контроллер управления меню в админке
 *
 * 🔧 Поддерживает:
 * - создание и редактирование меню
 * - добавление пунктов меню
 * - сортировку с вложенностью (drag-and-drop)
 * - включение/отключение меню
 */
class MenuController extends Controller
{
    /**
     * 📋 Метод index()
     *
     * 🔽 Отображает список всех меню
     */
    public function index()
    {
        $menus = Menu::all(); // Получаем все меню
        return view('Menu::admin.menu.index', compact('menus'));
    }

    /**
     * ➕ Метод create()
     *
     * 🔧 Форма создания нового меню
     */
    public function create()
    {
        return view('Menu::admin.menu.create');
    }

    /**
     * 💾 Метод store()
     *
     * 📥 Сохраняет новое меню в БД
     */
    public function store(Request $request)
    {
        // 📑 Валидация данных формы
        $request->validate([
            'title' => 'required|string|max:255',
            'position' => 'required|in:header,footer,sidebar', // Позиции, где может отображаться меню
            'active' => 'nullable|boolean',
        ]);

        // ✅ Создание меню
        Menu::create([
            'title' => $request->title,
            'position' => $request->position,
            'active' => $request->has('active'),
        ]);

        return redirect()->route('admin.menus.index')->with('success', '📁 Меню создано.');
    }

    /**
     * ✏️ Метод edit()
     *
     * 📃 Форма редактирования меню и его пунктов
     */
    public function edit(Menu $menu)
    {
        // 📂 Загружаем корневые пункты меню с потомками
        $items = $menu->items()->with('children')->whereNull('parent_id')->get();

        return view('Menu::admin.menu.edit', compact('menu', 'items'));
    }

    /**
     * 🔄 Метод toggle()
     *
     * ⚙️ Включает/отключает отображение меню
     */
    public function toggle(Menu $menu)
    {
        $menu->active = !$menu->active; // Переключаем флаг активности
        $menu->save();

        return back()->with('success', 'Меню успешно обновлено.');
    }

    /**
     * 🧩 Метод updateOrder()
     *
     * 💡 Сохраняет новый порядок и структуру пунктов меню (drag-and-drop)
     */
    public function updateOrder(Request $request, Menu $menu)
    {
        $orderData = $request->input('items'); // JSON с порядком и вложенностью
        $this->saveMenuItemsOrder($orderData, null, $menu->id); // Рекурсивное сохранение
        return response()->json(['success' => true]);
    }

    /**
     * ♻️ Вспомогательный метод saveMenuItemsOrder()
     *
     * 🔁 Сохраняет порядок и иерархию пунктов меню
     */
    private function saveMenuItemsOrder(array $items, $parentId = null, $menuId = null)
    {
        foreach ($items as $index => $itemData) {
            $item = MenuItem::find($itemData['id']);

            if ($item) {
                $item->update([
                    'order' => $index,
                    'parent_id' => $parentId,
                    'menu_id' => $menuId ?? $item->menu_id,
                ]);

                // 🔽 Рекурсивно сохраняем детей
                if (isset($itemData['children']) && is_array($itemData['children'])) {
                    $this->saveMenuItemsOrder($itemData['children'], $item->id, $menuId ?? $item->menu_id);
                }
            }
        }
    }

    /**
     * ➕ Метод storeItem()
     *
     * 📌 Добавляет новый пункт меню к текущему меню
     */
    public function storeItem(Request $request, Menu $menu)
    {
        // 📑 Валидация нового пункта меню
        $validated = $request->validate([
            'title' => 'required|string|max:255', // Название пункта
            'type' => 'required|in:url,page,category', // Тип ссылки
            'url' => 'nullable|string', // Внешняя ссылка
            'linked_id' => 'nullable|integer', // Привязанный ID (например, ID страницы)
            'parent_id' => 'nullable|exists:menu_items,id', // Родительский элемент
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        // 💾 Создаём пункт
        $menu->items()->create($validated);

        return back()->with('success', 'Пункт меню добавлен.');
    }

    /**
     * 🗑️ Метод destroy()
     *
     * ❌ Удаляет выбранное меню и все связанные пункты меню
     */
    public function destroy(Menu $menu)
    {
        // 🧹 Удаляем все связанные пункты меню
        $menu->items()->delete();

        // 🗑️ Удаляем само меню
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Меню успешно удалено.');
    }
}
