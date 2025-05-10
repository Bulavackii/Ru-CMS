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
use Modules\News\Controllers\Admin\NewsController;
use Modules\News\Controllers\Frontend\NewsController as FrontendNewsController;
use Modules\Categories\Models\Category;
use Modules\News\Models\News;
use App\Http\Controllers\Admin\UploadController;
use Modules\Slideshow\Models\Slideshow;

// ✅ Главная страница
Route::get('/', function () {
    $user = Auth::user();
    $categories = Category::all();

    $templateKeys = ['default', 'products', 'reviews', 'faq', 'gallery', 'slideshow', 'test', 'test2', 'contacts'];
    $templates = [];

    foreach ($templateKeys as $key) {
        $query = News::with('categories')
            ->where('published', true)
            ->where('template', $key);

        if (request("category_$key")) {
            $query->whereHas('categories', function ($q) use ($key) {
                $q->where('categories.id', request("category_$key"));
            });
        }

        $templates[$key] = $query->orderByDesc('id')->get();
    }

    $slideshows = Slideshow::with('items')->get();

    return view('frontend.home', [
        'user' => $user,
        'categories' => $categories,
        'templates' => $templates,
        'slideshows' => $slideshows,
    ]);
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

// 🛠️ Админка и модули
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    // Модули
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');

    // Загрузка медиа
    Route::post('/admin/upload-media', [UploadController::class, 'uploadMedia'])->name('admin.upload.media');

    // Поиск
    Route::get('/admin/search', [SearchController::class, 'index'])->name('admin.search.index');

    // Маршруты модуля News
    Route::prefix('admin/news')->group(function () {
        $controller = NewsController::class;

        Route::get('/', [$controller, 'index'])->name('admin.news.index');
        Route::get('/create', [$controller, 'create'])->name('admin.news.create');
        Route::post('/', [$controller, 'store'])->name('admin.news.store');
        Route::get('/{news}/edit', [$controller, 'edit'])->name('admin.news.edit');
        Route::put('/{news}', [$controller, 'update'])->name('admin.news.update');
        Route::delete('/{news}', [$controller, 'destroy'])->name('admin.news.destroy');

        // 🔀 Групповые действия
        Route::post('/bulk', [$controller, 'bulkAction'])->name('admin.news.bulk');
        Route::post('/bulk-update', [$controller, 'bulkUpdate'])->name('admin.news.bulk.update');
        Route::post('/bulk-delete', [$controller, 'bulkDelete'])->name('admin.news.bulkDelete'); 
        Route::get('/bulk', [$controller, 'bulkEdit'])->name('admin.news.bulk.edit');
    });

    // Подключение других модулей
    require_once base_path('modules/Categories/Routes/web.php');
    require_once base_path('modules/Slideshow/Routes/web.php');
    require_once base_path('modules/Notifications/Routes/web.php');

    // Главная админка
    Route::get('/admin', fn() => view('admin'))->name('admin');
    Route::get('/admin/{any}', fn() => view('admin'))->where('any', '.*');
});

// 🌐 Публичные маршруты
Route::get('/news', [FrontendNewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [FrontendNewsController::class, 'show'])->name('news.show');
Route::get('/slideshow/{slug}', [\Modules\Slideshow\Controllers\PublicController::class, 'show'])->name('slideshow.show');
