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
        $this->guardOutboundRequests();

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

    /**
     * Автономный режим: ни один запрос не уходит наружу.
     *
     * Заслон стоит на самом HTTP-клиенте, а не в двадцати сервисах по
     * отдельности. Смысл именно в этом: гасить оплату, доставку, SMS,
     * оповещения, обновления и выгрузки SEO поштучно — верный способ однажды
     * пропустить одну и не заметить. Здесь же выхода нет ни у кого, включая
     * код, который напишут после.
     *
     * Свои адреса остаются доступны. Автономный режим означает «не ходить в
     * интернет», а не «остаться без базы»: Elasticsearch на localhost, служба
     * в закрытой сети или собственный сервер обновлений во внутреннем контуре
     * работают как обычно.
     */
    private function guardOutboundRequests(): void
    {
        \Illuminate\Support\Facades\Http::globalRequestMiddleware(function ($request) {
            if (outbound_allowed()) {
                return $request;
            }

            $host = strtolower((string) $request->getUri()->getHost());

            if ($this->isInternalHost($host)) {
                return $request;
            }

            // Бросаем исключение, а не подменяем ответ: все исходящие вызовы в
            // проекте обёрнуты в try/catch, поэтому возможность деградирует
            // молча, а не роняет страницу. Заодно попадает в журнал.
            throw new \RuntimeException(
                'Автономный режим: запрос к ' . $host . ' отменён (APP_STANDALONE=true)'
            );
        });
    }

    /** Свой сервер, локальная сеть или собственный домен. */
    private function isInternalHost(string $host): bool
    {
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Публичный адрес — наружу; частный и петлевой — свои.
            return ! filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        return $host === strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
    }
}
