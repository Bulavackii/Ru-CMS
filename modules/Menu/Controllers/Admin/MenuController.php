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

    public function edit(Menu $menu)
    {
        $items = $menu->items()->with('children')->whereNull('parent_id')->get();
        return view('Menu::admin.menu.edit', compact('menu', 'items'));
    }

    public function updateOrder(Request $request, Menu $menu)
    {
        $orderData = $request->input('items');
        $this->saveMenuItemsOrder($orderData, null);
        return response()->json(['success' => true]);
    }

    private function saveMenuItemsOrder(array $items, $parentId = null)
    {
        foreach ($items as $index => $itemData) {
            $item = MenuItem::find($itemData['id']);
            if ($item) {
                $item->update([
                    'order' => $index,
                    'parent_id' => $parentId,
                ]);
                if (isset($itemData['children'])) {
                    $this->saveMenuItemsOrder($itemData['children'], $item->id);
                }
            }
        }
    }
}
