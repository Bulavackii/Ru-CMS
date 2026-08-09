{{--
    Меню позиции sidebar как выдвижная боковая панель (off-canvas).
    На фронте нет постоянной боковой колонки, поэтому показываем меню в
    выезжающей панели с кнопкой-язычком у левого края — не ломает вёрстку
    страниц и работает на всех экранах. Рендерится только если sidebar-меню
    заполнено. Источник — БД (Menu::cachedByPosition('sidebar')).
--}}
@php
    $sidebarItems = collect(\Modules\Menu\Models\Menu::cachedByPosition('sidebar'))
        ->flatMap(fn($menu) => $menu->items)
        ->values();
@endphp

@if ($sidebarItems->isNotEmpty())
<div x-data="{ open: false }" @keydown.escape.window="open = false" class="frontend-sidebar">
    {{-- Кнопка-язычок --}}
    <button type="button" @click="open = true" class="fs-toggle" aria-label="Открыть боковое меню" title="Меню">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>
        </svg>
    </button>

    {{-- Затемнение --}}
    <div x-cloak x-show="open" x-transition.opacity @click="open = false" class="fs-overlay"></div>

    {{-- Панель --}}
    <aside x-cloak x-show="open"
           x-transition:enter="fs-enter" x-transition:enter-start="fs-enter-start" x-transition:enter-end="fs-enter-end"
           x-transition:leave="fs-enter" x-transition:leave-start="fs-enter-end" x-transition:leave-end="fs-enter-start"
           class="fs-panel" aria-label="Боковое меню">
        <div class="fs-head">
            <span class="fs-title">{{ __('frontend.header.menu') }}</span>
            <button type="button" @click="open = false" class="fs-close" aria-label="Закрыть">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="fs-nav" aria-label="Боковая навигация">
            <ul class="fs-list">
                @foreach ($sidebarItems as $item)
                    <li>
                        <a href="{{ $item->frontendUrl() }}" class="fs-link fx-underline"
                           @if($item->target) target="{{ $item->target }}" @endif
                           @if($item->rel) rel="{{ $item->rel }}" @endif>
                            <span class="fs-ico">@themeIcon($item->displayIcon())</span>
                            <span>{{ $item->t('title') }}</span>
                        </a>
                        @if ($item->activeChildren && $item->activeChildren->count())
                            <ul class="fs-list fs-sub">
                                @foreach ($item->activeChildren as $child)
                                    <li>
                                        <a href="{{ $child->frontendUrl() }}" class="fs-link fs-link--sub fx-underline"
                                           @if($child->target) target="{{ $child->target }}" @endif
                                           @if($child->rel) rel="{{ $child->rel }}" @endif>
                                            <span class="fs-ico">@themeIcon($child->displayIcon())</span>
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
    </aside>
</div>

<style>
    [x-cloak]{ display:none !important; }
    .fs-toggle{
        position:fixed; left:0; top:50%; transform:translateY(-50%); z-index:60;
        display:inline-flex; align-items:center; justify-content:center;
        width:38px; height:46px; padding:0; color:#fff;
        background:var(--fx-grad,#6366f1); border:0;
        border-radius:0 12px 12px 0; box-shadow:0 10px 22px -8px color-mix(in srgb, var(--color-primary, #6366f1) 60%, transparent); cursor:pointer;
        transition:background .15s, transform .15s;
    }
    .fs-toggle:hover{ transform:translateY(-50%) translateX(2px); }
    .fs-overlay{ position:fixed; inset:0; z-index:70; background:rgba(15,23,42,.45); -webkit-backdrop-filter:blur(2px); backdrop-filter:blur(2px); }
    .fs-panel{
        position:fixed; left:0; top:0; bottom:0; z-index:80;
        width:290px; max-width:85vw; overflow-y:auto;
        background:rgba(255,255,255,.85); -webkit-backdrop-filter:blur(14px); backdrop-filter:blur(14px);
        border-right:1px solid rgba(17,24,39,.08);
        box-shadow:0 0 50px -10px rgba(17,24,39,.4);
    }
    :root.dark .fs-panel{ background:rgba(15,23,42,.82); border-color:rgba(255,255,255,.08); }

    /* Тёмная ТЕМА — отдельный класс от системного тёмного режима выше.
       Без этих строк выдвижная панель оставалась белой, а подписи на ней
       светлыми: меню открывалось пустым белым полотном. */
    body.fx-theme-dark .fs-panel{
        background:var(--surface);
        border-color:var(--surface-bd);
        color:var(--color-text);
    }
    body.fx-theme-dark .fs-panel a,
    body.fx-theme-dark .fs-panel span,
    body.fx-theme-dark .fs-panel button{ color:var(--color-text); }
    body.fx-theme-dark .fs-panel a:hover{ color:var(--color-primary); }
    .fs-enter{ transition:transform .22s ease, opacity .22s ease; }
    .fs-enter-start{ transform:translateX(-100%); opacity:.4; }
    .fs-enter-end{ transform:translateX(0); opacity:1; }
    .fs-head{ display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid rgba(17,24,39,.07); }
    :root.dark .fs-head{ border-color:rgba(255,255,255,.08); }
    .fs-title{ font-weight:600; font-size:.95rem; color:var(--color-text,#111827); }
    .fs-close{ display:inline-flex; padding:6px; border:0; background:transparent; color:var(--surface-mute,#6b7280); cursor:pointer; border-radius:6px; }
    .fs-close:hover{ background:rgba(17,24,39,.06); color:var(--fx-a,#6366f1); }
    .fs-nav{ padding:8px; }
    .fs-list{ list-style:none; margin:0; padding:0; }
    .fs-link{ display:flex; align-items:center; gap:.6rem; padding:.6rem .7rem; border-radius:8px;
        text-decoration:none; color:var(--color-text,#374151); font-size:.9rem; transition:background .12s, color .12s; }
    .fs-link:hover{ background:color-mix(in srgb, var(--color-primary, #6366f1) 8%, transparent); color:var(--fx-a,#6366f1); }
    .fs-link--sub{ font-size:.85rem; color:var(--surface-mute,#6b7280); }
    .fs-sub{ margin:2px 0 4px .9rem; padding-left:.5rem; border-left:1.5px solid color-mix(in srgb, var(--color-primary, #6366f1) 18%, transparent); }
    .fs-ico{ display:inline-flex; color:var(--fx-a,#6366f1); opacity:.7; }
    .fs-ico svg, .fs-ico i{ width:1.05rem; height:1.05rem; font-size:1.05rem; line-height:1; }
</style>
@endif
