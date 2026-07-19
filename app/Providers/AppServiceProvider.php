<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\News\Models\News;
use Modules\Menu\Models\Page;
use Modules\Categories\Models\Category;
use App\Observers\NewsObserver;
use App\Observers\PageObserver;
use App\Observers\CategoryObserver;
use App\Services\SecurityService;
use App\Services\UpdateService;
use App\Services\SubscriptionService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрация сервис-провайдеров
        $this->app->register(ModuleServiceProvider::class);
        $this->app->register(ThemeServiceProvider::class);

        // Регистрация сервисов как синглтонов
        $this->app->singleton(SecurityService::class);
        $this->app->singleton(UpdateService::class);
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(\App\Services\CacheService::class);
        $this->app->singleton(\App\Services\ImageOptimizationService::class);
        $this->app->singleton(\App\Services\NotificationService::class);
        $this->app->singleton(\App\Services\MonitoringService::class);
        $this->app->singleton(\App\Services\LoginHistoryService::class);

        // Алиасы для удобного доступа
        $this->app->alias(SecurityService::class, 'security');
        $this->app->alias(UpdateService::class, 'updates');
        $this->app->alias(SubscriptionService::class, 'subscription');
        $this->app->alias(\App\Services\CacheService::class, 'cacheService');
        $this->app->alias(\App\Services\ImageOptimizationService::class, 'imageOptimizer');
        $this->app->alias(\App\Services\MonitoringService::class, 'monitoring');
    }

    public function boot(): void
    {
        // Проверка установки обрабатывается через middleware RedirectIfInstalled
        // Не нужно делать редирект здесь, так как это нарушает жизненный цикл Laravel

        // Captcha: формы регистрации/входа безусловно вызывают app('captcha')
        // (guard в blade — config('captcha.enabled', true) && class_exists(...) —
        // фактически всегда true), так что биндинг обязан быть доступен вне
        // зависимости от того, включён ли модуль Captcha через таблицу modules.
        // Регистрируем именно в boot(), не в register(): CaptchaServiceProvider
        // вызывает Validator::extend() в своём register(), а 'validator' ещё
        // не забинжен на момент register()-фазы других провайдеров.
        $this->app->register(\Modules\Captcha\Providers\CaptchaServiceProvider::class);

        // Наблюдатели
        News::observe(NewsObserver::class);
        Page::observe(PageObserver::class);
        Category::observe(CategoryObserver::class);

        // Загрузка Install Views
        View::addNamespace('Install', base_path('modules/Install/Views'));

        // Глобальные переменные для представлений
        View::composer('*', function ($view) {
            $view->with('currentLocale', app()->getLocale());
        });
    }
}
