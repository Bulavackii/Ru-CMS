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
use Modules\Categories\Models\Category;
use App\Http\Controllers\Admin\UploadController;
use Modules\Slideshow\Controllers\Admin\SlideshowController;
use Modules\Slideshow\Models\Slideshow;

// ✅ Главная страница — показываем все опубликованные новости
Route::get('/', function () {
    $user = Auth::user();
    $categories = Category::all();

    // Список шаблонов
    $allTemplates = ['default', 'products', 'contacts', 'gallery', 'test', 'slideshow', 'test2', 'example'];

    $templates = [];

    foreach ($allTemplates as $templateKey) {
        $query = News::with('categories')
            ->where('published', true)
            ->where('template', $templateKey);

        if (request("category_$templateKey")) {
            $query->whereHas('categories', function ($q) use ($templateKey) {
                $q->where('categories.id', request("category_$templateKey"));
            });
        }

        $templates[$templateKey] = $query->get();
    }

    $slideshows = Slideshow::with('items')->get();

    return view('frontend.home', compact('user', 'categories', 'templates', 'slideshows'));
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
    // ✅ Модули
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');

    // ✅ Новости
    require_once base_path('modules/News/Routes/web.php');

    // ✅ Категории
    require_once base_path('modules/Categories/Routes/web.php');

    // ✅ Слайдшоу (подключаем все маршруты из модуля)
    require_once base_path('modules/Slideshow/Routes/web.php');

    // 🔍 Поиск
    Route::get('/admin/search', [SearchController::class, 'index'])->name('admin.search.index');

    // ✅ Загрузка медиа
    Route::post('/admin/upload-media', [UploadController::class, 'uploadMedia'])->name('admin.upload.media');

    // Главная страница админки
    Route::get('/admin', fn() => view('admin'))->name('admin');

    // SPA fallback
    Route::get('/admin/{any}', fn() => view('admin'))->where('any', '.*');
});

// ✅ Новости (frontend)
Route::get('/news', [FrontendNewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [FrontendNewsController::class, 'show'])->name('news.show');

// ✅ Слайдшоу (frontend)
Route::get('/slideshow/{slug}', [\Modules\Slideshow\Controllers\PublicController::class, 'show'])->name('slideshow.show');
