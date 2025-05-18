<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Controllers\Admin\MenuController;
use Modules\Menu\Controllers\Admin\MenuItemController;
use Modules\Menu\Controllers\Admin\PageController as AdminPageController;
use Modules\Menu\Controllers\Frontend\PageController as FrontendPageController;
use Modules\Menu\Models\Page;
use Modules\Categories\Models\Category;
use Modules\Menu\Controllers\Admin\PageController;

// 🔒 Админка
Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    // 🧩 Меню
    Route::get('menus', [MenuController::class, 'index'])->name('admin.menus.index');
    Route::get('menus/create', [MenuController::class, 'create'])->name('admin.menus.create');
    Route::post('menus', [MenuController::class, 'store'])->name('admin.menus.store');
    Route::get('menus/{menu}/edit', [MenuController::class, 'edit'])->name('admin.menus.edit');
    Route::patch('menus/{menu}/toggle', [MenuController::class, 'toggle'])->name('admin.menus.toggle');
    Route::post('menus/{menu}/items/update-order', [MenuController::class, 'updateOrder'])->name('admin.menus.updateOrder');
    Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('admin.menus.destroy');

    // 🧷 Пункты меню
    Route::post('menus/{menu}/items', [MenuItemController::class, 'store'])->name('admin.menu_items.store');
    Route::delete('menus/{menu}/items/{item}', [MenuItemController::class, 'destroy'])->name('admin.menu_items.destroy');

    // 📄 Страницы
    Route::get('pages', [AdminPageController::class, 'index'])->name('admin.pages.index');
    Route::get('pages/create', [AdminPageController::class, 'create'])->name('admin.pages.create');
    Route::post('pages', [AdminPageController::class, 'store'])->name('admin.pages.store');
    Route::get('pages/{page}/edit', [AdminPageController::class, 'edit'])->name('admin.pages.edit');
    Route::put('pages/{page}', [AdminPageController::class, 'update'])->name('admin.pages.update');
    Route::delete('pages/{page}', [AdminPageController::class, 'destroy'])->name('admin.pages.destroy');
    Route::get('pages/{page}/preview', [PageController::class, 'preview'])->name('admin.pages.preview');

    // 🔄 AJAX-запросы (✅ исправлены имена)
    Route::get('ajax/pages', fn() => Page::select('id', 'title')->get())->name('admin.ajax.pages');
    Route::get('ajax/categories', fn() => Category::select('id', 'title')->get())->name('admin.ajax.categories');
});

// 🌐 Публичные страницы
Route::middleware(['web'])->group(function () {
    Route::get('/page/{slug}', [FrontendPageController::class, 'show'])->name('frontend.pages.show');
});
