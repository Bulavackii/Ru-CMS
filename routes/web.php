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

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout')->middleware('auth');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');

    require_once base_path('modules/News/Routes/web.php');
    require_once base_path('modules/Categories/Routes/web.php');
    require_once base_path('modules/Slideshow/Routes/web.php');
    require_once base_path('modules/Notifications/Routes/web.php');

    Route::get('/admin/search', [SearchController::class, 'index'])->name('admin.search.index');
    Route::post('/admin/upload-media', [UploadController::class, 'uploadMedia'])->name('admin.upload.media');

    Route::get('/admin', fn() => view('admin'))->name('admin');
    Route::get('/admin/{any}', fn() => view('admin'))->where('any', '.*');
});

Route::get('/news', [FrontendNewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [FrontendNewsController::class, 'show'])->name('news.show');
Route::get('/slideshow/{slug}', [\Modules\Slideshow\Controllers\PublicController::class, 'show'])->name('slideshow.show');
