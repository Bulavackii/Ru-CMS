<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::all();
        return view('Menu::admin.menu.index', compact('menus'));
    }

    public function create()
    {
        return view('Menu::admin.menu.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'position' => 'required|in:header,footer,sidebar',
            'active' => 'nullable|boolean',
        ]);

        Menu::create([
            'title' => $request->title,
            'position' => $request->position,
            'active' => $request->has('active'),
        ]);

        return redirect()->route('admin.menus.index')->with('success', 'Меню создано.');
    }

    public function edit(Menu $menu)
    {
        $items = $menu->items()->with('children')->whereNull('parent_id')->get();
        return view('Menu::admin.menu.edit', compact('menu', 'items'));
    }

    public function toggle(Menu $menu)
    {
        $menu->active = !$menu->active;
        $menu->save();

        return back()->with('success', 'Меню успешно обновлено.');
    }

    public function updateOrder(Request $request, Menu $menu)
    {
        $orderData = $request->input('items');
        $this->saveMenuItemsOrder($orderData, null, $menu->id);
        return response()->json(['success' => true]);
    }

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

                if (isset($itemData['children']) && is_array($itemData['children'])) {
                    $this->saveMenuItemsOrder($itemData['children'], $item->id, $menuId ?? $item->menu_id);
                }
            }
        }
    }

    public function storeItem(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:url,page,category',
            'url' => 'nullable|string',
            'linked_id' => 'nullable|integer',
            'parent_id' => 'nullable|exists:menu_items,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $menu->items()->create($validated);
        return back()->with('success', 'Пункт меню добавлен.');
    }
}
