<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\OrganizationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Admin\ErrorReportController;
use App\Http\Controllers\Admin\UploadController;
use Modules\System\Controllers\Admin\ModuleController;
use Modules\Search\Controllers\Admin\SearchController;
use Modules\News\Controllers\Admin\NewsController;
use Modules\News\Controllers\Frontend\NewsController as FrontendNewsController;
use Modules\Slideshow\Controllers\PublicController;
use Modules\Categories\Models\Category;
use Modules\News\Models\News;
use Modules\Slideshow\Models\Slideshow;
use Modules\Messages\Controllers\Admin\MessageController;

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

// 👤 Личный кабинет (физ и юр лица)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/edit', [DashboardController::class, 'edit'])->name('dashboard.edit');
    Route::put('/dashboard/edit', [DashboardController::class, 'update'])->name('dashboard.update');

    Route::get('/organization', [OrganizationController::class, 'edit'])->name('organization.edit');
    Route::put('/organization', [OrganizationController::class, 'update'])->name('organization.update');
});

// 🛠️ Админка и модули
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');
    Route::post('/admin/modules/register', [ModuleController::class, 'register'])->name('admin.modules.register');
    Route::delete('/admin/modules/{id}', [ModuleController::class, 'destroy'])->name('admin.modules.destroy');

    Route::post('/admin/upload-media', [UploadController::class, 'uploadMedia'])->name('admin.upload.media');

    Route::get('/admin/search', [SearchController::class, 'index'])->name('admin.search.index');

    Route::prefix('admin/news')->group(function () {
        $controller = NewsController::class;
        Route::get('/', [$controller, 'index'])->name('admin.news.index');
        Route::get('/create', [$controller, 'create'])->name('admin.news.create');
        Route::post('/', [$controller, 'store'])->name('admin.news.store');
        Route::get('/{news}/edit', [$controller, 'edit'])->name('admin.news.edit');
        Route::put('/{news}', [$controller, 'update'])->name('admin.news.update');
        Route::delete('/{news}', [$controller, 'destroy'])->name('admin.news.destroy');

        Route::post('/bulk', [$controller, 'bulkAction'])->name('admin.news.bulk');
        Route::post('/bulk-update', [$controller, 'bulkUpdate'])->name('admin.news.bulk.update');
        Route::post('/bulk-delete', [$controller, 'bulkDelete'])->name('admin.news.bulkDelete');
        Route::get('/bulk', [$controller, 'bulkEdit'])->name('admin.news.bulk.edit');
    });

    Route::prefix('admin/messages')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('admin.messages.index');
        Route::get('/create', [MessageController::class, 'create'])->name('admin.messages.create');
        Route::post('/', [MessageController::class, 'store'])->name('admin.messages.store');
        Route::get('/{message}', [MessageController::class, 'show'])->name('admin.messages.show');
    });

    Route::get('/admin/error-report', [ErrorReportController::class, 'form'])->name('admin.error.report');
    Route::post('/admin/error-report', [ErrorReportController::class, 'send'])->name('admin.error.report.send');

    Route::get('/admin/geolocation', [ErrorReportController::class, 'geolocation'])->name('admin.geolocation');
    Route::get('/admin/system-info', [ErrorReportController::class, 'systemInfo'])->name('admin.system_info');

    require_once base_path('modules/Categories/Routes/web.php');
    require_once base_path('modules/Slideshow/Routes/web.php');
    require_once base_path('modules/Notifications/Routes/web.php');
    // require_once base_path('modules/Messages/Routes/web.php'); // удалено, чтобы не дублировать маршруты

    Route::get('/admin', fn() => view('admin'))->name('admin');
    Route::get('/admin/{any}', fn() => view('admin'))->where('any', '.*');
});

// 🌐 Публичные маршруты
Route::get('/news', [FrontendNewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [FrontendNewsController::class, 'show'])->name('news.show');
Route::get('/slideshow/{slug}', [PublicController::class, 'show'])->name('slideshow.show');

// 🔗 Статические страницы
Route::view('/about', 'frontend.pages.about')->name('pages.about');
Route::view('/faq', 'frontend.pages.faq')->name('pages.faq');
Route::view('/contacts', 'frontend.pages.contacts')->name('pages.contacts');
