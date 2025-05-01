<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\DashboardController;
use Modules\System\Controllers\Admin\ModuleController;

Route::get('/', function () {
    return view('welcome');
});

// Пользовательская зона
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Админ-зона
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');

    // ⚠️ ВАЖНО: этот маршрут — В САМОМ КОНЦЕ
    Route::get('/admin/{any}', function () {
        return view('admin');
    })->where('any', '.*');
});
