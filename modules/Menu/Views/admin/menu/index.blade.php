@extends('layouts.admin')

@section('title', __('admin.sections.menus'))

@section('content')
    {{-- ── Шапка страницы: акцентная полоса + бейдж-иконка + действие ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge">@themeIcon('bars')</span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.sections.menus') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.menu.index_hint') }}
                </p>
            </div>
        </div>

        <a href="{{ route('admin.menus.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0"
           title="{{ __('admin.menu.create_title') }}">
            @themeIcon('plus') {{ __('admin.menu.page_create') }}
        </a>
    </div>

    {{-- ── Подсказка / быстрый старт ── --}}
    <div class="admin-note px-4 py-3 mb-6 text-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2 font-medium">
                @themeIcon('lightbulb')
                <span>{{ __('admin.menu.note_1') }} <b>{{ __('admin.menu.on') }}</b> {{ __('admin.menu.note_2') }}</span>
            </div>
            <ul class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                <li class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">Drag &amp; drop</li>
                <li class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">{{ __('admin.menu.chip_types') }}</li>
                <li class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">{{ __('admin.menu.chip_seo') }}</li>
                <li class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">{{ __('admin.menu.chip_toggle') }}</li>
            </ul>
        </div>
    </div>

    {{-- ── Сводка ──────────────────────────────────────────────────────
         Отвечает на главный вопрос раздела сразу: что из этого реально
         показывается на сайте. Раньше это выяснялось пересчётом плашек
         глазами — меню выводится, только если оно активно И непусто. --}}
    @php
        $totalMenus  = $menus->count();
        $liveMenus   = $menus->filter(fn ($m) => $m->active && $m->items_count > 0)->count();
        $emptyMenus  = $menus->filter(fn ($m) => $m->items_count === 0)->count();
    @endphp

    @if($totalMenus)
        <div class="mnu-summary mb-4">
            <span class="mnu-summary__item">@themeIcon('bars') всего: <b>{{ $totalMenus }}</b></span>
            <span class="mnu-summary__item {{ $liveMenus ? 'is-on' : 'is-off' }}">
                @themeIcon('eye') показывается: <b>{{ $liveMenus }}</b>
            </span>
            @if($emptyMenus)
                <span class="mnu-summary__item is-warn">
                    @themeIcon('alert-triangle') без пунктов: <b>{{ $emptyMenus }}</b>
                </span>
            @endif
            @unless($liveMenus)
                <span class="mnu-summary__note">Ни одно меню не выводится — навигация на сайте пуста.</span>
            @endunless
        </div>
    @endif

    {{-- ── Поиск + фильтр по позиции (клиентский) ── --}}
    <div class="mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="relative w-full md:w-80">
            {{-- Инлайн-SVG лупа: фиксированный размер и выравнивание, не зависит от
                 размера lucide-иконки темы (та рендерилась 24px и лезла на текст). --}}
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                 width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
            </svg>
            <input id="menu-search" type="text" placeholder="{{ __('admin.menu.search_ph') }}"
                   class="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        </div>

        <div class="flex gap-2 text-xs">
            <button data-pos="all"     class="pos-filter is-active bg-indigo-600 text-white px-3 py-1.5 font-medium transition">{{ __('admin.common.all') }}</button>
            <button data-pos="header"  class="pos-filter bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1.5 font-medium transition">Header</button>
            <button data-pos="footer"  class="pos-filter bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1.5 font-medium transition">Footer</button>
            <button data-pos="sidebar" class="pos-filter bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1.5 font-medium transition">Sidebar</button>
            <button data-pos="social"  class="pos-filter bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1.5 font-medium transition">Соцсети</button>
        </div>
    </div>

    {{-- ── Карточки меню ── --}}
    <div id="menu-grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($menus as $menu)
            @php
                $itemsCount = $menu->items_count;
                $isLive     = $menu->active && $itemsCount > 0;

                // Куда попадает меню — для мини-макета на карточке. Тот же
                // приём, что в разделе «Фрагменты»: позицию видно, а не
                // читаешь словом.
                $plan = [
                    'header'  => ['area' => 'site', 'slot' => 'header'],
                    'footer'  => ['area' => 'site', 'slot' => 'footer'],
                    'sidebar' => ['area' => 'site', 'slot' => 'side'],
                    'social'  => ['area' => 'site', 'slot' => 'social'],
                ][$menu->position] ?? null;
            @endphp

            <div class="menu-card admin-card relative overflow-hidden flex flex-col {{ $isLive ? 'mnu-card--live' : '' }}"
                 data-title="{{ Str::lower($menu->title) }}"
                 data-pos="{{ $menu->position }}">

                {{-- Мини-макет страницы с подсвеченным местом --}}
                <div class="mnu-map">
                    <span class="mnu-map__where">
                        @themeIcon($menu->position === 'social' ? 'link' : 'globe')
                        {{ $menu->position }}
                    </span>

                    <div class="mnu-map__page">
                        <div class="mnu-map__slot mnu-map__slot--header {{ ($plan['slot'] ?? '') === 'header' ? 'is-here' : '' }}"></div>
                        <div class="mnu-map__mid">
                            <div class="mnu-map__slot mnu-map__slot--side {{ ($plan['slot'] ?? '') === 'side' ? 'is-here' : '' }}"></div>
                            <div class="mnu-map__body">
                                <span class="mnu-map__line"></span>
                                <span class="mnu-map__line mnu-map__line--short"></span>
                            </div>
                        </div>
                        <div class="mnu-map__slot mnu-map__slot--footer {{ ($plan['slot'] ?? '') === 'footer' ? 'is-here' : '' }}"></div>
                        <div class="mnu-map__slot mnu-map__slot--social {{ ($plan['slot'] ?? '') === 'social' ? 'is-here' : '' }}"></div>
                    </div>
                </div>

                <div class="p-4 flex flex-col flex-1">
                    <div class="flex items-start gap-2">
                        <div class="min-w-0 flex-1">
                            <h2 class="font-bold text-gray-900 dark:text-white truncate">{{ $menu->title }}</h2>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ trans_choice('admin.menu.items_plural', $itemsCount) }} · ID {{ $menu->id }}
                            </div>
                        </div>

                        {{-- Состояние — КНОПКА, а не подпись. Раньше включение
                             было одной из трёх равновеликих кнопок внизу, хотя
                             это самое частое действие раздела. --}}
                        <form method="POST" action="{{ route('admin.menus.toggle', $menu) }}" class="flex-none">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="mnu-state {{ $menu->active ? 'is-on' : 'is-off' }}"
                                    title="{{ $menu->active ? __('admin.menu.toggle_off_title') : __('admin.menu.toggle_on_title') }}">
                                <span class="mnu-state__dot"></span>
                                {{ $menu->active ? __('admin.menu.on') : __('admin.menu.off') }}
                            </button>
                        </form>
                    </div>

                    {{-- Что внутри: первые пункты названиями. Раньше содержимое
                         меню можно было узнать, только открыв его. --}}
                    @if($itemsCount)
                        <div class="mnu-items">
                            @foreach($menu->items as $item)
                                <span class="mnu-chip">{{ $item->title }}</span>
                            @endforeach
                            @if($itemsCount > $menu->items->count())
                                <span class="mnu-chip mnu-chip--more">ещё {{ $itemsCount - $menu->items->count() }}</span>
                            @endif
                        </div>
                    @else
                        <p class="mnu-empty">
                            @themeIcon('alert-triangle') пусто — меню на сайт не попадёт
                        </p>
                    @endif

                    <div class="mt-auto pt-4 flex items-center gap-2">
                        <a href="{{ route('admin.menus.edit', $menu) }}" class="mnu-btn mnu-btn--primary flex-1 justify-center"
                           title="{{ __('admin.menu.edit_title') }}">
                            @themeIcon('edit') <span>{{ __('admin.menu.edit') }}</span>
                        </a>

                        <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}"
                              onsubmit="return confirm(@js(__('admin.menu.delete_confirm')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mnu-icon mnu-icon--danger" title="{{ __('admin.menu.delete_title') }}">
                                @themeIcon('trash-alt')
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="admin-card p-10 text-center sm:col-span-2 xl:col-span-3">
                <span class="admin-icon-badge mx-auto mb-3">@themeIcon('bars')</span>
                <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('admin.menu.empty') }}</p>
                <a href="{{ route('admin.menus.create') }}" class="text-indigo-600 dark:text-indigo-400 underline text-sm">{{ __('admin.menu.create_first') }}</a>
            </div>
        @endforelse
    </div>

    @push('scripts')
    <script>
        // Клиентский фильтр по названию/позиции — быстро и без перезагрузки
        const search = document.getElementById('menu-search');
        const cards  = [...document.querySelectorAll('.menu-card')];
        const posButtons = [...document.querySelectorAll('.pos-filter')];
        let currentPos = 'all';

        function applyFilter() {
            const q = (search.value || '').trim().toLowerCase();
            cards.forEach(card => {
                const inTitle = card.dataset.title.includes(q);
                const inPos   = currentPos === 'all' || card.dataset.pos === currentPos;
                card.style.display = (inTitle && inPos) ? '' : 'none';
            });
        }
        search.addEventListener('input', applyFilter);
        posButtons.forEach(btn => btn.addEventListener('click', e => {
            e.preventDefault();
            currentPos = btn.dataset.pos;
            // подсветка активного фильтра индиго-акцентом
            posButtons.forEach(b => {
                const on = b.dataset.pos === currentPos;
                b.classList.toggle('bg-indigo-600', on);
                b.classList.toggle('text-white', on);
                b.classList.toggle('bg-gray-100', !on);
                b.classList.toggle('dark:bg-gray-800', !on);
                b.classList.toggle('text-gray-700', !on);
                b.classList.toggle('dark:text-gray-300', !on);
            });
            applyFilter();
        }));
    </script>
    @endpush
@endsection

@push('styles')
<style>
    /* ── Список меню ──────────────────────────────────────────────────
       Устроен как раздел «Фрагменты»: сверху карточки — мини-макет
       страницы с подсвеченным местом, где меню появится. Позицию видно,
       а не читаешь словом «sidebar».

       Литеральный CSS: в собранном tailwind.min.css этого проекта нет ни
       произвольных значений, ни прозрачности через дробь. */

    .mnu-summary{ display:flex; flex-wrap:wrap; align-items:center; gap:.75rem;
        padding:.6rem .9rem; font-size:.8rem; color:#4b5563;
        background:#f9fafb; border:1px solid #e5e7eb }
    .dark .mnu-summary{ background:#111827; border-color:#374151; color:#d1d5db }
    .mnu-summary__item{ display:inline-flex; align-items:center; gap:.4rem }
    .mnu-summary__item svg, .mnu-summary__item i{ width:.9rem; height:.9rem; color:#9ca3af }
    .mnu-summary__item.is-on svg, .mnu-summary__item.is-on i{ color:#16a34a }
    .mnu-summary__item.is-warn svg, .mnu-summary__item.is-warn i{ color:#d97706 }
    .mnu-summary__note{ color:#6b7280; font-style:italic }

    .mnu-card--live{ outline:2px solid var(--admin-primary); outline-offset:-2px }

    /* Мини-макет страницы */
    .mnu-map{ position:relative; padding:.6rem .75rem .7rem; background:#f8fafc;
        border-bottom:1px solid #e5e7eb }
    .dark .mnu-map{ background:#0f172a; border-bottom-color:#374151 }

    .mnu-map__where{ display:inline-flex; align-items:center; gap:.3rem; margin-bottom:.4rem;
        font-size:.6rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#64748b }
    .mnu-map__where svg, .mnu-map__where i{ width:.7rem; height:.7rem; color:#94a3b8 }
    .dark .mnu-map__where{ color:#94a3b8 }

    .mnu-map__page{ display:flex; flex-direction:column; gap:3px; height:78px; padding:4px;
        background:#fff; border:1px solid #e2e8f0 }
    .dark .mnu-map__page{ background:#1e293b; border-color:#334155 }

    .mnu-map__slot{ flex:none; background:#e8edf3 }
    .dark .mnu-map__slot{ background:#334155 }
    .mnu-map__slot--header{ height:13px }
    .mnu-map__slot--footer{ height:11px }
    .mnu-map__slot--social{ height:7px }

    .mnu-map__mid{ flex:1; display:flex; gap:3px; min-height:0 }
    .mnu-map__slot--side{ width:16px; height:auto }
    .mnu-map__body{ flex:1; display:flex; flex-direction:column; justify-content:center;
        gap:4px; padding:0 5px }
    .mnu-map__line{ display:block; height:3px; width:100%; background:#e8edf3 }
    .mnu-map__line--short{ width:60% }
    .dark .mnu-map__line{ background:#334155 }

    /* Подсвеченное место — то, где появится это меню. Кольцо нужно самым
       тонким полосам: одной заливки для них мало, чтобы заметить. */
    .mnu-map__slot.is-here{ position:relative; background:var(--admin-primary) }
    .mnu-map__slot.is-here::after{ content:''; position:absolute; inset:-3px;
        border:1px solid var(--admin-primary); opacity:.45 }

    /* Состояние — кнопка */
    .mnu-state{ display:inline-flex; align-items:center; gap:.35rem; flex:none;
        padding:.2rem .5rem; font-size:.7rem; font-weight:700; cursor:pointer;
        border:1px solid transparent; transition:filter .15s }
    .mnu-state:hover{ filter:brightness(.95) }
    .mnu-state__dot{ width:.45rem; height:.45rem; border-radius:9999px; background:currentColor }
    .mnu-state.is-on{ color:color-mix(in srgb, #16a34a 60%, #111827);
        background:color-mix(in srgb, #16a34a 16%, #fff);
        border-color:color-mix(in srgb, #16a34a 30%, #fff) }
    .mnu-state.is-off{ color:#6b7280; background:#f3f4f6; border-color:#e5e7eb }
    .dark .mnu-state.is-off{ color:#9ca3af; background:#374151; border-color:#4b5563 }

    /* Пункты меню названиями */
    .mnu-items{ display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.7rem }
    .mnu-chip{ font-size:.68rem; padding:.12rem .4rem; color:#4b5563;
        background:#f3f4f6; border:1px solid #e5e7eb; max-width:100%;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .mnu-chip--more{ color:#6b7280; background:transparent; border-style:dashed }
    .dark .mnu-chip{ color:#d1d5db; background:#374151; border-color:#4b5563 }

    .mnu-empty{ margin-top:.7rem; display:inline-flex; align-items:center; gap:.35rem;
        font-size:.72rem; padding:.3rem .5rem;
        color:color-mix(in srgb, #d97706 60%, #111827);
        background:color-mix(in srgb, #d97706 12%, #fff);
        border:1px solid color-mix(in srgb, #d97706 26%, #fff) }
    .mnu-empty svg, .mnu-empty i{ width:.85rem; height:.85rem }

    /* Кнопки */
    .mnu-btn{ display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .8rem;
        font-size:.8rem; font-weight:600; white-space:nowrap; cursor:pointer;
        color:#374151; background:#fff; border:1px solid #d1d5db; text-decoration:none;
        transition:background-color .15s, border-color .15s, color .15s }
    .mnu-btn:hover{ background:#f3f4f6; border-color:var(--admin-primary); color:var(--admin-primary) }
    .mnu-btn--primary{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary) }
    .mnu-btn--primary:hover{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary); filter:brightness(1.08) }
    .dark .mnu-btn{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    .mnu-icon{ display:inline-flex; align-items:center; justify-content:center; flex:none;
        width:2.25rem; height:2.25rem; cursor:pointer; color:#6b7280;
        background:transparent; border:1px solid #d1d5db; transition:border-color .15s, color .15s }
    .mnu-icon--danger:hover{ border-color:#dc2626; color:#dc2626 }
    .dark .mnu-icon{ color:#9ca3af; border-color:#374151 }
</style>
@endpush
