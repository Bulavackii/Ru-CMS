{{--
    Левый сайдбар админки. Статичный: фиксированная компактная ширина (15rem),
    без переключателя «свернуть/развернуть» и без localStorage-состояния —
    раньше сайдбар мог схлопываться в узкую иконочную полосу, сейчас всегда
    один и тот же аккуратный вид. На мобильных (< lg) скрыт целиком: там уже
    есть отдельный выдвижной drawer (layouts/admin/mobile-menu.blade.php),
    так что дублировать навигацию свёрнутой иконочной полосой не нужно.
--}}
<aside class="admin-glass hidden lg:flex fixed top-0 left-0 h-screen w-60 flex-col z-40 border-r border-gray-200 dark:border-gray-800 shadow-lg">
    @php
        $fontBase = data_get(($activeTheme ?? null)?->tokens ?? [], 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
    @endphp

    {{-- Шапка: логотип ведёт на дашборд — единственная ссылка на него --}}
    <div class="h-14 flex-shrink-0 flex items-center px-4 border-b border-gray-200 dark:border-gray-800" style="font-family: {{ $fontBase }};">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 group min-w-0" title="Панель управления">
            {{-- Значок — не «RU» (это дублировало бы подпись рядом), а «слои»:
                 тот же смысловой символ модульности, что и в шапке мастера
                 установки (modules/Install/Views/welcome.blade.php). --}}
            <span class="flex-shrink-0 grid place-items-center w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-md transition-transform group-hover:scale-105">
                <i class="fas fa-layer-group text-sm"></i>
            </span>
            <span class="min-w-0 leading-tight">
                <span class="block text-sm font-bold text-gray-900 dark:text-white tracking-tight truncate">RU CMS</span>
                <span class="block text-xs text-gray-400 dark:text-gray-500 truncate">Панель управления</span>
            </span>
        </a>
    </div>

    @php
        $contentLinks = [
            // Ссылка на дашборд — в шапке сайдбара (логотип «RU»), отдельного пункта нет.
            ['route' => route('admin.menus.index'),      'route_name' => 'admin.menus.*',      'label' => 'Меню',       'icon' => 'dashboard'],
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
                // Страны, форматы дат/валют и графический редактор переводов.
                // Route::has — на случай, если модуль Localization отключён.
                Route::has('admin.localization.index')
                    ? [
                        'url' => route('admin.localization.index'),
                        'check' => request()->routeIs('admin.localization.*'),
                        'label' => 'Локализация',
                        'icon'  => 'globe',
                    ]
                    : null,
            ]),
        );

        // Раньше «Спецвозможности» жили в отдельной группе ради одного пункта —
        // при переходе на компактный, помещающийся без прокрутки сайдбар
        // отдельный заголовок группы того не стоил, присоединили к «Система».
        $systemLinks[] = [
            'url' => '/admin/accessibility',
            'check' => request()->is('admin/accessibility*'),
            'label' => 'Спецвозможности',
            'icon' => 'user',
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

        $groups = [
            'Контент' => $contentLinks,
            'Система' => $systemLinks,
            'Оплата'  => $paymentLinks,
        ];

        $base   = 'flex items-center gap-2.5 px-2.5 py-1 rounded-lg text-sm transition-colors';
        $active = 'bg-indigo-600 dark:bg-indigo-500 text-white font-semibold shadow-sm';
        $idle   = 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white';
    @endphp

    {{-- overflow-y-auto — не «дизайн», а страховка: без прокрутки должно
         помещаться всё при обычной высоте окна, но если когда-нибудь
         включат все опциональные модули разом (Route::has-пункты) и список
         вырастет на маленьком экране — пункты не должны стать недоступны. --}}
    <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-3" style="font-family: {{ $fontBase }};" aria-label="Основная навигация">
        @foreach ($groups as $title => $links)
            @php $links = array_map(fn($l) => $l + ['route_name' => null], $links); @endphp
            @if(count($links))
                {{-- Разделитель между смысловыми блоками — не просто отступ,
                     а тонкая линия сверху у каждой группы, кроме первой. --}}
                <div class="{{ $loop->first ? '' : 'pt-3 border-t border-gray-200 dark:border-gray-800' }}">
                    <p class="px-2.5 mb-1 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        {{ $title }}
                    </p>
                    <div class="space-y-0.5">
                        @foreach ($links as $link)
                            @php
                                $href = $link['route'] ?? $link['url'];
                                $isActive = $link['route_name'] ? request()->routeIs($link['route_name']) : $link['check'];
                            @endphp
                            <a href="{{ $href }}" class="{{ $base }} {{ $isActive ? $active : $idle }}"
                               aria-current="{{ $isActive ? 'page' : 'false' }}" title="{{ $link['label'] }}">
                                @themeIcon($link['icon'], 'w-4 text-center flex-shrink-0')
                                <span class="truncate">{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    {{-- Версия — из конфига (единый источник с UpdateService::checkForUpdates()),
         а не отдельный захардкоженный литерал здесь. --}}
    <div class="flex-shrink-0 px-4 py-2 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs text-gray-400 dark:text-gray-500" style="font-family: {{ $fontBase }};">
        <span>Версия</span>
        <span class="font-mono font-semibold text-gray-500 dark:text-gray-400">{{ config('app.version', '1.0.0') }}</span>
    </div>
</aside>
