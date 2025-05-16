<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

class MenuItemController extends Controller
{
    public function store(Request $request, Menu $menu)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:url,page,category',
            'url' => 'nullable|string|max:255',
            'linked_id' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $menu->items()->create([
            'title' => $request->title,
            'type' => $request->type,
            'url' => $request->url,
            'linked_id' => $request->linked_id,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
        ]);

        return redirect()->route('admin.menus.edit', $menu)->with('success', 'Пункт меню добавлен.');
    }

    public function destroy(Request $request, Menu $menu, $itemId)
    {
        $item = MenuItem::where('menu_id', $menu->id)->where('id', $itemId)->firstOrFail();
        $item->delete();

        return redirect()->route('admin.menus.edit', $menu)->with('success', 'Пункт меню удалён.');
    }
}
