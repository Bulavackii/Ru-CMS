{{--
    Один узел меню-хедера. Рекурсивно включает сам себя для детей — до 3 уровней.
    Ожидает: $item (MenuItem с загруженным activeChildren), $level (1 — корень).

    Мега-меню (по образцу): корень — горизонтальная полоса; 2-й уровень —
    вертикальная панель-список; 3-й уровень — широкая панель в несколько колонок,
    вылетающая вбок от пункта 2-го уровня. Показ/скрытие — чистым CSS (см.
    Menu::frontend.header), поэтому на маленьких экранах всё дерево раскрыто сразу.
--}}
@php
    $hasChildren = $item->activeChildren && $item->activeChildren->count() > 0;

    $link = match ($item->type) {
        'url'      => $item->url ?: '#',
        'page'     => optional($item->linkedPage)?->slug
                        ? route('frontend.pages.show', optional($item->linkedPage)->slug)
                        : '#',
        'category' => url('/?category=' . $item->linked_id),
        default    => '#',
    };

    // Иконка по умолчанию на всё: нет своей — берём по типу (валидные имена Lucide).
    $iconName = $item->icon ?: match ($item->type) {
        'page'     => 'file-text',
        'category' => 'tag',
        default    => 'link',
    };

    $attrs = [];
    if ($item->target) { $attrs['target'] = $item->target; }
    if ($item->rel)    { $attrs['rel']    = $item->rel; }
    $attrsStr = collect($attrs)->map(fn($v, $k) => "$k=\"$v\"")->join(' ');

    $path   = parse_url($link, PHP_URL_PATH);
    $active = $path !== null && request()->is(ltrim($path, '/'));

    $liClass   = $level === 1 ? 'menu-item--root relative'
               : ($level === 2 ? 'menu-item--l2 relative' : 'menu-item--l3');
    $linkClass = $level === 1 ? 'menu-link--root'
               : ($level === 2 ? 'menu-link--l2' : 'menu-link--l3');
@endphp

<li class="menu-item {{ $liClass }} list-none {{ $item->css_class ?? '' }}">
    <a href="{{ $link }}"
       class="menu-link {{ $linkClass }} {{ $active ? 'active-link' : '' }}"
       style="color:var(--color-text,#111827)"
       {!! $attrsStr !!}
       {{ $active ? 'aria-current=page' : '' }}>
        <span class="menu-ico">@themeIcon($iconName)</span>
        <span class="menu-text">{{ $item->title }}</span>
        @if ($hasChildren && $level === 1)
            <svg class="menu-caret" width="11" height="11" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        @elseif ($hasChildren && $level === 2)
            <svg class="menu-caret menu-caret--right" width="11" height="11" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m9 6 6 6-6 6"/>
            </svg>
        @endif
    </a>

    @if ($hasChildren)
        <ul class="submenu list-none">
            @foreach ($item->activeChildren as $child)
                @include('Menu::frontend.menu-node', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
