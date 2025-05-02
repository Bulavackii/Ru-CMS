<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use Modules\System\Controllers\Admin\ModuleController;
use Modules\Search\Controllers\Admin\SearchController;
use Modules\News\Controllers\Frontend\NewsController as FrontendNewsController;
use Modules\Categories\Controllers\Admin\CategoryController as FrontendCategoryController;
use Modules\News\Models\News;
use App\Models\Category;
use App\Http\Controllers\Admin\UploadController;

// Главная страница
Route::get('/', function () {
    $user = Auth::user();
    $categories = Category::all();

    $query = News::with('categories')->where('published', true);

    if (request('category')) {
        $query->whereHas('categories', function ($q) {
            $q->where('categories.id', request('category'));
        });
    }

    $newsList = $query->orderByDesc('id')->paginate(10);

    return view('frontend.home', compact('user', 'categories', 'newsList'));
});

// 👤 Гостевой доступ
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// 🔒 Выход
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout')->middleware('auth');

// 👤 Личный кабинет
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// 🛠 Админка
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');

    // 🔍 Поиск
    Route::get('/admin/search', [SearchController::class, 'index'])->name('admin.search.index');

    // ✅ Категории (из модуля)
    require_once base_path('modules/Categories/Routes/web.php');

    // ✅ Загрузка медиафайлов из редактора
    Route::post('/admin/upload-media', [UploadController::class, 'uploadMedia'])->name('admin.upload.media');

    // SPA fallback — в самом конце
    Route::get('/admin/{any}', fn () => view('admin'))->where('any', '.*');
});

// ✅ Новости (frontend)
Route::get('/news', [FrontendNewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [FrontendNewsController::class, 'show'])->name('news.show');
