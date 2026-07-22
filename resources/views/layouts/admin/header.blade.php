{{--
    Единая шапка админки. Раньше это были два раздельных бара (header + navbar),
    и часть функциональности в них дублировалась незаметно для глаза:
    — свой Ctrl+K тут (`searchOpen`) и свой — уже внутри components.admin.global-search;
    — простая ссылка-колокольчик со счётчиком (тот же Notification::where('enabled',1))
      рядом с полноценным центром уведомлений (components.admin.notifications-center);
    — загрузка шрифта/иконок темы была объявлена только в navbar.blade.php.
    Теперь это один бар, каждая функция — в одном месте.
--}}
<header class="admin-glass-dark z-30 w-full border-b border-gray-800 shadow text-sm text-gray-300">
    @php
        // Шрифт и набор иконок темы — общие на всю админку.
        $activeTheme = \Modules\Visual\Models\Theme::where('is_default', true)->first();
        $tokens   = $activeTheme->tokens ?? [];
        $config   = $activeTheme->config ?? [];
        $fontBase = data_get($tokens, 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
        $iconMode = data_get($config, 'icon_mode', 'lucide');
        $fontProvider = data_get($config,'font_provider');
        $fontName     = trim((string) data_get($config,'font_name',''));
        $iconsPath    = rtrim((string) data_get($config,'icons_path',''),'/');

        $localFontSlug = null;
        if ($fontProvider === 'local' && $fontName !== '') {
            $slug = \Illuminate\Support\Str::slug($fontName);
            $localFontSlug = array_key_exists($slug, LOCAL_FONTS) ? $slug : null;
        }
    @endphp

    {{-- Подключение шрифта/иконок: локальный (по умолчанию — Inter), без внешних CDN --}}
    @if ($localFontSlug)
        <link rel="stylesheet" href="{{ local_font_css($localFontSlug) }}">
    @elseif ($fontProvider === 'google' && $fontName !== '')
        <link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontName) }}:wght@400;500;600;700&display=swap" rel="stylesheet">
    @elseif($fontProvider === 'bunny' && $fontName !== '')
        <link href="https://fonts.bunny.net/css?family={{ urlencode(str_replace(' ', '-', $fontName)) }}:400,500,600,700" rel="stylesheet">
    @else
        <link rel="stylesheet" href="{{ local_font_css('inter') }}">
    @endif

    @php $iconAsset = theme_icon_asset($iconMode ?: 'lucide'); @endphp
    @if($iconAsset)
        @if($iconMode === 'lucide')
            <script src="{{ $iconAsset }}"></script>
        @else
            <link rel="stylesheet" href="{{ $iconAsset }}" crossorigin="anonymous" referrerpolicy="no-referrer"/>
        @endif
    @endif

    <style>body { font-family: {{ $fontBase }}; }</style>

    @php
        // Хлебная крошка раздела
        $route = request()->route()?->getName() ?? '';
        $map = [
            'admin.menus.'              => 'Меню',
            'admin.news.'               => 'Новости',
            'admin.pages.'              => 'Страницы',
            'admin.categories.'         => 'Категории',
            'admin.slideshow.'          => 'Слайдшоу',
            'admin.files.'              => 'Файлы',
            'admin.search.'             => 'Поиск',
            'admin.notifications.'      => 'Уведомления',
            'admin.visual.themes.'      => 'Темы',
            'admin.visual.fragments.'   => 'Фрагменты',
            'admin.users.'              => 'Пользователи',
            'admin.payments.'           => 'Оплата',
            'admin.orders.'             => 'Заказы',
            'admin.delivery.'           => 'Доставка',
        ];
        $section = null;
        foreach ($map as $prefix => $label) {
            if (str_starts_with($route, $prefix)) { $section = $label; break; }
        }

        // Счётчики и предупреждения для правого кластера кнопок
        $unread = 0; $newOrders = 0; $unreadMessages = 0; $licenseWarning = null;
        try { if (class_exists(\Modules\Notifications\Models\Notification::class)) $unread = \Modules\Notifications\Models\Notification::where('enabled',1)->count(); } catch (\Throwable $e) {}
        try { if (class_exists(\Modules\Payments\Models\Order::class))         $newOrders = \Modules\Payments\Models\Order::where('is_new',true)->count(); } catch (\Throwable $e) {}
        try {
            if (class_exists(\Modules\Messages\Models\Message::class) && Auth::check()) {
                $unreadMessages = \Modules\Messages\Models\Message::where('to_user_id', Auth::id())
                    ->where('is_read', false)
                    ->notArchived()
                    ->count();
            }
        } catch (\Throwable $e) {}
        try {
            $subscriptionService = app(\App\Services\SubscriptionService::class);
            $licenseInfo = $subscriptionService->getLicenseInfo();
            if ($licenseInfo && ($licenseInfo['is_expiring_soon'] || $licenseInfo['is_expired'])) {
                $licenseWarning = [
                    'days_left' => $licenseInfo['days_left'],
                    'is_critical' => $licenseInfo['is_critical'],
                    'is_expired' => $licenseInfo['is_expired'],
                ];
            }
        } catch (\Throwable $e) {}

        $badge = 'absolute -top-1.5 -right-1.5 w-5 h-5 flex items-center justify-center text-xs text-white rounded-full shadow';
        $btn   = 'relative w-9 h-9 grid place-items-center rounded-lg border border-gray-700 hover:bg-gray-800 hover:border-gray-600 transition';
    @endphp

    <div class="max-w-screen-2xl mx-auto px-4 py-2.5 flex flex-wrap items-center gap-2">

        {{-- ── Левый кластер: где я / быстрое создание ──────────────────── --}}
        <div class="flex items-center gap-2 flex-wrap">
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <a href="{{ url('/admin/news') }}" class="inline-flex items-center gap-1 hover:text-white transition">
                    @themeIcon('home') Панель
                </a>
                @if($section)
                    <span class="text-gray-600">/</span>
                    <span class="text-gray-200 font-medium">{{ $section }}</span>
                @endif
            </nav>

            <div x-data="{open:false}" class="relative">
                <button type="button" @click="open=!open"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition">
                    @themeIcon('plus') <span>Создать</span>
                </button>
                <div x-cloak x-show="open" @click.outside="open=false"
                     class="absolute left-0 mt-2 w-56 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl p-1 z-20 text-gray-700 dark:text-gray-200">
                    @if(Route::has('admin.news.create'))
                        <a class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                           href="{{ route('admin.news.create') }}">@themeIcon('file-text','w-4 text-center') Новость</a>
                    @endif
                    @if(Route::has('admin.pages.create'))
                        <a class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                           href="{{ route('admin.pages.create') }}">@themeIcon('file-text','w-4 text-center') Страницу</a>
                    @endif
                    @if(Route::has('admin.categories.create'))
                        <a class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                           href="{{ route('admin.categories.create') }}">@themeIcon('folder','w-4 text-center') Категорию</a>
                    @endif
                    @if(Route::has('admin.slideshow.create'))
                        <a class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                           href="{{ route('admin.slideshow.create') }}">@themeIcon('image','w-4 text-center') Слайдшоу</a>
                    @endif
                    @if(Route::has('admin.visual.fragments.create'))
                        <a class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                           href="{{ route('admin.visual.fragments.create') }}">@themeIcon('puzzle','w-4 text-center') Фрагмент</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Поиск — по центру, тянется на всю оставшуюся ширину --}}
        <div class="order-last w-full sm:order-none sm:w-auto sm:flex-1 sm:max-w-md sm:mx-3">
            @include('components.admin.global-search')
        </div>

        {{-- ── Правый кластер: инструменты и аккаунт ─────────────────────── --}}
        <div class="ml-auto sm:ml-0 flex items-center gap-1.5 flex-wrap">
            @include('components.admin.notifications-center')

            {{-- Лицензия --}}
            @if($licenseWarning)
                <a href="{{ route('admin.subscriptions.index') }}"
                   class="{{ $btn }} {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'border-red-500' : 'border-yellow-500' }}"
                   title="Лицензия {{ $licenseWarning['is_expired'] ? 'истекла' : 'истекает через ' . $licenseWarning['days_left'] . ' ' . ($licenseWarning['days_left'] === 1 ? 'день' : ($licenseWarning['days_left'] < 5 ? 'дня' : 'дней')) }}"
                   aria-label="Лицензия">
                    <i class="fas fa-key {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'text-red-400' : 'text-yellow-400' }}"></i>
                    <span class="{{ $badge }} {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'bg-red-500 animate-pulse' : 'bg-yellow-500' }}">
                        {{ $licenseWarning['is_expired'] ? '!' : $licenseWarning['days_left'] }}
                    </span>
                </a>
            @endif

            <a href="{{ route('admin.orders.index') }}" class="{{ $btn }}" title="Новые заказы" aria-label="Новые заказы">
                @themeIcon('shopping-cart')
                @if($newOrders>0)<span class="{{ $badge }} bg-green-600">{{ $newOrders }}</span>@endif
            </a>

            <a href="{{ route('admin.messages.index') }}" class="{{ $btn }}" title="Сообщения" aria-label="Сообщения">
                @themeIcon('message')
                @if($unreadMessages>0)<span class="{{ $badge }} bg-indigo-500">{{ $unreadMessages }}</span>@endif
            </a>

            <a href="{{ route('admin.error.report') }}" class="{{ $btn }}" title="Сообщить об ошибке" aria-label="Сообщить об ошибке">
                @themeIcon('bug')
            </a>

            <a href="{{ route('admin.geolocation') }}" class="{{ $btn }}" title="Геолокация пользователей" aria-label="Геолокация">
                @themeIcon('globe')
            </a>

            <a href="{{ route('admin.system_info') }}" class="{{ $btn }}" title="Информация о сервере и конфигурации" aria-label="Система">
                @themeIcon('cog')
            </a>

            @if(class_exists(\Modules\Localization\Views\Components\CountrySwitcher::class))
                <div class="hidden sm:block">
                    <x-country-switcher />
                </div>
            @endif

            @include('components.admin.dark-mode-toggle')

            <div class="w-px h-6 bg-gray-700 mx-1 hidden sm:block" aria-hidden="true"></div>

            <x-user-dropdown />
        </div>
    </div>

    <script>
        (function(){
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                try { window.lucide.createIcons(); } catch (e) {}
            }
        })();
    </script>
</header>
