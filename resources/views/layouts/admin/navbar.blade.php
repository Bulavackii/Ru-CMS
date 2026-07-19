<header
    x-data="{
        searchOpen: false,
        q: ''
    }"
    @keydown.window.prevent.ctrl.k="searchOpen = !searchOpen; $nextTick(()=> $refs.search?.focus())"
    class="z-30 w-full bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b shadow text-sm text-gray-700 dark:text-gray-300"
>
    @php
        // Подключаем тему в админке: шрифт + набор иконок
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

    @php
        $iconAsset = theme_icon_asset($iconMode ?: 'lucide');
    @endphp
    @if($iconAsset)
        @if($iconMode === 'lucide')
            <script src="{{ $iconAsset }}"></script>
        @else
            <link rel="stylesheet" href="{{ $iconAsset }}" crossorigin="anonymous" referrerpolicy="no-referrer"/>
        @endif
    @endif

    <style>
        /* Применяем шрифт темы ко всей админке */
        body { font-family: {{ $fontBase }}; }
    </style>

    <div class="max-w-screen-2xl mx-auto px-4 py-3 flex items-center gap-3">
        @php
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

            $env = app()->environment();
            $envCls = match ($env) {
                'production' => 'bg-emerald-600',
                'staging'    => 'bg-amber-500',
                'testing'    => 'bg-blue-600',
                default      => 'bg-rose-600',
            };
        @endphp

        <nav class="hidden md:flex items-center gap-2 text-[12px] text-gray-500 dark:text-gray-400">
            <a href="{{ url('/admin/news') }}" class="hover:text-gray-800 dark:hover:text-gray-200">
                @themeIcon('home','mr-1') Панель
            </a>
            @if($section)
                <span class="text-gray-300">/</span>
                <span class="text-gray-800 dark:text-gray-200 font-medium">{{ $section }}</span>
            @endif

            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] text-white {{ $envCls }}">
                {{ strtoupper($env) }}
            </span>

            <div x-data="{open:false}" class="relative">
                <button type="button" @click="open=!open"
                        class="ml-2 inline-flex items-center gap-1 px-2.5 py-1 rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    @themeIcon('plus') <span>Создать</span>
                </button>
                <div x-cloak x-show="open" @click.outside="open=false"
                     class="absolute left-0 mt-2 w-56 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg p-1 z-10">
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
        </nav>

        <div class="ml-auto md:mx-auto flex-1 max-w-2xl">
            <form method="GET" action="{{ route('admin.search.index') }}"
                  :class="searchOpen ? '' : 'hidden md:block'"
                  class="relative">
                <input
                    x-ref="search"
                    type="text"
                    name="q"
                    x-model="q"
                    placeholder="Поиск… (Ctrl + K)"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 px-3 py-2 pl-9 shadow-sm outline-none focus:ring-2 focus:ring-blue-500"
                />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                  @themeIcon('search')
                </span>
                <button type="button"
                        @click="searchOpen = false"
                        class="md:hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    @themeIcon('search')
                </button>
            </form>
        </div>

        <button class="md:hidden ml-1 w-9 h-9 grid place-items-center rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                @click="searchOpen = true"
                aria-label="Открыть поиск">
            @themeIcon('search')
        </button>

        @php
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
            $badge = 'absolute -top-1.5 -right-2 min-w-[18px] px-1 py-0.5 leading-none text-[11px] text-white rounded-full shadow ring-1 ring-black/5 text-center';
            $btn   = 'relative w-9 h-9 grid place-items-center rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition';
        @endphp

        {{-- Уведомление о лицензии --}}
        @if($licenseWarning)
            <a href="{{ route('admin.subscriptions.index') }}" 
               class="{{ $btn }} {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' }}" 
               title="Лицензия {{ $licenseWarning['is_expired'] ? 'истекла' : 'истекает через ' . $licenseWarning['days_left'] . ' ' . ($licenseWarning['days_left'] === 1 ? 'день' : ($licenseWarning['days_left'] < 5 ? 'дня' : 'дней')) }}" 
               aria-label="Лицензия">
                <i class="fas fa-key {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}"></i>
                <span class="{{ $badge }} {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'bg-red-500 animate-pulse' : 'bg-yellow-500' }}">
                    @if($licenseWarning['is_expired'])
                        !
                    @else
                        {{ $licenseWarning['days_left'] }}
                    @endif
                </span>
            </a>
        @endif

        <a href="{{ route('admin.notifications.index') }}" class="{{ $btn }}" title="Уведомления" aria-label="Уведомления">
            @themeIcon('bell')
            @if($unread>0)<span class="{{ $badge }} bg-red-500 animate-pulse">{{ $unread }}</span>@endif
        </a>

        <a href="{{ route('admin.orders.index') }}" class="{{ $btn }}" title="Новые заказы" aria-label="Новые заказы">
            @themeIcon('shopping-cart')
            @if($newOrders>0)<span class="{{ $badge }} bg-green-600">{{ $newOrders }}</span>@endif
        </a>

        <a href="{{ route('admin.messages.index') }}" class="{{ $btn }}" title="Сообщения" aria-label="Сообщения">
            @themeIcon('message')
            @if($unreadMessages>0)<span class="{{ $badge }} bg-indigo-600">{{ $unreadMessages }}</span>@endif
        </a>

        <div class="ml-1"><x-user-dropdown /></div>
    </div>

    <script>
        (function(){
            // Lucide (если выбран режимом темы) — начальный проход, финальный идёт в конце layout после полной загрузки DOM
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                try { window.lucide.createIcons(); } catch (e) {}
            }
        })();
    </script>
</header>
