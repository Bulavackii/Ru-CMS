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
use Modules\Users\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\CategoryController;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Payments\Models\Order;
use App\Http\Controllers\Frontend\PasswordController;
use App\Http\Controllers\Admin\AccountSettingsController;
use App\Http\Controllers\Frontend\FrontendSearchController;
use Modules\Menu\Models\Page;

// ✅ Главная страница с пагинацией по шаблонам
Route::get('/', function () {
    $user = Auth::user();
    $categories = Category::all();
    $slideshows = Slideshow::with('items')->get();

    $templateKeys = [
        'default',
        'products',
        'reviews',
        'faq',
        'gallery',
        'slideshow',
        'test',
        'test2',
        'contacts'
    ];

    $templates = [];
    $cart = session('cart', []);

    foreach ($templateKeys as $key) {
        $query = News::with('categories')->where('published', true)->where('template', $key);

        if (request("category_$key")) {
            $query->whereHas('categories', function ($q) use ($key) {
                $q->where('categories.id', request("category_$key"));
            });
        }

        $allItems = $query->orderByDesc('id')->get();

        $allItems->transform(function ($item) use ($cart) {
            $cartQty = isset($cart[$item->id]) ? $cart[$item->id]['qty'] : 0;
            $item->stock = is_null($item->stock) ? null : max($item->stock - $cartQty, 0);
            return $item;
        });

        $perPage = 9;
        $currentPage = LengthAwarePaginator::resolveCurrentPage($key . '_page');
        $offset = ($currentPage - 1) * $perPage;
        $items = $allItems->slice($offset, $perPage)->values();

        $templates[$key] = new LengthAwarePaginator(
            $items,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => $key . '_page']
        );
    }

    $homePages = Page::where('published', true)
        ->where('show_on_homepage', true)
        ->orderBy('homepage_order')
        ->get();

    return view('frontend.home', [
        'user' => $user,
        'categories' => $categories,
        'templates' => $templates,
        'slideshows' => $slideshows,
        'homePages' => $homePages,
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
    Route::get('/dashboard/password', [PasswordController::class, 'edit'])->name('password.change.form');
    Route::put('/dashboard/password', [PasswordController::class, 'update'])->name('password.change.update');

    Route::get('/organization', [OrganizationController::class, 'edit'])->name('organization.edit');
    Route::put('/organization', [OrganizationController::class, 'update'])->name('organization.update');

    // ✅ История заказов с доставкой и пагинацией по 5 штук
    Route::get('/dashboard/orders', function () {
        $orders = \Modules\Payments\Models\Order::with('paymentMethod', 'deliveryMethod', 'items')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(5);

        return view('frontend.dashboard.orders', compact('orders'));
    })->name('dashboard.orders');
});

// 🛠️ Админка и модули
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');
    Route::post('/admin/modules/register', [ModuleController::class, 'register'])->name('admin.modules.register');
    Route::delete('/admin/modules/{id}', [ModuleController::class, 'destroy'])->name('admin.modules.destroy');
    Route::patch('/admin/users/{id}/toggle-role', [UserController::class, 'toggleRole'])->name('admin.users.toggleRole');

    // Для отображения формы создания пользователя
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    // Для обработки и сохранения нового пользователя
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');

    // Настройки учётной записи админа
    Route::get('/account/settings', [AccountSettingsController::class, 'index'])->name('admin.account.settings');

    Route::post('/admin/upload-media', [UploadController::class, 'uploadMedia'])->name('admin.upload.media');
    Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');

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

    // Маршруты для управления файлами
    Route::prefix('admin/files')->name('admin.files.')->group(function () {
        // Страница списка файлов
        Route::get('/', [FileController::class, 'index'])->name('index');

        // Загрузка файла
        Route::post('/upload', [FileController::class, 'upload'])->name('upload');

        // Скачивание файла
        Route::get('/download/{id}', [FileController::class, 'download'])->name('download');

        // Фильтрация файлов по категориям
        Route::get('/filter', [FileController::class, 'filter'])->name('filter');
    });

    Route::get('/admin/error-report', [ErrorReportController::class, 'form'])->name('admin.error.report');
    Route::post('/admin/error-report', [ErrorReportController::class, 'send'])->name('admin.error.report.send');

    Route::get('/admin/geolocation', [ErrorReportController::class, 'geolocation'])->name('admin.geolocation');
    Route::get('/admin/system-info', [ErrorReportController::class, 'systemInfo'])->name('admin.system_info');

    Route::delete('/admin/files/bulk-delete', [FileController::class, 'bulkDelete'])->name('admin.files.bulkDelete');

    Route::delete('/admin/categories/bulk-delete', [\Modules\Categories\Controllers\Admin\CategoryController::class, 'bulkDelete'])
        ->name('admin.categories.bulkDelete');

    require_once base_path('modules/Categories/Routes/web.php');
    require_once base_path('modules/Slideshow/Routes/web.php');
    require_once base_path('modules/Notifications/Routes/web.php');

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

Route::get('/search', [FrontendSearchController::class, 'index'])->name('frontend.search');
