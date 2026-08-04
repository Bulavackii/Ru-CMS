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

    // Оформление вынесено в литеральный CSS подвала (класс f-menu-link):
    // прежний набор Tailwind-классов давал ссылке собственный ховер с
    // подложкой, из-за чего столбцы меню вели себя иначе, чем контакты
    // в соседней колонке. Теперь у них общий вид: значок в плитке,
    // подчёркивание значения.
    $footerColTitle = 'footer-col-title';
    $footerLinkCls  = 'f-menu-link';
@endphp

{{-- Столбцы меню лежат в собственной группе, а не прямыми детьми сетки
     подвала. Раньше каждый столбец был отдельной ячейкой наравне с
     брендом и контактами и получал столько же места — при двух меню
     это давало четыре колонки по 316px, и короткие списки ссылок
     разъезжались по всей ширине. Внутри группы шаг меньше, поэтому
     столбцы держатся рядом, а число их по-прежнему любое: добавили
     меню в панели — появилась ещё одна колонка. --}}
<div class="footer-menus">
@forelse ($footerMenus as $menu)
    <nav class="footer-col" aria-label="{{ $menu->t('title') }}">
        <h2 class="{{ $footerColTitle }}">{{ $menu->t('title') }}</h2>
        <ul class="f-menu-list">
            @foreach ($menu->items as $item)
                <li>
                    <a href="{{ $item->frontendUrl() }}" class="{{ $footerLinkCls }}"
                       @if($item->target) target="{{ $item->target }}" @endif
                       @if($item->rel) rel="{{ $item->rel }}" @endif>
                        <span class="f-menu-ico">@themeIcon($item->displayIcon())</span>
                        <span class="f-menu-text">{{ $item->t('title') }}</span>
                    </a>
                    @if ($item->activeChildren && $item->activeChildren->count())
                        <ul class="f-menu-list f-menu-list--child">
                            @foreach ($item->activeChildren as $child)
                                <li>
                                    <a href="{{ $child->frontendUrl() }}" class="f-menu-link f-menu-link--child">
                                        <span class="f-menu-ico">@themeIcon($child->displayIcon())</span>
                                        <span class="f-menu-text">{{ $child->t('title') }}</span>
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
    <nav class="footer-col" aria-label="Навигация">
        <h2 class="{{ $footerColTitle }}">Навигация</h2>
        <ul class="f-menu-list">
            @foreach ($footerFallback as $link)
                <li>
                    <a href="{{ url($link['url']) }}" class="{{ $footerLinkCls }}">
                        <span class="f-menu-ico">@themeIcon($link['icon'])</span>
                        <span class="f-menu-text">{{ $link['title'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endforelse
</div>
