{{--
    Выдвижное меню панели для экранов меньше lg (там сайдбар скрыт).

    26.07.2026: раньше здесь лежал СВОЙ захардкоженный список из пяти пунктов
    (Панель, Новости, Страницы, Категории, Файлы) — он давно разъехался с
    сайдбаром, и с телефона половина разделов была недоступна в принципе.
    Теперь источник тот же, что у сайдбара, шапки и глобального поиска:
    App\Support\AdminSections.
--}}
@php
    $mobileDashboard = \App\Support\AdminSections::dashboard();
    $mobileGroups    = \App\Support\AdminSections::groups();
    $mobileCounters  = \App\Support\AdminCounters::all();

    $mobileActive = function (array $link): bool {
        $active = $link['is_route']
            ? request()->routeIs($link['pattern'])
            : request()->is($link['pattern']);

        return $active || (isset($link['also']) && request()->is($link['also']));
    };

    $mobileDashboardActive = $mobileDashboard ? $mobileActive($mobileDashboard) : false;
@endphp

<div x-data="{ open: false }" class="lg:hidden" x-cloak>
    {{-- Кнопка открытия --}}
    <button @click="open = true"
            class="amb-toggle fixed top-4 left-4 z-50 w-10 h-10 flex items-center justify-center shadow-lg"
            aria-label="Открыть меню">
        <i class="fas fa-bars"></i>
    </button>

    {{-- Затемнение --}}
    <div x-show="open"
         @click="open = false"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 z-40"
         style="display: none;"></div>

    {{-- Панель --}}
    <div x-show="open"
         @click.away="open = false"
         @keydown.escape.window="open = false"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-900 shadow-2xl z-50 overflow-y-auto"
         style="display: none;">

        <div class="amb-accent" aria-hidden="true"></div>

        {{-- Заголовок --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-2">
            {{-- Логотип ведёт на дашборд — так же, как в сайдбаре, отдельного
                 пункта под ним нет --}}
            <a href="{{ $mobileDashboard['url'] ?? url('/admin') }}"
               class="amb-brand flex items-center gap-2.5 min-w-0 {{ $mobileDashboardActive ? 'is-active' : '' }}"
               aria-current="{{ $mobileDashboardActive ? 'page' : 'false' }}">
                <span class="amb-logo" aria-hidden="true"><i class="fas fa-layer-group text-sm"></i></span>
                <span class="min-w-0 leading-tight">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white truncate">RU CMS</span>
                    <span class="block text-xs text-gray-400 truncate">Панель управления</span>
                </span>
            </a>
            <button @click="open = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 flex-shrink-0"
                    aria-label="Закрыть меню">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="p-3 space-y-3" aria-label="Основная навигация">
            @foreach ($mobileGroups as $title => $links)
                @continue(! count($links))
                <div class="{{ $loop->first ? '' : 'pt-3 border-t border-gray-200 dark:border-gray-800' }}">
                    <p class="amb-group">{{ $title }}</p>
                    <div class="space-y-0.5">
                        @foreach ($links as $link)
                            @php
                                $active = $mobileActive($link);
                                $count  = $link['counter'] ? ($mobileCounters[$link['counter']] ?? 0) : 0;
                            @endphp
                            <a href="{{ $link['url'] }}"
                               class="amb-item {{ $active ? 'is-active' : '' }}"
                               aria-current="{{ $active ? 'page' : 'false' }}">
                                @themeIcon($link['icon'], 'w-5 text-center flex-shrink-0')
                                <span class="truncate flex-1">{{ $link['label'] }}</span>
                                @if($count > 0)
                                    <span class="amb-count">{{ $count > 99 ? '99+' : $count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>

    <style>
        /* Те же переменные оформления, что у сайдбара — меню меняется вместе
           с выбранной темой панели. */
        .amb-toggle{color:var(--admin-on-primary,#fff);background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7))}
        .amb-accent{height:3px;background:linear-gradient(90deg,var(--admin-primary),var(--admin-accent),var(--admin-primary))}
        .amb-brand{text-decoration:none}
        .amb-brand.is-active .amb-logo{box-shadow:0 0 0 2px var(--admin-accent,#a855f7)}
        .amb-logo{display:grid;place-items:center;width:2rem;height:2rem;flex:none;color:var(--admin-on-primary,#fff);
            background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7))}
        .amb-group{padding:0 .6rem;margin-bottom:.25rem;font-size:.68rem;font-weight:700;
            letter-spacing:.06em;text-transform:uppercase;color:var(--surface-dim,#9ca3af)}
        .amb-item{display:flex;align-items:center;gap:.7rem;padding:.5rem .6rem;font-size:.875rem;
            color:var(--surface-ink,#374151);text-decoration:none;border-left:2px solid transparent;
            transition:background .15s ease,color .15s ease}
        .dark .amb-item{color:#d1d5db}
        .amb-item:hover{background:var(--admin-primary-soft,color-mix(in srgb, var(--admin-primary, #6366f1) 10%, transparent));
            border-left-color:var(--admin-primary,#6366f1)}
        .amb-item.is-active{color:var(--admin-on-primary,#fff);font-weight:600;border-left-color:var(--admin-accent,#a855f7);
            background:linear-gradient(90deg,var(--admin-primary,#6366f1),transparent 340%)}
        .amb-count{flex:none;min-width:1.25rem;padding:.05rem .3rem;text-align:center;font-size:.65rem;
            font-weight:700;color:var(--admin-on-primary,#fff);background:var(--admin-primary,#6366f1)}
        .amb-item.is-active .amb-count{color:var(--admin-primary,#4f46e5);background:var(--surface,#fff)}
    </style>
</div>
