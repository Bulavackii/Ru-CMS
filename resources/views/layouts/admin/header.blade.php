{{--
    Единая шапка админки. Раньше это были два раздельных бара (header + navbar),
    и часть функциональности в них дублировалась незаметно для глаза:
    — свой Ctrl+K тут и свой — уже внутри components.admin.global-search;
    — простая ссылка-колокольчик со счётчиком рядом с полноценным центром
      уведомлений (components.admin.notifications-center);
    — загрузка шрифта/иконок темы была объявлена только в navbar.blade.php.
    Теперь это один бар, каждая функция — в одном месте.

    26.07.2026: перекомпоновка «работа слева, поиск в центре, личное справа».
    Что изменилось по сути, а не косметически:
    — Поиск стал НАСТОЯЩИМ полем ввода (было: кнопка, стилизованная под поле,
      ввод жил в модалке). Подробности — в components/admin/global-search.
    — Появился переключатель ЯЗЫКА интерфейса (как на сайте). До этого на его
      месте стоял селект СТРАН из модуля Локализация — он выглядел языковым
      («RU RU»), но менял часовой пояс и форматы дат, а стран в базе одна,
      то есть это был список с единственным вариантом. Убран отсюда: страны и
      форматы настраиваются в разделе «Локализация» (ссылка — в меню профиля).
    — Появился переключатель ОФОРМЛЕНИЯ панели — список тем из модуля Темы, как
      на сайте. Заменил кнопку-луну: класс .dark, который она вешала, в
      собранном tailwind.min.css почти ничем не поддержан (в сборке нет
      dark:-вариантов), поэтому «тёмная тема» красила лишь несколько блоков
      с литеральными .dark-правилами и выглядела сломанной.
    — Хлебная крошка «Панель /» убрана: ссылка вела на /admin/news и называлась
      не тем, чем была. Остался сам раздел + отдельная кнопка перехода на сайт.
    — Блок профиля выделен в самостоятельную карточку с аватаром и ролью.
--}}
<header class="admin-glass-dark z-30 w-full border-b border-gray-800 shadow text-sm text-gray-300">
    <div class="admin-accent-bar" aria-hidden="true"></div>

    @php
        // Шрифт и набор иконок темы — общие на всю админку.
        // $activeTheme приходит из ThemeServiceProvider (View::composer('*')) и
        // кешируется; раньше здесь был собственный запрос к visual_themes,
        // выполнявшийся на КАЖДОЙ странице панели — как и в подвале.
        $activeTheme = $activeTheme ?? null;
        $tokens   = $activeTheme->tokens ?? [];
        $config   = $activeTheme->config ?? [];
        $fontBase = data_get($tokens, 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
        $iconMode = data_get($config, 'icon_mode', 'lucide');
        $fontProvider = data_get($config,'font_provider');
        $fontName     = trim((string) data_get($config,'font_name',''));

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

    <style>
        body { font-family: {{ $fontBase }}; }

        /* Оформление шапки — литеральный CSS. В собранном tailwind.min.css нет
           ни dark:-вариантов, ни opacity-модификаторов (bg-white/10), ни
           произвольных значений, поэтому «стеклянные» элементы на утилитах
           просто не отрисовались бы. Акцент берётся из --admin-primary, то
           есть следует за выбранным оформлением панели. */
        .ahd-btn{position:relative;display:grid;place-items:center;width:2rem;height:2rem;
            border:1px solid #374151;color:#d1d5db;transition:background .15s ease,border-color .15s ease,color .15s ease}
        .ahd-btn:hover{background:rgba(255,255,255,.08);
            border-color:color-mix(in srgb, var(--admin-primary,#6366f1) 60%, #374151);color:#fff}
        .ahd-badge{position:absolute;top:-.375rem;right:-.375rem;min-width:1.25rem;height:1.25rem;
            display:flex;align-items:center;justify-content:center;padding:0 .2rem;
            font-size:.68rem;line-height:1;color:#fff;box-shadow:0 2px 6px rgba(0,0,0,.4)}

        /* Иконки собраны в ГРУППЫ-обоймы по смыслу: у обоймы своя рамка и фон,
           а кнопки внутри — без собственных рамок. Раньше это была сплошная
           лента одинаковых квадратов, разделённая только тонкими полосками:
           глаз не видел, где кончается один смысловой блок и начинается
           другой, и весь правый край читался как один ком. */
        /* Высота обоймы = высоте кнопок «Создать» / «На сайт» / поля поиска
           (2rem): всё в полосе должно стоять на одной линии. Полоса сделана
           компактнее — раньше 2.25rem содержимого занимали 56px высоты. */
        .ahd-group{display:inline-flex;align-items:stretch;gap:.125rem;height:2rem;padding:.125rem;
            flex:none;box-sizing:border-box;border:1px solid #374151;background:rgba(255,255,255,.04)}
        .ahd-group .ahd-btn{width:1.75rem;height:100%;border:0;background:transparent}
        .ahd-group .ahd-btn:hover{background:var(--admin-primary-soft,rgba(255,255,255,.1));color:#fff}
        /* Обойма подсвечивается акцентом ТЕМЫ, а не серым: панель следует
           выбранному оформлению, и рамка не должна быть единственным
           местом, которое этого не делает. */
        .ahd-group:hover{border-color:color-mix(in srgb, var(--admin-primary,#6366f1) 55%, #374151)}
        /* Кнопка центра уведомлений приходит из своего компонента со своими
           классами — подгоняем её под размер соседей по обойме */
        .ahd-group > div{display:flex;align-items:stretch}
        .ahd-group > div > button:not(.ahd-btn):not(.ahd-user){position:relative;display:grid;
            place-items:center;width:1.75rem;height:100%;padding:0;color:#d1d5db;
            transition:background .15s ease,color .15s ease}
        .ahd-group > div > button:not(.ahd-btn):not(.ahd-user):hover{background:rgba(255,255,255,.1);color:#fff}
        /* Переключатели языка и оформления — те же кнопки обоймы, но с
           подписью. Подпись моноширинным капсом: это не текст интерфейса,
           а метка состояния, и так она не спорит с названием раздела. */
        .ahd-group .ahd-btn--wide{width:auto;padding:0 .5rem;gap:.35rem;display:inline-flex;align-items:center}
        .ahd-group .ahd-btn--wide span{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
            font-size:.66rem;font-weight:700;letter-spacing:.08em}

        /* Текущий раздел — плашкой на подложке, как надзаголовки в
           разделах панели: раньше подпись висела в воздухе и на тёмном
           фоне читалась как часть ленты кнопок. */
        .ahd-section{display:inline-flex;align-items:center;gap:.4rem;height:2rem;padding:0 .6rem;
            font-size:.65rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
            color:#fff;white-space:nowrap;background:rgba(255,255,255,.06);border:1px solid #374151;
            font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
        a.ahd-section:hover{border-color:color-mix(in srgb, var(--admin-primary,#6366f1) 60%, #374151);
            background:rgba(255,255,255,.1);color:#fff}
        .ahd-section-dot{width:.4rem;height:.4rem;background:var(--admin-primary,#6366f1);flex:none;
            box-shadow:0 0 0 3px var(--admin-primary-soft,color-mix(in srgb, var(--admin-primary, #6366f1) 25%, transparent))}

        /* Кнопка-действие с подписью (Создать / На сайт) */
        .ahd-action{display:inline-flex;align-items:center;gap:.4rem;height:2rem;padding:0 .7rem;
            font-size:.76rem;font-weight:600;white-space:nowrap;transition:filter .15s ease,background .15s ease,color .15s ease}
        .ahd-action--primary{background:var(--admin-primary,#6366f1);color:var(--admin-on-primary,#fff);border:1px solid transparent}
        .ahd-action--primary:hover{filter:brightness(1.12);color:var(--admin-on-primary,#fff)}
        .ahd-action--ghost{border:1px solid #374151;color:#d1d5db}
        .ahd-action--ghost:hover{background:rgba(255,255,255,.08);
            border-color:color-mix(in srgb, var(--admin-primary,#6366f1) 60%, #374151);color:#fff}

        /* Выпадающие меню шапки (язык, оформление, создание, профиль) */
        .ahd-menu{position:absolute;right:0;top:calc(100% + .45rem);z-index:70;min-width:12.5rem;
            padding:.3rem;background:var(--surface,#fff);border:1px solid var(--surface-bd,#e5e7eb);
            box-shadow:0 24px 48px -20px rgba(17,24,39,.55)}
        .dark .ahd-menu{background:#111827;border-color:#374151}
        .ahd-menu--left{right:auto;left:0}
        .ahd-menu-title{padding:.5rem .6rem .3rem;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
            font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
            color:color-mix(in srgb, var(--surface-ink,#111827) 55%, var(--surface,#fff))}
        .ahd-menu-item{display:flex;align-items:center;gap:.6rem;width:100%;padding:.45rem .6rem;
            font-size:.82rem;color:var(--surface-ink,#374151);text-align:left;text-decoration:none;background:none;border:0;
            cursor:pointer;transition:background .12s ease,color .12s ease}
        .dark .ahd-menu-item{color:#d1d5db}
        .ahd-menu-item:hover{background:var(--admin-primary-soft,color-mix(in srgb, var(--admin-primary, #6366f1) 12%, transparent));
            color:var(--admin-primary-ink,#312e81)}
        .dark .ahd-menu-item:hover{color:#c7d2fe}
        .ahd-menu-item.is-active{font-weight:600;color:var(--admin-primary,#4f46e5)}
        .ahd-menu-item--danger{color:#dc2626}
        .ahd-menu-item--danger:hover{background:rgba(220,38,38,.1);color:#b91c1c}
        .ahd-menu-sep{margin:.25rem 0;border-top:1px solid #e5e7eb}
        .dark .ahd-menu-sep{border-color:#374151}
        .ahd-menu-note{margin-left:auto;font-size:.62rem;color:var(--surface-dim,#9ca3af);letter-spacing:.02em}

        /* Флаг локали (тот же приём, что в шапке сайта) */
        .flag{width:1.3rem;height:.92rem;display:inline-block;flex:none;overflow:hidden;
            vertical-align:middle;box-shadow:0 0 0 1px rgba(255,255,255,.25)}
        .ahd-menu .flag{box-shadow:0 0 0 1px rgba(17,24,39,.15)}

        /* Кружок-превью цвета темы */
        .ahd-dot{width:.85rem;height:.85rem;border-radius:999px;flex:none;
            border:1px solid rgba(255,255,255,.3);box-shadow:0 1px 2px rgba(0,0,0,.3)}
        .ahd-menu .ahd-dot{border-color:rgba(17,24,39,.15)}

        /* Блок профиля: отдельная карточка, а не ещё одна иконка в ленте */
        .ahd-user{display:inline-flex;align-items:center;gap:.5rem;height:2rem;padding:0 .5rem 0 .3rem;
            border:1px solid #374151;background:rgba(255,255,255,.05);color:#e5e7eb;
            transition:background .15s ease,border-color .15s ease}
        .ahd-user:hover{background:rgba(255,255,255,.1);border-color:var(--admin-primary,#6366f1)}
        .ahd-user-ava{display:grid;place-items:center;width:1.4rem;height:1.4rem;flex:none;overflow:hidden;
            background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7));
            color:#fff;font-size:.66rem;font-weight:700;letter-spacing:.02em}
        .ahd-user-ava img{width:100%;height:100%;object-fit:cover}
        .ahd-user-text{display:none;line-height:1.1;text-align:left;min-width:0}
        @media (min-width:768px){.ahd-user-text{display:block}}
        .ahd-user-name{display:block;max-width:8.5rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
            font-size:.78rem;font-weight:600}
        .ahd-user-role{display:block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
            font-size:.58rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#9ca3af}
        .ahd-user-caret{font-size:.55rem;opacity:.6}
    </style>

    @php
        // Текущий раздел. Список общий с сайдбаром и поиском
        // (App\Support\AdminSections) — раньше здесь была своя третья копия.
        $route = request()->route()?->getName() ?? '';
        $section = null;
        $sectionUrl = null;
        foreach (\App\Support\AdminSections::all() as $item) {
            $match = $item['is_route']
                ? ($route !== '' && request()->routeIs($item['pattern']))
                : request()->is($item['pattern']);

            if ($match) {
                $section = $item['label'];
                // Адрес раздела: с формы создания или правки подпись
                // возвращает к списку — раньше это была мёртвая надпись.
                $sectionUrl = $item['url'] ?? null;
                break;
            }
        }

        // Счётчики берём из общего источника (App\Support\AdminCounters):
        // те же числа показывает сайдбар, и считаются они один раз за запрос,
        // а не отдельно в каждом шаблоне.
        $counters = \App\Support\AdminCounters::all();
        $newOrders = $counters['orders'];
        $unreadMessages = $counters['messages'];

        $licenseWarning = null;
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

        // Язык интерфейса. Названия — на самом языке, как принято в
        // переключателях: их не переводят, иначе искать свой язык неудобно.
        $langNames = ['ru'=>'Русский','en'=>'English'];
        $curLocale = app()->getLocale();

        // Оформление — из ThemeServiceProvider. Личного выбора нет: тема одна
        // на сайт и на панель, хранится в базе и потому одинакова у всех.
        $panelThemes = $panelThemes ?? collect();
        $panelActive = $panelThemes->firstWhere('is_default', true);
        $panelPrimary = $panelActive->primary ?? '#6366f1';
        $panelTitle = $panelActive->title ?? __('admin.header.theme');

        // Профиль
        $me = Auth::user();
        $myName = $me->name ?? __('admin.header.role_admin');
        $myRole = ($me->is_admin ?? false) ? __('admin.header.role_admin') : __('admin.header.role_user');
        $myInitials = mb_strtoupper(mb_substr(trim($myName), 0, 1)) ?: 'A';
    @endphp

    {{-- Полоса тянется на всю ширину области содержимого. Раньше здесь стоял
         max-w-screen-2xl mx-auto: при широком окне контейнер оказывался уже
         доступного места и центрировался, из-за чего у обоих краёв оставались
         пустые поля, а весь ряд кнопок «висел» посередине. --}}
    <div class="w-full px-4 py-1.5 flex flex-wrap items-center gap-x-2 gap-y-2">

        {{-- ══ Слева: где я и что могу сделать ══ --}}
        <div class="flex items-center gap-2 flex-wrap flex-none">
            @if($sectionUrl)
                <a href="{{ $sectionUrl }}" class="ahd-section" aria-label="{{ __('admin.header.section') }}">
                    <span class="ahd-section-dot" aria-hidden="true"></span>
                    {{ $section }}
                </a>
            @else
                <span class="ahd-section" aria-label="{{ __('admin.header.section') }}">
                    <span class="ahd-section-dot" aria-hidden="true"></span>
                    {{ $section ?? __('admin.header.overview') }}
                </span>
            @endif

            <div x-data="{open:false}" class="relative">
                <button type="button" @click="open=!open" @click.outside="open=false"
                        @keydown.escape.window="open=false"
                        class="ahd-action ahd-action--primary admin-clip-corner" :aria-expanded="open.toString()">
                    @themeIcon('plus') <span>{{ __('admin.header.create') }}</span>
                </button>
                <div x-cloak x-show="open" x-transition.opacity.duration.120ms class="ahd-menu ahd-menu--left">
                    @if(Route::has('admin.news.create'))
                        <a class="ahd-menu-item" href="{{ route('admin.news.create') }}">
                            @themeIcon('file-text','w-4 text-center') {{ __('admin.header.create_news') }}</a>
                    @endif
                    @if(Route::has('admin.pages.create'))
                        <a class="ahd-menu-item" href="{{ route('admin.pages.create') }}">
                            @themeIcon('file-text','w-4 text-center') {{ __('admin.header.create_page') }}</a>
                    @endif
                    @if(Route::has('admin.categories.create'))
                        <a class="ahd-menu-item" href="{{ route('admin.categories.create') }}">
                            @themeIcon('folder','w-4 text-center') {{ __('admin.header.create_category') }}</a>
                    @endif
                    @if(Route::has('admin.slideshow.create'))
                        <a class="ahd-menu-item" href="{{ route('admin.slideshow.create') }}">
                            @themeIcon('image','w-4 text-center') {{ __('admin.header.create_slideshow') }}</a>
                    @endif
                    @if(Route::has('admin.visual.fragments.create'))
                        <a class="ahd-menu-item" href="{{ route('admin.visual.fragments.create') }}">
                            @themeIcon('puzzle','w-4 text-center') {{ __('admin.header.create_fragment') }}</a>
                    @endif
                </div>
            </div>

            {{-- Переход на сайт: самостоятельное действие, в новой вкладке --}}
            <a href="{{ url('/') }}" target="_blank" rel="noopener"
               class="ahd-action ahd-action--ghost" title="{{ __('admin.header.site_title') }}">
                <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                <span class="hidden md:inline">{{ __('admin.header.site') }}</span>
            </a>
        </div>

        {{-- ══ Центр: поиск. Занимает всё, что осталось между краями ══ --}}
        <div class="order-last w-full sm:order-none sm:w-auto sm:flex-1 sm:min-w-0 sm:max-w-2xl">
            @include('components.admin.global-search')
        </div>

        {{-- ══ Справа: работа → инструменты → личное ══ --}}
        <div class="ml-auto flex items-center gap-2 flex-none">

            {{-- Обойма 1: то, что требует внимания и меняется само --}}
            <div class="ahd-group">
                @include('components.admin.notifications-center')

                {{-- Заказы и сообщения приходят из модулей Payments и Messages.
                     Выключили модуль — кнопка уходит из шапки вместе с разделом
                     в левом меню, иначе она вела бы в раздел, которого для
                     администратора больше нет. Остальные кнопки обоймы ведут в
                     ядро и есть всегда. --}}
                @if(module_enabled('Payments'))
                    <a href="{{ route('admin.orders.index') }}" class="ahd-btn" title="{{ __('admin.header.orders') }}"
                       aria-label="{{ __('admin.header.orders') }}">
                        @themeIcon('shopping-cart')
                        @if($newOrders>0)<span class="ahd-badge bg-green-600">{{ $newOrders }}</span>@endif
                    </a>
                @endif

                @if(module_enabled('Messages'))
                    <a href="{{ route('admin.messages.index') }}" class="ahd-btn" title="{{ __('admin.header.messages') }}"
                       aria-label="{{ __('admin.header.messages') }}">
                        @themeIcon('message')
                        @if($unreadMessages>0)<span class="ahd-badge bg-indigo-500">{{ $unreadMessages }}</span>@endif
                    </a>
                @endif

                @if($licenseWarning && Route::has('admin.subscriptions.index'))
                    <a href="{{ route('admin.subscriptions.index') }}"
                       class="ahd-btn {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'text-red-400' : 'text-yellow-400' }}"
                       title="{{ __('admin.header.license') }}: {{ $licenseWarning['is_expired'] ? __('admin.header.license_expired') : __('admin.header.license_expires') . ' ' . $licenseWarning['days_left'] }}"
                       aria-label="{{ __('admin.header.license') }}">
                        <i class="fas fa-key"></i>
                        <span class="ahd-badge {{ $licenseWarning['is_expired'] || $licenseWarning['is_critical'] ? 'bg-red-500 animate-pulse' : 'bg-yellow-500' }}">
                            {{ $licenseWarning['is_expired'] ? '!' : $licenseWarning['days_left'] }}
                        </span>
                    </a>
                @endif
            </div>

            {{-- Служебные страницы — одной кнопкой с меню. Раньше это была
                 обойма из трёх иконок, спрятанная на узких экранах
                 (hidden md:inline-flex): с телефона три страницы были
                 недостижимы из шапки вовсе. Подписи уточнены: «глобус»
                 показывает геолокацию и устройство ТЕКУЩЕГО администратора,
                 а не пользователей сайта. --}}
            <div x-data="{open:false}" class="relative">
                <button type="button" @click="open=!open" @click.outside="open=false"
                        @keydown.escape.window="open=false"
                        class="ahd-btn" title="{{ __('admin.header.tools') }}"
                        aria-label="{{ __('admin.header.tools') }}" :aria-expanded="open.toString()">
                    <i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>
                </button>

                <div x-cloak x-show="open" x-transition.opacity.duration.120ms class="ahd-menu">
                    <p class="ahd-menu-title">{{ __('admin.header.tools') }}</p>

                    <a href="{{ route('admin.error.report') }}" class="ahd-menu-item">
                        <i class="fas fa-bug w-4 text-center"></i>{{ __('admin.header.report_bug') }}
                    </a>
                    <a href="{{ route('admin.geolocation') }}" class="ahd-menu-item">
                        <i class="fas fa-location-dot w-4 text-center"></i>{{ __('admin.header.geolocation') }}
                    </a>
                    <a href="{{ route('admin.system_info') }}" class="ahd-menu-item">
                        <i class="fas fa-circle-info w-4 text-center"></i>{{ __('admin.header.system_info') }}
                    </a>
                </div>
            </div>

            {{-- Обойма 3: личное — язык и оформление --}}
            <div class="ahd-group">
            <div x-data="{open:false}" class="relative">
                <button type="button" @click="open=!open" @click.outside="open=false"
                        @keydown.escape.window="open=false"
                        class="ahd-btn ahd-btn--wide"
                        title="{{ __('admin.header.language') }}" :aria-expanded="open.toString()">
                    {!! locale_flag($curLocale) !!}
                    <span class="hidden lg:inline text-xs font-semibold">{{ strtoupper($curLocale) }}</span>
                </button>
                <div x-cloak x-show="open" x-transition.opacity.duration.120ms class="ahd-menu">
                    <p class="ahd-menu-title">{{ __('admin.header.language') }}</p>
                    @foreach(available_locales() as $code)
                        <a href="{{ route('frontend.locale.set', $code) }}"
                           class="ahd-menu-item {{ $code === $curLocale ? 'is-active' : '' }}">
                            {!! locale_flag($code) !!}
                            <span>{{ $langNames[$code] ?? strtoupper($code) }}</span>
                            @if($code === $curLocale)<i class="fas fa-check ahd-menu-note"></i>@endif
                        </a>
                    @endforeach
                </div>
            </div>

            @if($panelThemes->isNotEmpty())
                <div x-data="{open:false}" class="relative">
                    <button type="button" @click="open=!open" @click.outside="open=false"
                            @keydown.escape.window="open=false"
                            class="ahd-btn ahd-btn--wide"
                            title="{{ __('admin.header.theme') }}" :aria-expanded="open.toString()">
                        <span class="ahd-dot" style="background: {{ $panelPrimary }}"></span>
                        <span class="hidden xl:inline text-xs font-semibold">{{ $panelTitle }}</span>
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity.duration.120ms class="ahd-menu">
                        <p class="ahd-menu-title">{{ __('admin.header.theme') }}</p>
                        @foreach($panelThemes as $themeOption)
                            <a href="{{ route('admin.theme.set', $themeOption->slug) }}"
                               class="ahd-menu-item {{ $themeOption->is_default ? 'is-active' : '' }}">
                                <span class="ahd-dot" style="background: {{ $themeOption->primary }}"></span>
                                <span>{{ $themeOption->title }}</span>
                                @if($themeOption->is_default)
                                    <span class="ahd-menu-note">{{ __('admin.header.theme_site') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            </div>

            {{-- Профиль — самостоятельная карточка с аватаром и ролью,
                 а не ещё одна иконка в общей ленте --}}
            <div x-data="{open:false}" class="relative">
                <button type="button" @click="open=!open" @click.outside="open=false"
                        @keydown.escape.window="open=false"
                        class="ahd-user" :aria-expanded="open.toString()">
                    <span class="ahd-user-ava">
                        @if($me?->avatar)
                            <img src="{{ asset($me->avatar) }}" alt="">
                        @else
                            {{ $myInitials }}
                        @endif
                    </span>
                    <span class="ahd-user-text">
                        <span class="ahd-user-name">{{ $myName }}</span>
                        <span class="ahd-user-role">{{ $myRole }}</span>
                    </span>
                    <i class="fas fa-chevron-down ahd-user-caret" aria-hidden="true"></i>
                </button>

                <div x-cloak x-show="open" x-transition.opacity.duration.120ms class="ahd-menu">
                    <p class="ahd-menu-title">{{ $myName }}</p>

                    <a href="{{ url('/dashboard') }}" class="ahd-menu-item">
                        <i class="fas fa-user w-4 text-center"></i>{{ __('admin.header.profile') }}
                    </a>
                    <a href="{{ route('admin.account.settings') }}" class="ahd-menu-item">
                        <i class="fas fa-sliders w-4 text-center"></i>{{ __('admin.header.account_settings') }}
                    </a>
                    @if($me && Route::has('admin.users.password.edit'))
                        <a href="{{ route('admin.users.password.edit', $me->id) }}" class="ahd-menu-item">
                            <i class="fas fa-key w-4 text-center"></i>{{ __('admin.header.password') }}
                        </a>
                    @endif
                    @if(Route::has('admin.localization.index'))
                        {{-- Страны, часовые пояса и форматы дат: сюда переехал
                             прежний селект стран из шапки --}}
                        <a href="{{ route('admin.localization.index') }}" class="ahd-menu-item">
                            <i class="fas fa-globe w-4 text-center"></i>{{ __('admin.header.localization') }}
                        </a>
                    @endif

                    <div class="ahd-menu-sep"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="ahd-menu-item ahd-menu-item--danger">
                            <i class="fas fa-right-from-bracket w-4 text-center"></i>{{ __('admin.header.logout') }}
                        </button>
                    </form>
                </div>
            </div>
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

{{-- 🧩 Зона фрагмента: полоса под шапкой панели (объявления для редакторов).
     Пусто или выключено — не выводится ничего. --}}
@php $fragmentAdminHeader = \Modules\Visual\Support\FragmentRenderer::zone('admin.header'); @endphp
@if($fragmentAdminHeader)
    <div class="fragment-zone fragment-zone--admin-header">{!! $fragmentAdminHeader !!}</div>
@endif
