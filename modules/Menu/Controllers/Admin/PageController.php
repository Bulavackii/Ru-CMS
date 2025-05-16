<?php

namespace Modules\Menu\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Menu\Models\Page;
use App\Models\Category;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('categories')->orderByDesc('id')->get();
        return view('Menu::admin.pages.index', compact('pages'));
    }

    public function create()
    {
        $categories = Category::all();
        $page = new Page(['published' => true, 'show_on_homepage' => false, 'homepage_order' => 0]);

        return view('Menu::admin.pages.create', compact('categories', 'page'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages',
            'content' => 'nullable|string',
            'published' => 'boolean',
            'show_on_homepage' => 'boolean',
            'homepage_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'categories' => 'array',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']) . '-' . uniqid();
        $page = Page::create($data);
        $page->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.pages.index')->with('success', 'Страница создана');
    }

    public function edit(Page $page)
    {
        $categories = Category::all();
        return view('Menu::admin.pages.edit', compact('page', 'categories'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
            'content'          => 'nullable|string',
            'homepage_order'   => 'nullable|integer|min:0',
            'categories'       => 'nullable|array',
        ]);

        // 🧩 Чекбоксы: если не переданы — значит false
        $data['published'] = $request->has('published');
        $data['show_on_homepage'] = $request->has('show_on_homepage');

        // Генерация slug при необходимости
        if (empty($data['slug'])) {
            $data['slug'] = \Str::slug($data['title']) . '-' . uniqid();
        }

        $page->update($data);

        // Категории
        $page->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.pages.index')->with('success', 'Страница обновлена.');
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('admin.pages.index')->with('success', 'Страница удалена');
    }
}
