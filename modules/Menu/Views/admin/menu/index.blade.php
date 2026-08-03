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
    <div class="admin-hint px-4 py-3 mb-6 text-sm">
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
        </div>
    </div>

    {{-- ── Карточки меню ── --}}
    <div id="menu-grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($menus as $menu)
            @php $itemsCount = $menu->items()->count(); @endphp

            <div class="menu-card admin-card relative overflow-hidden flex flex-col"
                 data-title="{{ Str::lower($menu->title) }}"
                 data-pos="{{ $menu->position }}">
                <div class="admin-accent-bar"></div>

                <div class="p-5 flex flex-col flex-1">
                    {{-- Заголовок: бейдж-иконка + название, статус/позиция в потоке под ним --}}
                    <div class="flex items-start gap-3 mb-4">
                        <span class="admin-icon-badge shrink-0">@themeIcon('bars')</span>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold text-gray-900 dark:text-white break-words leading-tight">
                                {{ $menu->title }}
                            </h2>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="text-xs px-2 py-0.5 font-semibold
                                    {{ $menu->active ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }}">
                                    {{ $menu->active ? __('admin.menu.on') : __('admin.menu.off') }}
                                </span>
                                <span class="text-xs px-2 py-0.5 font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 capitalize">
                                    {{ $menu->position }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Счётчики: чипы «N пунктов» и «ID» --}}
                    @php
                        // Склонение отдано trans_choice: правила множественного
                        // числа у языков разные, и захардкоженная русская логика
                        // (1 пункт / 2–4 пункта / 5+ пунктов) на других языках
                        // всё равно давала бы неверную форму.
                        $itemsWord = trans_choice('admin.menu.items_plural', $itemsCount);
                    @endphp
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 font-semibold
                                     {{ $itemsCount > 0 ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}"
                              title="{{ __('admin.menu.items_count_title') }}">
                            @themeIcon('list') {{ $itemsWord }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                              title="{{ __('admin.menu.id_title') }}">
                            @themeIcon('hashtag') ID {{ $menu->id }}
                        </span>
                    </div>

                    {{-- Действия: три кнопки одинакового размера (grid = равная ширина,
                         stretch = равная высота), прижаты к низу карточки --}}
                    <div class="mt-auto pt-4 grid grid-cols-3 gap-2 text-xs border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ route('admin.menus.edit', $menu) }}"
                           class="inline-flex items-center justify-center gap-1.5 w-full px-2 py-2 font-semibold whitespace-nowrap bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition"
                           title="{{ __('admin.menu.edit_title') }}">
                            @themeIcon('edit') <span>{{ __('admin.menu.edit') }}</span>
                        </a>

                        <form method="POST" action="{{ route('admin.menus.toggle', $menu) }}" class="flex"
                              title="{{ $menu->active ? __('admin.menu.toggle_off_title') : __('admin.menu.toggle_on_title') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-1.5 w-full px-2 py-2 font-semibold whitespace-nowrap shadow-sm transition
                                           {{ $menu->active ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : 'bg-gray-200 dark:bg-gray-700 hover:bg-green-600 text-gray-800 dark:text-gray-200 hover:text-white' }}">
                                @themeIcon('power-off') <span>{{ $menu->active ? __('admin.menu.turn_off') : __('admin.menu.turn_on') }}</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" class="flex"
                              onsubmit="return confirm(@js(__('admin.menu.delete_confirm')))"
                              title="{{ __('admin.menu.delete_title') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-1.5 w-full px-2 py-2 font-semibold whitespace-nowrap bg-red-600 hover:bg-red-700 text-white shadow-sm transition">
                                @themeIcon('trash-alt') <span>{{ __('admin.delete') }}</span>
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
