<aside x-data="{
    collapsed: false,
    init() {
        const saved = localStorage.getItem('admin_sidebar_collapsed');
        this.collapsed = saved !== null ? JSON.parse(saved) : (window.innerWidth < 1024);
        this.$watch('collapsed', v => {
            localStorage.setItem('admin_sidebar_collapsed', JSON.stringify(v));
            this.$nextTick(() => window.dispatchEvent(new Event('resize')));
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth < 768) this.collapsed = true;
        }, { passive: true });
    }
}" x-cloak :class="collapsed ? 'w-16' : 'w-72'"
    class="fixed top-0 left-0 h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-lg flex flex-col z-40 transition-[width] duration-300 ease-in-out">
    @php
    $fontBase = data_get(($activeTheme ?? null)?->tokens ?? [], 'font.base', 'Inter, system-ui, sans-serif');
@endphp

    <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-800 bg-gray-900"
        style="font-family: {{ $fontBase }};">
        <div class="flex items-center justify-between gap-2 text-white">
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex w-8 h-8 rounded-lg bg-blue-600 items-center justify-center font-bold text-sm">RU</span>
                <span x-show="!collapsed" class="font-semibold tracking-tight">CMS · Панель управления</span>
            </div>
            <button @click="collapsed = !collapsed" :aria-expanded="(!collapsed).toString()"
                aria-label="Переключить меню"
                class="shrink-0 w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 grid place-items-center transition"
                title="Свернуть/развернуть меню">
                @themeIcon('chevron-right', 'hidden')
                <i class="fa-solid" :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
            </button>
        </div>
    </div>

    @php
        $contentLinks = [
            ['route' => route('admin.dashboard'),         'route_name' => 'admin.dashboard',    'label' => 'Панель управления', 'icon' => 'dashboard'],
            ['route' => route('admin.menus.index'),      'route_name' => 'admin.menus.*',      'label' => 'Меню',       'icon' => 'bars'],
            ['route' => route('admin.news.index'),       'route_name' => 'admin.news.*',       'label' => 'Новости',    'icon' => 'file-text'],
            ['route' => route('admin.pages.index'),      'route_name' => 'admin.pages.*',      'label' => 'Страницы',   'icon' => 'file-text'],
            ['route' => route('admin.categories.index'), 'route_name' => 'admin.categories.*', 'label' => 'Категории',  'icon' => 'folder'],
            ['route' => route('admin.slideshow.index'),  'route_name' => 'admin.slideshow.*',  'label' => 'Слайдшоу',   'icon' => 'image'],
            ['route' => route('admin.files.index'),      'route_name' => 'admin.files.*',      'label' => 'Файлы',      'icon' => 'folder'],
            ['route' => route('admin.newsio.index'),     'route_name' => 'admin.newsio.*',     'label' => 'Импорт/Экспорт', 'icon' => 'arrow-up'],
        ];

        $systemLinks = array_values(
            array_filter([
                ['url' => '/admin/modules', 'check' => request()->is('admin/modules'), 'label' => 'Модули', 'icon' => 'puzzle'],
                ['url' => '/admin/users', 'check' => request()->is('admin/users'), 'label' => 'Пользователи', 'icon' => 'user'],
                ['url' => '/admin/search', 'check' => request()->is('admin/search'), 'label' => 'Поиск', 'icon' => 'search'],
                [
                    'url' => route('admin.notifications.index'),
                    'check' => request()->routeIs('admin.notifications.*'),
                    'label' => 'Уведомления',
                    'icon' => 'bell',
                ],
                [
                    'url' => Route::has('seo.pages.index') ? route('seo.pages.index') : url('/admin/seo/pages'),
                    'check' => request()->routeIs('seo.*') || request()->is('admin/seo*'),
                    'label' => 'SEO',
                    'icon' => 'search',
                ],
                Route::has('admin.visual.themes.index')
                    ? [
                        'url' => route('admin.visual.themes.index'),
                        'check' => request()->routeIs('admin.visual.themes.*'),
                        'label' => 'Темы',
                        'icon'  => 'palette',
                    ]
                    : null,
                Route::has('admin.visual.fragments.index')
                    ? [
                        'url' => route('admin.visual.fragments.index'),
                        'check' => request()->routeIs('admin.visual.fragments.*'),
                        'label' => 'Фрагменты',
                        'icon'  => 'puzzle',
                    ]
                    : null,
            ]),
        );

        $accessibilityLinks = [
            [
                'url' => '/admin/accessibility',
                'check' => request()->is('admin/accessibility*'),
                'label' => 'Спецвозможности',
                'icon' => 'user',
            ],
        ];

        $paymentLinks = [
            [
                'url' => route('admin.payments.index'),
                'check' => request()->routeIs('admin.payments.*'),
                'label' => 'Оплата',
                'icon' => 'credit-card',
            ],
            [
                'url' => route('admin.orders.index'),
                'check' => request()->routeIs('admin.orders.*'),
                'label' => 'Заказы',
                'icon' => 'shopping-cart',
            ],
            [
                'url' => route('admin.delivery.index'),
                'check' => request()->routeIs('admin.delivery.*'),
                'label' => 'Доставка',
                'icon' => 'truck',
            ],
        ];

        $base = 'flex items-center gap-3 px-3 py-2 rounded-md transition group';
        $active = 'bg-black text-white font-semibold shadow-md';
        $idle = 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-black dark:hover:text-white';
    @endphp

    <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-6 text-[15px] font-medium"
        style="font-family: {{ $fontBase }};">
        <div>
            <p x-show="!collapsed"
               class="text-[11px] uppercase text-gray-400 dark:text-gray-500 font-semibold px-2 mb-1">Контент</p>
            @foreach ($contentLinks as $link)
                @php $isActive = request()->routeIs($link['route_name']); @endphp
                <a href="{{ $link['route'] }}" class="{{ $base }} {{ $isActive ? $active : $idle }}"
                   aria-current="{{ $isActive ? 'page' : 'false' }}" title="{{ $link['label'] }}">
                    @themeIcon($link['icon'], 'w-5 text-center')
                    <span x-show="!collapsed">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div>
            <p x-show="!collapsed"
               class="text-[11px] uppercase text-gray-400 dark:text-gray-500 font-semibold px-2 mb-1">Система</p>
            @foreach ($systemLinks as $link)
                <a href="{{ $link['url'] }}" class="{{ $base }} {{ $link['check'] ? $active : $idle }}"
                   aria-current="{{ $link['check'] ? 'page' : 'false' }}" title="{{ $link['label'] }}">
                    @themeIcon($link['icon'], 'w-5 text-center')
                    <span x-show="!collapsed">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div>
            <p x-show="!collapsed"
               class="text-[11px] uppercase text-gray-400 dark:text-gray-500 font-semibold px-2 mb-1">Доступность</p>
            @foreach ($accessibilityLinks as $link)
                <a href="{{ $link['url'] }}" class="{{ $base }} {{ $link['check'] ? $active : $idle }}"
                   aria-current="{{ $link['check'] ? 'page' : 'false' }}" title="{{ $link['label'] }}">
                    @themeIcon($link['icon'], 'w-5 text-center')
                    <span x-show="!collapsed">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div>
            <p x-show="!collapsed"
               class="text-[11px] uppercase text-gray-400 dark:text-gray-500 font-semibold px-2 mb-1">Оплата</p>
            @foreach ($paymentLinks as $link)
                <a href="{{ $link['url'] }}" class="{{ $base }} {{ $link['check'] ? $active : $idle }}"
                   aria-current="{{ $link['check'] ? 'page' : 'false' }}" title="{{ $link['label'] }}">
                    @themeIcon($link['icon'], 'w-5 text-center')
                    <span x-show="!collapsed">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900"
        style="font-family: {{ $fontBase }};">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                @themeIcon('dashboard')
                <span x-show="!collapsed">Версия CMS:</span>
            </div>
            <strong class="text-gray-900 dark:text-white">1.0</strong>
        </div>
    </div>
</aside>
