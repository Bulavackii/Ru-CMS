{{--
    Колонка «Навигация» в подвале из меню позиции footer (аналогично шапке).
    Источник — БД (Menu::cachedByPosition('footer')). Если footer-меню нет или
    пусто — показываем прежний захардкоженный набор ссылок (fallback), чтобы
    подвал никогда не оставался без навигации.
--}}
@php
    $footerItems = collect(\Modules\Menu\Models\Menu::cachedByPosition('footer'))
        ->flatMap(fn($menu) => $menu->items)
        ->values();

    // Fallback — прежние ссылки подвала (когда footer-меню не заполнено).
    $footerFallback = [
        ['url' => '/terms',       'icon' => 'home',   'title' => 'Соглашение'],
        ['url' => '/partnership', 'icon' => 'search', 'title' => 'Сотрудничество'],
        ['url' => '/developers',  'icon' => 'code',   'title' => 'Разработчикам'],
        ['url' => '/concept',     'icon' => 'star',   'title' => 'О проекте'],
        ['url' => '/sitemap',     'icon' => 'map',    'title' => 'Карта сайта'],
        ['url' => '/donate',      'icon' => 'heart',  'title' => 'Поддержать проект'],
    ];

    $footerLinkCls = 'inline-flex items-center gap-2 px-2 py-1 rounded transition '
        . 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
        . 'focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400';
@endphp

<ul class="grid grid-cols-2 gap-y-2 gap-x-6 list-none m-0 p-0">
    @if ($footerItems->isNotEmpty())
        @foreach ($footerItems as $item)
            <li>
                <a href="{{ $item->frontendUrl() }}" class="{{ $footerLinkCls }}"
                   @if($item->target) target="{{ $item->target }}" @endif
                   @if($item->rel) rel="{{ $item->rel }}" @endif>
                    @themeIcon($item->displayIcon())
                    <span class="text-[13px]">{{ $item->title }}</span>
                </a>
                @if ($item->activeChildren && $item->activeChildren->count())
                    <ul class="mt-1 ml-6 space-y-1 list-none">
                        @foreach ($item->activeChildren as $child)
                            <li>
                                <a href="{{ $child->frontendUrl() }}"
                                   class="inline-flex items-center gap-2 text-[12px] text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                    @themeIcon($child->displayIcon())
                                    <span>{{ $child->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    @else
        @foreach ($footerFallback as $link)
            <li>
                <a href="{{ url($link['url']) }}" class="{{ $footerLinkCls }}">
                    @themeIcon($link['icon'])
                    <span class="text-[13px]">{{ $link['title'] }}</span>
                </a>
            </li>
        @endforeach
    @endif
</ul>
