@extends('layouts.admin')

@section('title', 'Меню')

@push('styles')
<style>
    .hint {
        border-left: 3px solid #60a5fa;
        background: #f0f9ff;
        color:#0f172a;
    }
</style>
@endpush

@section('content')
    {{-- Заголовок + действие --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📋 Меню</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Создавайте навигацию для шапки, подвала и боковых панелей. Поддерживается вложенность и произвольные ссылки.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.menus.create') }}"
               class="inline-flex items-center gap-2 bg-black hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm shadow transition"
               title="Создать новое меню">
                @themeIcon('plus') Создать меню
            </a>
        </div>
    </div>

    {{-- Панель подсказок / быстрого старта --}}
    <div class="hint rounded-xl px-4 py-3 mb-6 text-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2 font-medium">
                @themeIcon('lightbulb')
                <span>Совет: меню отображается на сайте, только если оно <b>Активировано</b> и у него есть хотя бы один пункт.</span>
            </div>
            <ul class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                <li class="bg-white/70 dark:bg-gray-900/50 rounded px-2 py-1">Вложенность — drag & drop</li>
                <li class="bg-white/70 dark:bg-gray-900/50 rounded px-2 py-1">URL / Страницы / Категории</li>
                <li class="bg-white/70 dark:bg-gray-900/50 rounded px-2 py-1">SEO поля для пунктов</li>
                <li class="bg-white/70 dark:bg-gray-900/50 rounded px-2 py-1">Быстрое включение/выключение</li>
            </ul>
        </div>
    </div>

    {{-- Поиск/фильтр по названию/позиции (клиентский) --}}
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-2 w-full md:w-80">
            <input id="menu-search" type="text" placeholder="Поиск по названию…"
                   class="w-full px-3 py-2 border rounded-md text-sm dark:bg-gray-800 dark:text-white dark:border-gray-700">
        </div>

        <div class="flex gap-2 text-xs">
            <button data-pos="all" class="pos-filter bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded">Все</button>
            <button data-pos="header" class="pos-filter bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded">Header</button>
            <button data-pos="footer" class="pos-filter bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded">Footer</button>
            <button data-pos="sidebar" class="pos-filter bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded">Sidebar</button>
        </div>
    </div>

    {{-- Карточки меню --}}
    <div id="menu-grid" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($menus as $menu)
            @php
                $itemsCount = $menu->items()->count();
            @endphp

            <div class="menu-card relative group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200"
                 data-title="{{ Str::lower($menu->title) }}"
                 data-pos="{{ $menu->position }}">

                {{-- Статус/позиция --}}
                <div class="absolute top-3 right-3 flex gap-2">
                    <span class="text-[11px] px-2 py-1 rounded-full font-semibold
                        {{ $menu->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                        {{ $menu->active ? 'Включено' : 'Отключено' }}
                    </span>
                    <span class="text-[11px] px-2 py-1 rounded-full bg-blue-50 text-blue-800 capitalize">
                        {{ $menu->position }}
                    </span>
                </div>

                {{-- Название --}}
                <div class="flex items-center gap-2 mb-3">
                    @themeIcon('bars','text-blue-500')
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white break-words">
                        {{ $menu->title }}
                    </h2>
                </div>

                {{-- Счётчики / доп. сведения --}}
                <div class="mb-5 text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-4">
                    <span title="Количество пунктов">@themeIcon('list') {{ $itemsCount }} пункт(ов)</span>
                    <span title="ID меню">@themeIcon('hashtag') ID: {{ $menu->id }}</span>
                </div>

                {{-- Действия --}}
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="{{ route('admin.menus.edit', $menu) }}"
                       class="inline-flex items-center gap-1 bg-gray-800 hover:bg-gray-900 text-white px-3 py-1.5 rounded-md shadow transition"
                       title="Открыть редактор и управлять пунктами">
                        @themeIcon('edit') Редактировать
                    </a>

                    <form method="POST" action="{{ route('admin.menus.toggle', $menu) }}"
                          title="{{ $menu->active ? 'Отключить отображение на сайте' : 'Включить отображение на сайте' }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md font-medium shadow transition
                                       {{ $menu->active ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : 'bg-gray-200 hover:bg-green-600 text-gray-800 hover:text-white' }}">
                            @themeIcon('power-off')
                            {{ $menu->active ? 'Отключить' : 'Включить' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}"
                          onsubmit="return confirm('Удалить это меню вместе с пунктами?')"
                          title="Безвозвратное удаление меню и всех его пунктов">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md shadow transition">
                            @themeIcon('trash-alt') Удалить
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-gray-600 dark:text-gray-400 text-sm">
                ❗ Пока нет ни одного меню. Нажмите «Создать меню» выше.
            </div>
        @endforelse
    </div>

    @push('scripts')
    <script>
        // Фильтр по названию/позиции на клиенте — быстро и удобно
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
            posButtons.forEach(b => b.classList.toggle('bg-gray-200', b.dataset.pos==='all' ? currentPos!=='all' : false));
            applyFilter();
        }));
    </script>
    @endpush
@endsection
