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
        // Разделы берутся из App\Support\AdminSections — того же списка, по
        // которому ищет глобальный поиск в шапке. Раньше он жил только здесь,
        // в разметке, и поиск про разделы не знал вовсе.
        // Ссылка на дашборд — в шапке сайдбара (логотип), отдельного пункта нет.
        $groups = \App\Support\AdminSections::groups();

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
                                $isActive = $link['is_route']
                                    ? request()->routeIs($link['pattern'])
                                    : request()->is($link['pattern']);
                                // У SEO маршруты живут в своём модуле: раздел считается
                                // активным и по имени маршрута, и по пути
                                if (! $isActive && isset($link['also'])) {
                                    $isActive = request()->is($link['also']);
                                }
                            @endphp
                            <a href="{{ $link['url'] }}" class="{{ $base }} {{ $isActive ? $active : $idle }}"
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

    {{-- Версия отсюда убрана 26.07.2026: она показывалась и здесь, и в подвале
         панели. Значение одно (config('app.version')), место показа тоже
         должно быть одно — оставили подвал, там рядом весь стек. --}}
</aside>
