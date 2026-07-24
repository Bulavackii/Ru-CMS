@php
    // Фолбэк, если по какой-то причине не сработал View::composer
    // (например, кеш шаблонов/служб): берём меню прямо отсюда — с деревом до 3 уровней.
    if (!isset($menus)) {
        $menus = \Modules\Menu\Models\Menu::query()
            ->where('active', true)
            ->where('position', 'header')
            ->with([
                'items' => fn($q) => $q->where('active', true)->whereNull('parent_id')->orderBy('order'),
                'items.linkedPage',
                'items.activeChildren' => fn($q) => $q->where('active', true)->orderBy('order'),
                'items.activeChildren.linkedPage',
                'items.activeChildren.activeChildren' => fn($q) => $q->where('active', true)->orderBy('order'),
                'items.activeChildren.activeChildren.linkedPage',
            ])->get();
    }

    // Корневые пункты всех активных header-меню. Источник правды — только БД:
    // статичного fallback нет (решение владельца) — нет пунктов, нет навигации,
    // в шапке остаётся один поиск.
    $rootItems = collect($menus)->flatMap(fn($menu) => $menu->items)->values();
@endphp

@if ($rootItems->isNotEmpty())
<nav class="header-nav" aria-label="Основная навигация">
    @foreach ($rootItems as $item)
        @include('Menu::frontend.menu-node', ['item' => $item, 'level' => 1])
    @endforeach
</nav>

<style>
    /* ===== Мега-меню шапки: акцент + базовые стили ===== */
    .header-nav{ --nav-accent: var(--color-primary,#2563eb); --nav-soft: rgba(37,99,235,.08);
        display:flex; align-items:center; gap:.15rem; }
    .header-nav ul{ margin:0; padding:0; list-style:none; }
    .header-nav .menu-link{ display:flex; align-items:center; gap:.55rem; text-decoration:none;
        color:var(--color-text,#374151); line-height:1.25; }
    .header-nav .menu-ico{ display:inline-flex; flex:0 0 auto; color:var(--nav-accent); opacity:.7; }
    .header-nav .menu-ico svg,
    .header-nav .menu-ico i{ width:1rem; height:1rem; font-size:1rem; line-height:1; }
    .header-nav .menu-text{ white-space:nowrap; }
    .header-nav .menu-caret{ flex:0 0 auto; opacity:.5; }

    /* ── Полоса верхнего уровня ── */
    .header-nav .menu-link--root{ padding:.55rem .75rem; border-radius:6px; font-weight:600;
        text-transform:uppercase; letter-spacing:.02em; font-size:.78rem; transition:color .15s; }
    .header-nav .menu-link--root:hover,
    .header-nav .menu-item--root:hover > .menu-link--root{ color:var(--nav-accent); }
    .header-nav .menu-link--root.active-link{ color:var(--nav-accent); }

    /* ══════════ Десктоп (широкий экран, есть hover) ══════════ */
    @media (min-width:1024px){
        .header-nav{ flex-direction:row; flex-wrap:wrap; }

        /* Панель 2-го уровня (выпадает под корневым пунктом) */
        .header-nav > .menu-item--root > .submenu{
            display:none; position:absolute; left:0; top:100%;
            min-width:16rem; padding:.4rem;
            background:rgba(255,255,255,.72);
            -webkit-backdrop-filter:blur(14px) saturate(160%); backdrop-filter:blur(14px) saturate(160%);
            border:1px solid rgba(17,24,39,.08); border-radius:10px;
            box-shadow:0 18px 44px -16px rgba(17,24,39,.26);
        }
        .dark .header-nav > .menu-item--root > .submenu{ background:rgba(15,23,42,.72); border-color:rgba(255,255,255,.08); }
        .header-nav > .menu-item--root:hover > .submenu{ display:block; }

        /* Пункты 2-го уровня */
        .header-nav .menu-link--l2{ padding:.6rem .8rem; border-radius:7px; font-weight:500;
            justify-content:space-between; transition:background .12s, color .12s; }
        .header-nav .menu-link--l2 .menu-text{ flex:1 1 auto; }
        .header-nav .menu-item--l2:hover > .menu-link--l2,
        .header-nav .menu-item--l2:focus-within > .menu-link--l2{ background:var(--nav-soft); color:var(--nav-accent); }
        .header-nav .menu-link--l2:hover .menu-ico{ opacity:.95; }

        /* Панель 3-го уровня — широкая, в несколько колонок, вылетает вбок */
        .header-nav .menu-item--l2 > .submenu{
            display:none; position:absolute; left:100%; top:-.4rem; margin-left:.5rem;
            min-width:32rem; max-width:46rem; padding:1rem 1.25rem;
            background:rgba(255,255,255,.72);
            -webkit-backdrop-filter:blur(14px) saturate(160%); backdrop-filter:blur(14px) saturate(160%);
            border:1px solid rgba(17,24,39,.08); border-radius:10px;
            box-shadow:0 18px 44px -16px rgba(17,24,39,.26);
            columns:13rem auto; column-gap:1.75rem;
        }
        .dark .header-nav .menu-item--l2 > .submenu{ background:rgba(15,23,42,.72); border-color:rgba(255,255,255,.08); }
        .header-nav .menu-item--l2:hover > .submenu,
        .header-nav .menu-item--l2:focus-within > .submenu{ display:block; }

        /* Пункты 3-го уровня — плоские ссылки-колонки */
        .header-nav .menu-link--l3{ padding:.4rem .1rem; break-inside:avoid; transition:color .12s; }
        .header-nav .menu-link--l3:hover{ color:var(--nav-accent); }
        .header-nav .menu-link--l3:hover .menu-ico{ opacity:.95; }
    }

    /* ══════════ Маленькие/средние экраны (тач, hover недоступен) ══════════ */
    @media (max-width:1023px){
        .header-nav{ flex-direction:column; align-items:stretch; width:100%; gap:.05rem; }
        .header-nav .menu-item{ width:100%; }
        .header-nav .menu-link{ width:100%; }
        .header-nav .menu-caret{ display:none; }   /* всё раскрыто — сворачивать нечего */
        .header-nav .submenu{ display:block; }       /* дерево показано сразу */
        .header-nav .menu-link--root{ padding:.6rem .5rem; }
        .header-nav .menu-link--l2{ padding:.5rem .5rem .5rem 1.6rem; border-radius:7px; }
        .header-nav .menu-item--l2:hover > .menu-link--l2{ background:var(--nav-soft); color:var(--nav-accent); }
        .header-nav .menu-link--l3{ padding:.45rem .5rem .45rem 2.6rem; }
        .header-nav .menu-link--l3:hover,
        .header-nav .menu-link--l2:hover{ color:var(--nav-accent); }
    }
</style>
@endif
