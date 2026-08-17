{{--
    Левый сайдбар админки. Статичный: фиксированная компактная ширина (15rem),
    без переключателя «свернуть/развернуть». Ниже брейкпоинта lg скрыт целиком —
    там работает выдвижной drawer (layouts/admin/mobile-menu.blade.php), который
    читает тот же список разделов.

    26.07.2026:
    — Разделы берутся из App\Support\AdminSections — общий список с шапкой,
      мобильным меню и глобальным поиском.
    — Логотип остался единственной ссылкой на дашборд (отдельный пункт рядом
      с ним был бы вторым таким же), но теперь подсвечивается, когда дашборд
      открыт: раньше по сайдбару вообще нельзя было понять, что ты на нём.
    — Цвета взяты из темы (--admin-primary / --admin-accent), а не из литералов
      bg-indigo-600 / from-indigo-500: раньше сайдбар брал у темы только шрифт
      и при переключении оформления не менялся вовсе.
    — У разделов с новым (заказы, уведомления) появились счётчики.
    — Версии здесь больше нет: она в подвале панели, в одном месте со стеком.
--}}
@php
    $fontBase = data_get(($activeTheme ?? null)?->tokens ?? [], 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');

    $dashboard = \App\Support\AdminSections::dashboard();
    $groups    = \App\Support\AdminSections::groups();
    $counters  = \App\Support\AdminCounters::all();

    // Активен ли пункт. У SEO маршруты живут в своём модуле, поэтому раздел
    // считается активным и по имени маршрута, и по пути.
    $isActive = function (array $link): bool {
        $active = $link['is_route']
            ? request()->routeIs($link['pattern'])
            : request()->is($link['pattern']);

        return $active || (isset($link['also']) && request()->is($link['also']));
    };

    // Дашборд открыт — подсвечиваем логотип: он единственная ссылка на него
    $dashboardActive = $dashboard ? $isActive($dashboard) : false;
@endphp

<aside class="admin-glass hidden lg:flex fixed top-0 left-0 h-screen w-60 flex-col z-40 border-r border-gray-200 dark:border-gray-800 shadow-lg"
       style="font-family: {{ $fontBase }};">

    {{-- Шапка: логотип ведёт на дашборд --}}
    <div class="h-14 flex-shrink-0 flex items-center px-4 border-b border-gray-200 dark:border-gray-800">
        <a href="{{ $dashboard['url'] ?? url('/admin') }}"
           class="asb-brand flex items-center gap-2.5 group min-w-0 {{ $dashboardActive ? 'is-active' : '' }}"
           aria-current="{{ $dashboardActive ? 'page' : 'false' }}"
           title="Панель управления">
            {{-- Значок — «слои»: тот же символ модульности, что в шапке мастера
                 установки. Цвет — из активного оформления панели. --}}
            <span class="asb-logo" aria-hidden="true">
                <i class="fas fa-layer-group text-sm"></i>
            </span>
            <span class="min-w-0 leading-tight">
                <span class="block text-sm font-bold text-gray-900 dark:text-white tracking-tight truncate">Nexum Core</span>
                <span class="block text-xs text-gray-400 dark:text-gray-500 truncate">Dashboard</span>
            </span>
        </a>
    </div>

    {{-- overflow-y-auto — страховка: при обычной высоте окна всё помещается,
         но если включить разом все опциональные модули на маленьком экране,
         пункты не должны стать недоступны. --}}
    <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-3" aria-label="Основная навигация">
        @foreach ($groups as $title => $links)
            @continue(! count($links))
            <div class="{{ $loop->first ? '' : 'pt-3 border-t border-gray-200 dark:border-gray-800' }}">
                <p class="asb-group">
                    <span class="asb-group-dot" aria-hidden="true"></span>
                    {{ $title }}
                </p>
                <div class="space-y-0.5">
                    @foreach ($links as $link)
                        @php
                            $active = $isActive($link);
                            $count  = $link['counter'] ? ($counters[$link['counter']] ?? 0) : 0;
                        @endphp
                        <a href="{{ $link['url'] }}"
                           class="asb-item {{ $active ? 'is-active' : '' }}"
                           aria-current="{{ $active ? 'page' : 'false' }}"
                           title="{{ $link['label'] }}">
                            @themeIcon($link['icon'], 'w-4 text-center flex-shrink-0')
                            <span class="truncate flex-1">{{ $link['label'] }}</span>
                            @if($count > 0)
                                <span class="asb-count">{{ $count > 99 ? '99+' : $count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <style>
        /* Цвета берутся из активного оформления панели: --admin-primary и
           --admin-accent задаёт layouts/admin.blade.php по выбранной теме.
           Литеральный CSS, а не Tailwind-утилиты: в собранном
           tailwind.min.css нет ни произвольных значений, ни dark:-вариантов
           (см. CLAUDE.md), поэтому раньше здесь стояли жёсткие indigo-литералы
           и сайдбар не реагировал на смену темы вовсе. */
        .asb-logo{display:grid;place-items:center;width:2rem;height:2rem;flex:none;color:var(--admin-on-primary,#fff);
            background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7));
            box-shadow:0 4px 10px -4px var(--admin-primary-glow,rgba(79,70,229,.5));
            transition:transform .15s ease}
        .group:hover .asb-logo{transform:scale(1.05)}
        /* Логотип — единственная ссылка на дашборд, поэтому у него тоже есть
           состояние «я сейчас здесь»: без него по сайдбару нельзя было понять,
           что открыт именно дашборд */
        .asb-brand{text-decoration:none}
        .asb-brand.is-active .asb-logo{box-shadow:0 0 0 2px var(--admin-accent,#a855f7),
            0 4px 10px -4px var(--admin-primary-glow,rgba(79,70,229,.5))}

        /* Пункт навигации. Активный держит слева акцентную грань — по ней видно
           текущий раздел, даже когда заливка неразличима на тёмной теме. */
        .asb-item{display:flex;align-items:center;gap:.6rem;padding:.3rem .6rem;font-size:.875rem;
            color:var(--surface-ink,#4b5563);text-decoration:none;border-left:2px solid transparent;
            transition:background .15s ease,color .15s ease,border-color .15s ease}
        .dark .asb-item{color:#9ca3af}
        .asb-item:hover{background:var(--admin-primary-soft,color-mix(in srgb, var(--admin-primary, #6366f1) 10%, transparent));
            color:var(--admin-primary-ink,#312e81);border-left-color:var(--admin-primary,#6366f1)}
        .dark .asb-item:hover{color:#e5e7eb}
        .asb-item.is-active{color:var(--admin-on-primary,#fff);font-weight:600;border-left-color:var(--admin-accent,#a855f7);
            background:linear-gradient(90deg,var(--admin-primary,#6366f1),transparent 340%)}
        .asb-item.is-active:hover{color:var(--admin-on-primary,#fff)}

        /* Заголовок группы */
        .asb-group{display:flex;align-items:center;gap:.4rem;padding:0 .6rem;margin-bottom:.25rem;
            font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--surface-dim,#9ca3af)}
        .asb-group-dot{width:.25rem;height:.25rem;border-radius:999px;flex:none;
            background:var(--admin-primary,#6366f1)}

        /* Счётчик «есть новое» */
        .asb-count{flex:none;min-width:1.25rem;padding:.05rem .3rem;text-align:center;
            font-size:.65rem;font-weight:700;line-height:1.4;color:var(--admin-on-primary,#fff);
            background:var(--admin-primary,#6366f1)}
        .asb-item.is-active .asb-count{color:var(--admin-primary,#4f46e5);background:var(--surface,#fff)}

    </style>

</aside>
