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

        return view('admin.dashboard.index', compact(
            'stats',
            'recentActivity',
            'quickActions',
            'systemStatus',
            'licenseInfo'
        ));
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
        return [
            [
                'title' => __('admin.dashboard.quick_news'),
                'icon' => 'newspaper',
                'url' => route('admin.news.create'),
                'color' => 'blue',
            ],
            [
                'title' => __('admin.dashboard.quick_page'),
                'icon' => 'file',
                'url' => route('admin.pages.create'),
                'color' => 'green',
            ],
            [
                'title' => __('admin.dashboard.quick_file'),
                'icon' => 'upload',
                'url' => route('admin.files.index'),
                'color' => 'purple',
            ],
            [
                'title' => __('admin.dashboard.quick_category'),
                'icon' => 'folder',
                'url' => route('admin.categories.create'),
                'color' => 'orange',
            ],
            [
                'title' => __('admin.dashboard.quick_slideshow'),
                'icon' => 'image',
                'url' => route('admin.slideshow.create'),
                'color' => 'pink',
            ],
            [
                'title' => __('admin.dashboard.quick_user'),
                'icon' => 'user-plus',
                'url' => route('admin.users.create'),
                'color' => 'indigo',
            ],
        ];
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

