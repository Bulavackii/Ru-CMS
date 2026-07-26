{{--
    Колонки подвала из меню позиции footer. ОДНО footer-меню = ОДИН столбец
    (заголовок столбца = название меню, пункты — вертикальным списком). Несколько
    footer-меню = несколько столбцов: так число столбцов в подвале регулируется
    количеством меню. Источник — БД (Menu::cachedByPosition('footer')). Если
    footer-меню нет — показываем один запасной столбец «Навигация» (fallback).

    Каждый столбец — отдельный <nav class="footer-col"> прямым потомком сетки
    подвала (layouts/partials/footer.blade.php), поэтому участвует в её
    авто-раскладке по числу столбцов.
--}}
@php
    $footerMenus = collect(\Modules\Menu\Models\Menu::cachedByPosition('footer'))
        ->filter(fn($menu) => $menu->items->isNotEmpty())
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

    $footerColTitle = 'footer-col-title text-base font-semibold mb-4 text-center text-gray-900 dark:text-gray-100';
    $footerLinkCls  = 'fx-underline inline-flex items-center gap-2 px-2 py-1 rounded transition '
        . 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
        . 'focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400';
@endphp

@forelse ($footerMenus as $menu)
    <nav class="footer-col text-center" aria-label="{{ $menu->title }}">
        <h2 class="{{ $footerColTitle }}">{{ $menu->title }}</h2>
        <ul class="space-y-2 list-none m-0 p-0 inline-block text-left">
            @foreach ($menu->items as $item)
                <li>
                    <a href="{{ $item->frontendUrl() }}" class="{{ $footerLinkCls }}"
                       @if($item->target) target="{{ $item->target }}" @endif
                       @if($item->rel) rel="{{ $item->rel }}" @endif>
                        @themeIcon($item->displayIcon())
                        <span class="text-[13px]">{{ $item->t('title') }}</span>
                    </a>
                    @if ($item->activeChildren && $item->activeChildren->count())
                        <ul class="mt-1 ml-6 space-y-1 list-none">
                            @foreach ($item->activeChildren as $child)
                                <li>
                                    <a href="{{ $child->frontendUrl() }}"
                                       class="fx-underline inline-flex items-center gap-2 text-[12px] text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                        @themeIcon($child->displayIcon())
                                        <span>{{ $child->title }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@empty
    <nav class="footer-col text-center" aria-label="Навигация">
        <h2 class="{{ $footerColTitle }}">Навигация</h2>
        <ul class="space-y-2 list-none m-0 p-0 inline-block text-left">
            @foreach ($footerFallback as $link)
                <li>
                    <a href="{{ url($link['url']) }}" class="{{ $footerLinkCls }}">
                        @themeIcon($link['icon'])
                        <span class="text-[13px]">{{ $link['title'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endforelse
