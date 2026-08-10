<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;
use Modules\Menu\Models\Page;
use App\Models\User;
use Modules\Payments\Models\Order;
use Modules\Messages\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * 📊 DashboardController - Главная страница админки с виджетами
 */
class DashboardController extends Controller
{
    private SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * 📊 Главная страница dashboard
     */
    public function index()
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return $this->getStats();
        });

        $recentActivity = $this->getRecentActivity();
        $quickActions = $this->getQuickActions();
        $systemStatus = $this->getSystemStatus();
        $licenseInfo = $this->subscriptionService->getLicenseInfo();

        $attention = $this->getAttention();
        $security  = $this->getSecurity();
        $updates   = $this->getUpdates();

        return view('admin.dashboard.index', compact(
            'stats',
            'recentActivity',
            'quickActions',
            'systemStatus',
            'licenseInfo',
            'attention',
            'security',
            'updates'
        ));
    }

/**
     * ❗ Что требует внимания.
     *
     * Дашборд показывал только сводные числа: сколько всего новостей, страниц
     * и пользователей. По ним видно состояние, но не видно, что СДЕЛАТЬ —
     * незамеченный черновик или выключённое меню так и оставались
     * незамеченными, пока не зайдёшь в раздел.
     *
     * Здесь собрано ровно то, у чего есть действие и ненулевой счёт. Пункты с
     * нулём не показываются: список «всё по нулям» ничего не сообщает и лишь
     * приучает его не читать.
     *
     * Каждый пункт обёрнут в try/catch по отдельности: модуль может быть
     * выключен или таблицы ещё нет, и главная панели не должна из-за этого
     * падать — она открывается чаще любой другой страницы.
     *
     * @return array<int, array{label:string, count:int, url:string, icon:string, tone:string}>
     */
    private function getAttention(): array
    {
        $items = [];

        $add = function (string $label, callable $count, string $route, string $icon, string $tone = 'warn') use (&$items) {
            try {
                if (! Route::has($route)) {
                    return;
                }

                $value = (int) $count();

                if ($value > 0) {
                    $items[] = [
                        'label' => $label,
                        'count' => $value,
                        'url'   => route($route),
                        'icon'  => $icon,
                        'tone'  => $tone,
                    ];
                }
            } catch (\Throwable $e) {
                // Молча пропускаем: раздел недоступен, значит и внимания не просит.
            }
        };

        $add('Черновики новостей', fn () => News::where('published', false)->count(),
            'admin.news.index', 'fa-file-pen');

        $add('Неопубликованные страницы', fn () => Page::where('published', false)->count(),
            'admin.pages.index', 'fa-file-lines');

        $add('Непрочитанные сообщения', fn () => Message::where('is_read', false)->count(),
            'admin.messages.index', 'fa-envelope', 'info');

        $add('Заказы на модерации', fn () => Order::where('status', 'pending')->count(),
            'admin.orders.index', 'fa-cart-shopping');

        // Меню без пунктов на сайт не попадает — это почти всегда недосмотр.
        $add('Меню без пунктов', fn () => \Modules\Menu\Models\Menu::doesntHave('items')->count(),
            'admin.menus.index', 'fa-bars');

        return $items;
    }

/**
     * 🔐 Безопасность: только ПРОВЕРЯЕМЫЕ факты.
     *
     * Здесь намеренно нет «оценки защищённости» и прочих цифр из воздуха.
     * Каждый пункт — конкретная настройка, которую видно из приложения и
     * которую владелец может изменить сам.
     *
     * Чего здесь НЕТ и почему: стойкость пароля администратора проверить
     * нельзя (в базе только хеш), а «сайт не взломан» — утверждение, которое
     * приложение о себе знать не может. Обещать такое на дашборде было бы
     * обманом.
     *
     * @return array{items: array<int, array{label:string, ok:bool, note:string}>, bad: int}
     */
    private function getSecurity(): array
    {
        $items = [];

        $debug = (bool) config('app.debug');
        $items[] = [
            'label' => 'Режим отладки',
            'ok'    => ! $debug,
            'note'  => $debug
                ? 'Включён — посетитель увидит текст ошибки и часть кода. На боевом сайте выключите APP_DEBUG.'
                : 'Выключен — ошибки не раскрывают устройство сайта.',
        ];

        $https = str_starts_with((string) config('app.url'), 'https://');
        $items[] = [
            'label' => 'Адрес сайта по HTTPS',
            'ok'    => $https,
            'note'  => $https
                ? 'APP_URL начинается с https.'
                : 'APP_URL без https: пароли и cookie ходят открытым текстом.',
        ];

        $key = (string) config('app.key');
        $items[] = [
            'label' => 'Ключ приложения',
            'ok'    => $key !== '',
            'note'  => $key !== ''
                ? 'Задан — сессии и cookie подписаны.'
                : 'Пуст: выполните php artisan key:generate.',
        ];

        $locked = file_exists(storage_path('install.lock'));
        $items[] = [
            'label' => 'Мастер установки закрыт',
            'ok'    => $locked,
            'note'  => $locked
                ? 'Повторная установка невозможна.'
                : 'Файл storage/install.lock отсутствует — /install открыт для всех.',
        ];

        try {
            $captcha = (bool) config('captcha.enabled', false);
            $items[] = [
                'label' => 'Каптча',
                'ok'    => $captcha,
                'note'  => $captcha
                    ? 'Включена — формы защищены от простых ботов.'
                    : 'Выключена: формы принимают отправку без проверки.',
            ];
        } catch (\Throwable $e) {
            // Модуль может быть выключен — тогда и пункта нет.
        }

        // Двухфакторная проверка. Считаем только по тем, кто реально может
        // зайти в панель — по тому же признаку, что проверяет AdminMiddleware
        // (`is_admin`). У покупателей она тоже есть, но её отсутствие у них
        // панель уязвимой не делает и только размывало бы картину.
        $total = User::where('is_admin', true)->count();
        // Флага мало: он может стоять при утраченном ключе, и тогда
        // сводка отчиталась бы о защите, которой на входе уже нет.
        // Условие повторяет hasTwoFactorEnabled() — запросом, а не в PHP,
        // чтобы не тащить всех администраторов в память.
        $guarded = User::where('is_admin', true)
            ->where('two_factor_enabled', true)
            ->whereNotNull('two_factor_secret')
            ->count();

        if ($total > 0) {
            $items[] = [
                'label' => 'Двухфакторная проверка',
                'ok'    => $guarded === $total,
                'note'  => $guarded === $total
                    ? 'Включена у всех, кто имеет доступ в панель.'
                    : 'Включена у ' . $guarded . ' из ' . $total . ': остальным для входа хватает пароля.',
            ];
        }

        $standalone = (bool) config('app.standalone', false);
        $items[] = [
            'label' => 'Автономный режим',
            'ok'    => true, // это выбор, а не изъян — отмечаем как сведение
            'note'  => $standalone
                ? 'Включён: наружу не уходит ни один запрос.'
                : 'Выключен: интеграции могут обращаться к внешним службам.',
        ];

        return [
            'items' => $items,
            'bad'   => count(array_filter($items, static fn ($i) => ! $i['ok'])),
        ];
    }

    /**
     * ⬆️ Обновления.
     *
     * Проверка идёт на СВОЙ сервер, адрес которого задаёт владелец. По
     * умолчанию он пуст, и никакого запроса наружу не происходит — раньше
     * панель ходила на чужой адрес при каждой отрисовке, отправляя туда
     * лицензионный ключ и версии окружения.
     *
     * @return array{current:string, configured:bool, latest:?string, available:bool, note:string}
     */
    private function getUpdates(): array
    {
        $current = (string) config('app.version', '1.0.0');
        $server  = trim((string) config('app.update_server_url', ''));

        if ($server === '') {
            return [
                'current'    => $current,
                'configured' => false,
                'latest'     => null,
                'available'  => false,
                'note'       => 'Сервер обновлений не задан — панель никуда не обращается. Свой адрес указывается в UPDATE_SERVER_URL.',
            ];
        }

        try {
            $result = app(\App\Services\UpdateService::class)->checkForUpdates();

            return [
                'current'    => $current,
                'configured' => true,
                'latest'     => $result['latest_version'] ?? null,
                'available'  => (bool) ($result['update_available'] ?? false),
                'note'       => ($result['update_available'] ?? false)
                    ? 'Доступна новая версия.'
                    : 'Установлена последняя версия.',
            ];
        } catch (\Throwable $e) {
            // Сервер недоступен — это не повод ронять главную панели.
            return [
                'current'    => $current,
                'configured' => true,
                'latest'     => null,
                'available'  => false,
                'note'       => 'Сервер обновлений не ответил.',
            ];
        }
    }

    /**
     * 📈 Получить статистику
     */
    private function getStats(): array
    {
        $now = Carbon::now();
        $lastWeek = $now->copy()->subWeek();
        $lastMonth = $now->copy()->subMonth();

        // Данные для графиков (последние 7 дней)
        $chartData = $this->getChartData();

        return [
            'charts' => $chartData,
            // Контент
            'content' => [
                'news' => [
                    'total' => News::count(),
                    'published' => News::where('published', true)->count(),
                    'draft' => News::where('published', false)->count(),
                    'this_week' => News::where('created_at', '>=', $lastWeek)->count(),
                    'this_month' => News::where('created_at', '>=', $lastMonth)->count(),
                ],
                'pages' => [
                    'total' => Page::count(),
                    'published' => Page::where('published', true)->count(),
                ],
                'files' => [
                    'total' => DB::table('files')->count() ?? 0,
                    'size' => $this->getFilesSize(),
                ],
            ],

            // Пользователи
            'users' => [
                'total' => User::count(),
                'admins' => User::where('is_admin', true)->count(),
                'this_week' => User::where('created_at', '>=', $lastWeek)->count(),
                'this_month' => User::where('created_at', '>=', $lastMonth)->count(),
                'active_today' => User::whereDate('last_login_at', $now->toDateString())->count(),
            ],

            // Заказы
            'orders' => [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'completed' => Order::where('status', 'completed')->count(),
                'this_week' => Order::where('created_at', '>=', $lastWeek)->count(),
                'revenue' => Order::where('status', 'completed')
                    ->where('created_at', '>=', $lastMonth)
                    ->sum('total'),
            ],

            // Сообщения
            'messages' => [
                'total' => Message::count(),
                // Колонка называется is_read (см. миграцию create_messages_table).
                // С 'read' главная страница панели отдавала 500 на PostgreSQL:
                // «столбец "read" не существует». В тестах баг не всплывал —
                // там таблица modules пуста, модуль Messages не регистрируется,
                // и до этого запроса дело не доходило.
                'unread' => Message::where('is_read', false)->count(),
                'this_week' => Message::where('created_at', '>=', $lastWeek)->count(),
            ],
        ];
    }

    /**
     * 📋 Последняя активность
     */
    private function getRecentActivity(): array
    {
        $activities = [];

        // Последние новости
        $recentNews = News::latest()->limit(5)->get();
        foreach ($recentNews as $news) {
            $activities[] = [
                'type' => 'news',
                'icon' => 'newspaper',
                'title' => __('admin.dashboard.act_news', ['title' => $news->title]),
                'user' => $news->user->name ?? __('admin.dashboard.system'),
                'time' => $news->created_at->diffForHumans(),
                'url' => route('admin.news.edit', $news->id),
            ];
        }

        // Последние пользователи
        $recentUsers = User::latest()->limit(3)->get();
        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user',
                'icon' => 'user',
                'title' => __('admin.dashboard.act_user', ['name' => $user->name]),
                'user' => __('admin.dashboard.system'),
                'time' => $user->created_at->diffForHumans(),
                'url' => route('admin.users.index'),
            ];
        }

        // Последние заказы
        if (class_exists(Order::class)) {
            $recentOrders = Order::latest()->limit(3)->get();
            foreach ($recentOrders as $order) {
                $activities[] = [
                    'type' => 'order',
                    'icon' => 'shopping-cart',
                    'title' => __('admin.dashboard.act_order', ['id' => $order->id]),
                    'user' => $order->user->name ?? __('admin.dashboard.guest'),
                    'time' => $order->created_at->diffForHumans(),
                    'url' => route('admin.orders.show', $order->id),
                ];
            }
        }

        // Сортировка по времени
        usort($activities, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));

        return array_slice($activities, 0, 10);
    }

    /**
     * ⚡ Быстрые действия
     */
    private function getQuickActions(): array
    {
        // Каждое действие ведёт в СВОЙ модуль, а модуль можно выключить в
        // «Модулях» — тогда действие должно уйти с главной вместе с разделом
        // из левого меню. Раньше список был жёстким, и панель предлагала
        // «Добавить слайд» при выключенном слайдшоу.
        $actions = [
            ['key' => 'quick_news',      'icon' => 'newspaper', 'route' => 'admin.news.create',       'module' => 'News',       'color' => 'blue'],
            ['key' => 'quick_page',      'icon' => 'file',      'route' => 'admin.pages.create',      'module' => 'Menu',       'color' => 'green'],
            ['key' => 'quick_file',      'icon' => 'upload',    'route' => 'admin.files.index',       'module' => 'Files',      'color' => 'purple'],
            ['key' => 'quick_category',  'icon' => 'folder',    'route' => 'admin.categories.create', 'module' => 'Categories', 'color' => 'orange'],
            ['key' => 'quick_slideshow', 'icon' => 'image',     'route' => 'admin.slideshow.create',  'module' => 'Slideshow',  'color' => 'pink'],
            ['key' => 'quick_user',      'icon' => 'user-plus', 'route' => 'admin.users.create',      'module' => 'Users',      'color' => 'indigo'],
        ];

        $available = [];

        foreach ($actions as $action) {
            if (! Route::has($action['route']) || ! module_enabled($action['module'])) {
                continue;
            }

            $available[] = [
                'title' => __('admin.dashboard.' . $action['key']),
                'icon'  => $action['icon'],
                'url'   => route($action['route']),
                'color' => $action['color'],
            ];
        }

        return $available;
    }

    /**
     * ⚙️ Статус системы
     */
    private function getSystemStatus(): array
    {
        $lastBackup = $this->getLastBackupTime();
        $updateInfo = $this->getUpdateInfo();

        return [
            'backup' => [
                'status' => $lastBackup ? 'success' : 'warning',
                'message' => $lastBackup 
                    ? __('admin.dashboard.backup_last', ['time' => $lastBackup->diffForHumans()])
                    : __('admin.dashboard.backup_none'),
                'icon' => 'database',
            ],
            'updates' => [
                'status' => $updateInfo['available'] ? 'info' : 'success',
                'message' => $updateInfo['available']
                    ? __('admin.dashboard.update_available', ['version' => $updateInfo['version']])
                    : __('admin.dashboard.update_none'),
                'icon' => 'sync',
            ],
            'cache' => [
                'status' => 'success',
                'message' => __('admin.dashboard.cache_ok'),
                'icon' => 'bolt',
            ],
            'queue' => [
                'status' => $this->checkQueue() ? 'success' : 'warning',
                'message' => $this->checkQueue() 
                    ? __('admin.dashboard.queue_ok')
                    : __('admin.dashboard.queue_none'),
                'icon' => 'tasks',
            ],
        ];
    }

    /**
     * 📁 Размер файлов
     */
    private function getFilesSize(): string
    {
        $size = DB::table('files')->sum('size') ?? 0;
        return $this->formatBytes($size);
    }

    /**
     * 📊 Форматирование размера
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 💾 Время последнего бэкапа
     */
    private function getLastBackupTime(): ?Carbon
    {
        $backupDir = storage_path('app/backups/database');
        if (!is_dir($backupDir)) {
            return null;
        }

        $files = glob($backupDir . '/*.{sql,sql.gz,custom}', GLOB_BRACE);
        if (empty($files)) {
            return null;
        }

        $latest = max(array_map('filemtime', $files));
        return Carbon::createFromTimestamp($latest);
    }

    /**
     * 🔄 Информация об обновлениях
     */
    private function getUpdateInfo(): array
    {
        try {
            $updateService = app('updates');
            return $updateService->checkForUpdates();
        } catch (\Exception $e) {
            return ['available' => false, 'version' => null];
        }
    }

    /**
     * ✅ Проверка очереди
     */
    private function checkQueue(): bool
    {
        return config('queue.default') !== 'sync';
    }

    /**
     * 📊 Данные для графиков
     */
    private function getChartData(): array
    {
        $days = [];
        $newsData = [];
        $usersData = [];
        $ordersData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->format('d.m');
            
            $newsData[] = News::whereDate('created_at', $date->toDateString())->count();
            $usersData[] = User::whereDate('created_at', $date->toDateString())->count();
            
            if (class_exists(Order::class)) {
                $ordersData[] = Order::whereDate('created_at', $date->toDateString())->count();
            } else {
                $ordersData[] = 0;
            }
        }

        return [
            'labels' => $days,
            'news' => $newsData,
            'users' => $usersData,
            'orders' => $ordersData,
        ];
    }

    /**
     * 💾 Сохранение порядка виджетов
     */
    public function saveWidgetOrder(Request $request)
    {
        $order = $request->input('order', []);
        
        // Сохранение в настройках пользователя или в БД
        $user = auth()->user();
        $settings = json_decode($user->settings ?? '{}', true);
        $settings['dashboard_widget_order'] = $order;
        $user->settings = json_encode($settings);
        $user->save();

        return response()->json(['success' => true]);
    }
}

