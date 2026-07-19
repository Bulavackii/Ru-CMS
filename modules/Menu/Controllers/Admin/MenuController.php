<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

/**
 * 📂 Контроллер управления меню в админке.
 */
class MenuController extends Controller
{
    /** 📋 Список меню */
    public function index()
    {
        $menus = Menu::all();
        return view('Menu::admin.menu.index', compact('menus'));
    }

    /** ➕ Форма создания меню */
    public function create()
    {
        return view('Menu::admin.menu.create');
    }

    /** 💾 Сохранение нового меню */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'position' => 'required|in:header,footer,sidebar',
            'active'   => 'nullable|boolean',
        ]);

        Menu::create([
            'title'    => $request->title,
            'position' => $request->position,
            'active'   => $request->has('active'),
        ]);

        return redirect()->route('admin.menus.index')->with('success', '📁 Меню создано.');
    }

    /** ✏️ Редактирование меню и его пунктов */
    public function edit(Menu $menu)
    {
        $items = $menu->items()->with('children')->whereNull('parent_id')->get();
        return view('Menu::admin.menu.edit', compact('menu', 'items'));
    }

    /** 🔄 Вкл/выкл меню */
    public function toggle(Menu $menu)
    {
        $menu->active = ! $menu->active;
        $menu->save();

        return back()->with('success', 'Меню успешно обновлено.');
    }

    /** 💾 Сохранение порядка/вложенности (drag-and-drop) */
    public function updateOrder(Request $request, Menu $menu)
    {
        $orderData = $request->input('items', []);
        
        // Проверяем глубину вложенности перед сохранением
        $maxDepth = $this->checkMaxDepth($orderData, 0);
        if ($maxDepth > 2) { // 0, 1, 2 = максимум 3 уровня
            return response()->json([
                'success' => false,
                'message' => 'Максимальная глубина вложенности - 3 уровня. Текущая структура превышает лимит.'
            ], 422);
        }

        $this->saveMenuItemsOrder($orderData, null, $menu->id);
        return response()->json(['success' => true]);
    }

    /** Проверка максимальной глубины вложенности */
    private function checkMaxDepth(array $items, int $currentDepth): int
    {
        $maxDepth = $currentDepth;
        
        foreach ($items as $itemData) {
            if (!empty($itemData['children']) && is_array($itemData['children'])) {
                $childDepth = $this->checkMaxDepth($itemData['children'], $currentDepth + 1);
                $maxDepth = max($maxDepth, $childDepth);
            }
        }
        
        return $maxDepth;
    }

    /** Рекурсивно сохраняем порядок */
    private function saveMenuItemsOrder(array $items, $parentId = null, $menuId = null, int $depth = 0)
    {
        // Защита от превышения глубины
        if ($depth > 2) {
            return;
        }

        foreach ($items as $index => $itemData) {
            $item = MenuItem::find($itemData['id'] ?? null);
            if (! $item) continue;

            // Проверяем, что пункт принадлежит меню
            if ($item->menu_id != $menuId) {
                continue;
            }

            $item->update([
                'order'     => $index,
                'parent_id' => $parentId,
                'menu_id'   => $menuId ?? $item->menu_id,
            ]);

            if (!empty($itemData['children']) && is_array($itemData['children'])) {
                $this->saveMenuItemsOrder($itemData['children'], $item->id, $menuId ?? $item->menu_id, $depth + 1);
            }
        }
    }

    /** 🗑️ Удаление меню (безопасно удаляем всю ветку пунктов) */
    public function destroy(Menu $menu)
    {
        // Удаляем все ветки от корней, затем само меню
        $roots = $menu->items()->with('children')->whereNull('parent_id')->get();
        foreach ($roots as $root) {
            $this->deleteBranch($root);
        }

        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Меню успешно удалено.');
    }

    /** Рекурсивное удаление ветки (используется и тут, и в MenuItemController) */
    private function deleteBranch(MenuItem $item): void
    {
        foreach ($item->children as $child) {
            $this->deleteBranch($child);
        }
        $item->delete();
    }
}
