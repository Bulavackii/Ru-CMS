<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

/**
 * 📦 Контроллер управления пунктами меню
 *
 * - Добавление пункта
 * - Удаление пункта (с безопасным удалением ветки)
 */
class MenuItemController extends Controller
{
    /**
     * ➕ Добавляет новый пункт в меню.
     */
    public function store(Request $request, Menu $menu)
    {
        $validated = $this->validateMenuItem($request, $menu);

        // Проверка глубины вложенности
        if ($validated['parent_id']) {
            $parent = MenuItem::find($validated['parent_id']);
            if ($parent && !$parent->canHaveChildren()) {
                return back()->withErrors([
                    'parent_id' => 'Максимальная глубина вложенности - 3 уровня. Выбранный родитель уже на максимальном уровне.'
                ])->withInput();
            }
        }

        $menu->items()->create($validated);

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('success', 'Пункт меню добавлен.');
    }

    /**
     * ✏️ Обновляет существующий пункт меню.
     */
    public function update(Request $request, Menu $menu, MenuItem $item)
    {
        // Проверяем, что пункт принадлежит меню
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }

        $validated = $this->validateMenuItem($request, $menu, $item);

        // Проверка глубины вложенности при изменении родителя
        if ($validated['parent_id'] && $validated['parent_id'] != $item->parent_id) {
            $parent = MenuItem::find($validated['parent_id']);
            if ($parent && !$parent->canHaveChildren()) {
                return back()->withErrors([
                    'parent_id' => 'Максимальная глубина вложенности - 3 уровня. Выбранный родитель уже на максимальном уровне.'
                ])->withInput();
            }
        }

        // Проверка, что не пытаемся сделать пункт родителем самого себя
        if ($validated['parent_id'] == $item->id) {
            return back()->withErrors([
                'parent_id' => 'Пункт не может быть родителем самого себя.'
            ])->withInput();
        }

        // Проверка циклических ссылок (пункт не может быть родителем своего потомка)
        if ($validated['parent_id']) {
            $descendants = $this->getDescendants($item);
            if (in_array($validated['parent_id'], $descendants)) {
                return back()->withErrors([
                    'parent_id' => 'Пункт не может быть родителем своего потомка.'
                ])->withInput();
            }
        }

        $item->update($validated);

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('success', 'Пункт меню обновлён.');
    }

    /**
     * Валидация данных пункта меню
     */
    private function validateMenuItem(Request $request, Menu $menu, ?MenuItem $item = null): array
    {
        $rules = [
            'title'            => 'required|string|max:255',
            'type'             => 'required|in:url,page,category',
            'url'              => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'url' && $value) {
                        // Проверяем, что это валидный URL или относительный путь
                        if (!filter_var($value, FILTER_VALIDATE_URL) && !str_starts_with($value, '/')) {
                            $fail('URL должен быть валидным адресом (начинается с http:// или https://) или относительным путём (начинается с /).');
                        }
                    }
                },
            ],
            'linked_id'        => [
                'nullable',
                'integer',
                Rule::requiredIf(function () use ($request) {
                    return in_array($request->type, ['page', 'category']);
                }),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'page' && $value) {
                        if (!\Modules\Menu\Models\Page::where('id', $value)->exists()) {
                            $fail('Выбранная страница не существует.');
                        }
                    }
                    if ($request->type === 'category' && $value) {
                        if (!\Modules\Categories\Models\Category::where('id', $value)->exists()) {
                            $fail('Выбранная категория не существует.');
                        }
                    }
                },
            ],
            'parent_id'        => [
                'nullable',
                'integer',
                Rule::exists('menu_items', 'id')->where('menu_id', $menu->id),
            ],
            'active'           => 'nullable|boolean',
            'icon'             => 'nullable|string|max:100',
            'css_class'        => 'nullable|string|max:255',
            'target'           => 'nullable|in:_self,_blank',
            'rel'              => 'nullable|string|max:100',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
        ];

        $validated = $request->validate($rules);

        // Обработка чекбокса active
        $validated['active'] = $request->has('active');

        // Если тип url, очищаем linked_id
        if ($validated['type'] === 'url') {
            $validated['linked_id'] = null;
        }

        // Если тип page или category, очищаем url
        if (in_array($validated['type'], ['page', 'category'])) {
            $validated['url'] = null;
        }

        return $validated;
    }

    /**
     * Получить всех потомков пункта меню (для проверки циклических ссылок)
     */
    private function getDescendants(MenuItem $item): array
    {
        $descendants = [];
        foreach ($item->children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->getDescendants($child));
        }
        return $descendants;
    }

    /**
     * 🗑️ Удаляет указанный пункт меню (и его потомков).
     */
    public function destroy(Request $request, Menu $menu, $itemId)
    {
        // Берём пункт вместе с детьми и проверяем принадлежность к меню
        $item = MenuItem::with('children')
            ->where('menu_id', $menu->id)
            ->where('id', $itemId)
            ->firstOrFail();

        $this->deleteBranch($item);

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('success', 'Пункт меню удалён.');
    }

    /**
     * Рекурсивное удаление ветки пункта меню.
     */
    private function deleteBranch(MenuItem $item): void
    {
        foreach ($item->children as $child) {
            $this->deleteBranch($child);
        }
        $item->delete();
    }
}
