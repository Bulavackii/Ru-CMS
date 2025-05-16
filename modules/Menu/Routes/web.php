<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Controllers\Admin\MenuController;
use Modules\Menu\Controllers\Admin\PageController as AdminPageController;
use Modules\Menu\Controllers\Frontend\PageController as FrontendPageController;

// 🔒 Админка
Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    // Меню
    Route::get('menus', [MenuController::class, 'index'])->name('admin.menus.index');
    Route::get('menus/{menu}/edit', [MenuController::class, 'edit'])->name('admin.menus.edit');
    Route::post('menus/{menu}/items/update-order', [MenuController::class, 'updateOrder'])->name('admin.menus.updateOrder');

    // Страницы
    Route::get('pages', [AdminPageController::class, 'index'])->name('admin.pages.index');
    Route::get('pages/create', [AdminPageController::class, 'create'])->name('admin.pages.create');
    Route::post('pages', [AdminPageController::class, 'store'])->name('admin.pages.store');
    Route::get('pages/{page}/edit', [AdminPageController::class, 'edit'])->name('admin.pages.edit');
    Route::put('pages/{page}', [AdminPageController::class, 'update'])->name('admin.pages.update');
    Route::delete('pages/{page}', [AdminPageController::class, 'destroy'])->name('admin.pages.destroy');
});

// 🌐 Публичные страницы
Route::middleware(['web'])->group(function () {
    Route::get('/page/{slug}', [FrontendPageController::class, 'show'])->name('frontend.pages.show');
});
