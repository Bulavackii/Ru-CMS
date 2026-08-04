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
use Modules\Categories\Controllers\Admin\CategoryController;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Payments\Models\Order;
use App\Http\Controllers\Frontend\PasswordController;
use App\Http\Controllers\Admin\AccountSettingsController;
use App\Http\Controllers\Frontend\FrontendSearchController;
use Modules\Menu\Models\Page;
use Modules\Menu\Models\Menu;
use Modules\Install\Controllers\InstallController;
use App\Http\Middleware\RedirectIfInstalled;
use App\Http\Controllers\SitemapController;
use Modules\Visual\Http\Controllers\Admin\FragmentsController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController as AuthPasswordController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// 📱 Web Push уведомления
Route::middleware('auth')->group(function () {
    Route::post('/webpush/subscribe', [\App\Http\Controllers\WebPushController::class, 'subscribe'])->name('webpush.subscribe');
    Route::post('/webpush/unsubscribe', [\App\Http\Controllers\WebPushController::class, 'unsubscribe'])->name('webpush.unsubscribe');
    Route::get('/webpush/public-key', [\App\Http\Controllers\WebPushController::class, 'getPublicKey'])->name('webpush.public-key');
});

// 📚 API Документация (Swagger)
Route::get('/api/docs', [\App\Http\Controllers\Api\V1\SwaggerController::class, 'index'])->name('api.docs');
Route::get('/api-docs.json', function () {
    $path = public_path('api-docs.json');
    if (!file_exists($path)) {
        \Artisan::call('api:docs:generate');
    }
    return response()->file($path);
})->name('api.docs.json');

// Маршруты установки загружаются из modules/Install/Routes/web.php
// Здесь оставлены только для обратной совместимости, но приоритет у модуля

// ✅ Главная страница с пагинацией по шаблонам
Route::get('/', [HomeController::class, 'index'])->name('home');

// 👤 Гостевой доступ
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1'); // 5 попыток в минуту

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:3,60'); // 3 попытки в час

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:3,60'); // 3 попытки в час

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store')->middleware('throttle:5,1'); // 5 попыток в минуту

    // 2FA маршруты
    Route::get('/two-factor/login', [\App\Http\Controllers\Auth\TwoFactorAuthenticationController::class, 'show'])->name('two-factor.login');
    Route::post('/two-factor/login', [\App\Http\Controllers\Auth\TwoFactorAuthenticationController::class, 'verify'])->name('two-factor.verify');
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

    // 2FA настройка
    Route::get('/two-factor/setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
    Route::post('/two-factor/enable', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/disable', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'disable'])->name('two-factor.disable');

    // История входов
    Route::get('/dashboard/login-history', [\App\Http\Controllers\Frontend\LoginHistoryController::class, 'index'])->name('dashboard.login-history');

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

    // 👤 Профиль пользователя (стандартный Breeze-контроллер)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ✅ Подтверждение email
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // 🔐 Повторное подтверждение пароля перед чувствительными действиями
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('/password', [AuthPasswordController::class, 'update'])->name('password.update');
});

// 🛠️ Админка и модули
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/admin', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/dashboard/save-widget-order', [\App\Http\Controllers\Admin\DashboardController::class, 'saveWidgetOrder'])->name('admin.dashboard.save-widget-order');
    
    Route::get('/admin/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
    Route::patch('/admin/modules/{id}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    Route::post('/admin/modules/install', [ModuleController::class, 'install'])->name('admin.modules.install');
    Route::post('/admin/modules/register', [ModuleController::class, 'register'])->name('admin.modules.register');
    Route::delete('/admin/modules/{id}', [ModuleController::class, 'destroy'])->name('admin.modules.destroy');
    Route::patch('/admin/users/{id}/toggle-role', [UserController::class, 'toggleRole'])->name('admin.users.toggleRole');
    Route::post('/admin/users/{id}/assign-roles', [UserController::class, 'assignRoles'])->name('admin.users.assignRoles');
    Route::patch('/admin/modules/{id}/archive', [ModuleController::class, 'archive'])->name('admin.modules.archive');
    Route::get('/admin/modules/{name}/download', [ModuleController::class, 'downloadArchive'])->name('admin.modules.downloadArchive');
    Route::post('/admin/modules/reorder', [ModuleController::class, 'reorder'])->name('admin.modules.reorder');

    // Для отображения формы создания пользователя
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    // Для обработки и сохранения нового пользователя
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');

    // Настройки учётной записи админа
    Route::get('/account/settings', [AccountSettingsController::class, 'index'])->name('admin.account.settings');

    // 🎨 Оформление ПАНЕЛИ, выбранное администратором для себя. Живёт в сессии
    // и не трогает активную тему сайта (её задаёт раздел «Темы») — как и
    // выбор темы посетителем на фронте. 'reset' убирает личный выбор.
    // Обычная ссылка, без JS: переключатель в шапке — просто список ссылок.
    Route::get('/admin/theme/{slug}', function (string $slug) {
        // Тема одна на панель и на сайт, и задаётся она здесь же.
        //
        // Раньше выбор писался в сессию как ЛИЧНОЕ оформление, а
        // resolveForVisitor отдаёт сессии приоритет над темой сайта. Из-за
        // этого кнопка «Применить» в разделе Темы выглядела нерабочей: она
        // честно меняла тему сайта, но старый выбор в шапке её перекрывал.
        // Теперь переключатель делает ровно то же, что и «Применить».
        session()->forget(['admin_theme', 'site_theme']);

        if ($slug !== 'reset') {
            $theme = \Modules\Visual\Models\Theme::where('slug', $slug)->first();

            if ($theme) {
                \Modules\Visual\Models\Theme::where('id', '!=', $theme->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                $theme->is_default = true;
                $theme->save();

                // Кеш активной темы обязателен: без него сайт продолжит
                // отдавать прежнюю тему до истечения срока хранения.
                \Illuminate\Support\Facades\Cache::forever('active_theme_id', $theme->id);
                \Illuminate\Support\Facades\Cache::forget('active_theme');
            }
        }

        return back();
    })->name('admin.theme.set');

    Route::post('/admin/upload-media', [UploadController::class, 'uploadMedia'])->name('admin.upload.media');
    Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');

    Route::get('/admin/search', [SearchController::class, 'index'])->name('admin.search.index');
    Route::get('/admin/search/global', [\App\Http\Controllers\Admin\GlobalSearchController::class, 'search'])->name('admin.search.global');

    // 📊 Аналитика
    Route::prefix('admin/analytics')->name('admin.analytics.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
        Route::get('/stats', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getStats'])->name('stats');
        Route::get('/popular', [\App\Http\Controllers\Admin\AnalyticsController::class, 'popularContent'])->name('popular');
        Route::get('/settings', [\App\Http\Controllers\Admin\AnalyticsController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\AnalyticsController::class, 'saveSettings'])->name('saveSettings');
    });

    // 💬 Комментарии (админка) - теперь через модуль Comments
    // Маршруты загружаются автоматически из modules/Comments/Routes/web.php

    // 🔄 Версионирование контента
    Route::prefix('admin/versions')->name('admin.versions.')->group(function () {
        Route::get('/history', [\App\Http\Controllers\Admin\VersionController::class, 'history'])->name('history');
        Route::post('/{version}/restore', [\App\Http\Controllers\Admin\VersionController::class, 'restore'])->name('restore');
        Route::post('/compare', [\App\Http\Controllers\Admin\VersionController::class, 'compare'])->name('compare');
        Route::post('/draft', [\App\Http\Controllers\Admin\VersionController::class, 'saveDraft'])->name('saveDraft');
        Route::get('/draft', [\App\Http\Controllers\Admin\VersionController::class, 'loadDraft'])->name('loadDraft');
    });

    // 📤 Экспорт/Импорт данных
    // Примечание: Импорт/Экспорт новостей перенесен в модуль NewsIO (admin/news-io)
    Route::prefix('admin/export')->name('admin.export.')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Admin\ExportController::class, 'exportUsers'])->name('users');
        Route::get('/orders', [\App\Http\Controllers\Admin\ExportController::class, 'exportOrders'])->name('orders');
        Route::post('/import/users', [\App\Http\Controllers\Admin\ExportController::class, 'importUsers'])->name('importUsers');
    });
    
    // Центр уведомлений админки (колокольчик в шапке, таблица admin_notifications).
    //
    // ⚠️ Раньше эта группа висела на префиксе admin/notifications с именами
    // admin.notifications.* — теми же, что у модуля Notifications (баннеры сайта).
    // URI совпадали, и по ним выигрывал модуль: колокольчик запрашивал JSON, а
    // получал HTML-страницу списка (в консоли «Failed to load notifications»), а
    // его кнопка удаления уходила в DELETE /admin/notifications/{id} — то есть
    // удаляла баннер сайта с таким же id. Разведено на отдельный префикс.
    Route::prefix('admin/notification-center')->name('admin.notification_center.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('destroy');
    });

    // 🔄 Обновления
    Route::prefix('admin/updates')->name('admin.updates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpdateController::class, 'index'])->name('index');
        Route::post('/check', [\App\Http\Controllers\Admin\UpdateController::class, 'check'])->name('check');
        Route::post('/install', [\App\Http\Controllers\Admin\UpdateController::class, 'install'])->name('install');
    });

    // 💳 Подписки и промокоды
    Route::prefix('admin/subscriptions')->name('admin.subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('index');
        Route::get('/promo-codes', [\App\Http\Controllers\Admin\SubscriptionController::class, 'promoCodes'])->name('promo-codes');
        Route::post('/promo-codes', [\App\Http\Controllers\Admin\SubscriptionController::class, 'createPromoCode'])->name('create-promo-code');
        Route::post('/apply-promo', [\App\Http\Controllers\Admin\SubscriptionController::class, 'applyPromoCode'])->name('apply-promo');
    });

    // 💾 Бэкапы
    Route::prefix('admin/backups')->name('admin.backups.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
        Route::post('/database', [\App\Http\Controllers\Admin\BackupController::class, 'createDatabase'])->name('create-database');
        Route::post('/files', [\App\Http\Controllers\Admin\BackupController::class, 'createFiles'])->name('create-files');
        Route::get('/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('download');
        Route::delete('/delete', [\App\Http\Controllers\Admin\BackupController::class, 'delete'])->name('delete');
    });


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

    // require (not require_once): route files are re-executed on every fresh
    // app boot (each PHPUnit test, each Octane/Swoole worker request). Since
    // require_once tracks included files at the PHP-process level rather than
    // per-application, it silently no-ops on the 2nd+ boot within one process
    // and these routes vanish from every router after the first boot.
    require base_path('modules/Categories/Routes/web.php');
    require base_path('modules/Slideshow/Routes/web.php');
    require base_path('modules/Notifications/Routes/web.php');
    require base_path('modules/NewsIO/Routes/web.php');
    require base_path('modules/Comments/Routes/web.php');

    // 🚚 💳 Подключение маршрутов модулей Delivery и Payments
    require base_path('modules/Delivery/Routes/web.php');
    require base_path('modules/Payments/Routes/web.php');
});

// 🌐 Публичные маршруты
Route::get('/news', [FrontendNewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [FrontendNewsController::class, 'show'])->name('news.show');
Route::get('/slideshow/{slug}', [PublicController::class, 'show'])->name('slideshow.show');

// 🔗 Статические страницы
//
// Страницы постепенно переезжают из Blade-вьюх в раздел «Страницы»: пока
// вьюха лежала в коде, посетитель видел одно, а редактор в панели правил
// совсем другую запись с похожим названием. Переехавшие адреса остаются
// прежними, но отдают запись из базы — её видно и правится в панели.
Route::get('/about', [\Modules\Menu\Controllers\Frontend\PageController::class, 'show'])
    ->defaults('slug', 'o-proekte')
    ->name('pages.about');

Route::get('/faq', [\Modules\Menu\Controllers\Frontend\PageController::class, 'show'])
    ->defaults('slug', 'chastye-voprosy')
    ->name('pages.faq');
Route::get('/contacts', [\Modules\Menu\Controllers\Frontend\PageController::class, 'show'])
    ->defaults('slug', 'kontakty')
    ->name('pages.contacts');
// 🔐 Статическая страница "Политика конфиденциальности"
Route::get('/privacy', [\Modules\Menu\Controllers\Frontend\PageController::class, 'show'])
    ->defaults('slug', 'konfidencialnost')
    ->name('pages.privacy');
// 📄 Навигационные страницы
Route::get('/terms', [\Modules\Menu\Controllers\Frontend\PageController::class, 'show'])
    ->defaults('slug', 'soglashenie')
    ->name('pages.terms');
Route::view('/partnership', 'frontend.pages.partnership')->name('pages.partnership'); // Сотрудничество
Route::get('/developers', [\Modules\Menu\Controllers\Frontend\PageController::class, 'show'])
    ->defaults('slug', 'razrabotchikam')
    ->name('pages.developers');
Route::view('/concept', 'frontend.pages.concept')->name('pages.concept'); // Концепция
Route::view('/sitemap', 'frontend.pages.sitemap')->name('pages.sitemap'); // Карта сайта
Route::view('/donate', 'frontend.pages.donate')->name('pages.donate'); // Пожертвовать

Route::get('/search', [FrontendSearchController::class, 'index'])->name('frontend.search');

// 🌍 Смена языка интерфейса переключателем в шапке. Явный выбор кладём в
// session('app_locale') — LocalizationMiddleware отдаёт ему приоритет над
// локалью страны. Принимаем только реально доступные локали (resources/lang).
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, available_locales(), true)) {
        session(['app_locale' => $locale, 'locale' => $locale]);
    }
    return redirect()->back();
})->name('frontend.locale.set');

// 🎨 Выбор темы оформления посетителем. Выбор личный: живёт в сессии и не
// трогает активную тему сайта (её задаёт админка). Значение 'reset' убирает
// личный выбор — посетитель снова видит оформление «как на сайте».
// Работает без JS: это обычная ссылка, как у переключателя языка выше.
Route::get('/theme/{slug}', function (string $slug) {
    // Тот же выбор применяется и к панели — см. admin.theme.set.
    if ($slug === 'reset') {
        session()->forget(['site_theme', 'admin_theme']);

        return redirect()->back();
    }

    if (\Modules\Visual\Models\Theme::where('slug', $slug)->exists()) {
        session(['site_theme' => $slug, 'admin_theme' => $slug]);
    }

    return redirect()->back();
})->name('frontend.theme.set');

// Route::get('/admin', fn() => view('admin'))->name('admin');
// Route::get('/admin/{any}', fn() => view('admin'))
//     ->where('any', '^(?!seo/).*');

Route::prefix('admin')->middleware(['web','auth','admin'])->name('admin.visual.')->group(function () {
    Route::get('/visual/fragments', [FragmentsController::class,'index'])->name('fragments.index');
});
