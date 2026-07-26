@extends('layouts.admin')

@section('title', 'Уведомления')

@section('content')
@php
    $typeLabels = ['text' => 'Текст', 'html' => 'HTML', 'cookie' => 'Одноразовое'];
    $targetLabels = ['all' => 'Все', 'admin' => 'Админы', 'user' => 'Пользователи'];
    $positionLabels = ['top' => 'Сверху', 'bottom' => 'Снизу', 'fullscreen' => 'По центру'];
    $hasFilters = request()->hasAny(['search', 'type', 'target', 'position', 'enabled']);
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-bell"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Уведомления</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Баннеры для посетителей сайта: объявления, техработы, предупреждения.
            </p>
        </div>
    </div>

    <a href="{{ route('admin.notifications.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition flex-shrink-0">
        <i class="fas fa-plus"></i> Добавить уведомление
    </a>
</div>

{{-- ── Сводка ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach([
        ['Всего', $stats['total'], 'fa-layer-group', 'Уведомлений в системе'],
        ['Включено', $stats['enabled'], 'fa-toggle-on', 'Переключатель «Вкл.»'],
        ['Показывается сейчас', $stats['active'], 'fa-eye', 'С учётом периода показа'],
        ['Показов', $stats['views'], 'fa-chart-simple', 'Сколько раз увидели'],
    ] as [$label, $value, $icon, $hint])
        <div class="admin-card p-4">
            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <i class="fas {{ $icon }} text-indigo-500"></i> {{ $label }}
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($value, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $hint }}</div>
        </div>
    @endforeach
</div>

{{-- ── Фильтры ── --}}
<form method="GET" action="{{ route('admin.notifications.index') }}" class="admin-card p-5 mb-5">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
        <i class="fas fa-filter text-indigo-500"></i> Фильтры
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
        <div class="xl:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Поиск</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                     width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Заголовок или текст…"
                       class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Тип</label>
            <select name="type" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Все типы</option>
                @foreach($typeLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Аудитория</label>
            <select name="target" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Все аудитории</option>
                @foreach($targetLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('target') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Статус</label>
            <select name="enabled" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Все статусы</option>
                <option value="1" @selected(request('enabled') === '1')>Включённые</option>
                <option value="0" @selected(request('enabled') === '0')>Отключённые</option>
            </select>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button type="submit"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-magnifying-glass"></i> Применить
        </button>
        @if($hasFilters)
            <a href="{{ route('admin.notifications.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-rotate-left"></i> Сбросить
            </a>
        @endif
    </div>
</form>

@if($notifications->isEmpty())
    {{-- ── Пустое состояние ── --}}
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-bell"></i></span>

        @if($hasFilters)
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Ничего не найдено</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                Под выбранные фильтры не подходит ни одно уведомление.
            </p>
            <a href="{{ route('admin.notifications.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-rotate-left"></i> Сбросить фильтры
            </a>
        @else
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Уведомлений пока нет</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto mb-5">
                Уведомление — это баннер поверх сайта: сообщение о техработах, акции или
                предупреждение. Можно показывать всем или только авторизованным, на всех
                страницах или на выбранных, постоянно или в заданный период.
            </p>

            <div class="text-left max-w-2xl mx-auto grid grid-cols-1 sm:grid-cols-3 gap-2 mb-6">
                @foreach([
                    ['fa-users', 'Кому', 'Всем, только админам или только авторизованным'],
                    ['fa-map-location-dot', 'Где', 'На всех страницах или по адресу: /about, /news/*'],
                    ['fa-clock', 'Когда', 'Постоянно, по таймеру или в период дат'],
                ] as [$icon, $title, $text])
                    <div class="border border-gray-200 dark:border-gray-700 p-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                            <i class="fas {{ $icon }} text-indigo-500"></i> {{ $title }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            <a href="{{ route('admin.notifications.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-plus"></i> Создать первое уведомление
            </a>
        @endif
    </div>
@else
    {{-- ── Массовые действия ──
         Форма только оборачивает панель: чекбоксы в таблице привязаны к ней
         атрибутом form. Раньше форма охватывала таблицу и не закрывалась, а
         внутри неё лежали формы включения и удаления — вложенные формы HTML
         запрещает, поэтому кнопки строк отправляли не свою форму. --}}
    <form method="POST" action="{{ route('admin.notifications.bulk') }}" id="bulkForm" class="admin-card p-4 mb-4">
        @csrf
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">С отмеченными:</span>

            <select name="action"
                    class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Выберите действие</option>
                <option value="enable">Включить</option>
                <option value="disable">Отключить</option>
                <option value="delete">Удалить</option>
            </select>

            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition"
                    onclick="return confirm('Применить действие к отмеченным уведомлениям?')">
                <i class="fas fa-bolt"></i> Применить
            </button>

            <span id="bulkCounter" class="text-sm text-gray-500 dark:text-gray-400"></span>
        </div>
    </form>

    {{-- ── Таблица ── --}}
    <div class="admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-center w-10">
                        <input type="checkbox" id="selectAll" class="border-gray-400">
                    </th>
                    <th class="px-4 py-3 text-left font-semibold">Уведомление</th>
                    <th class="px-4 py-3 text-left font-semibold">Кому и где</th>
                    <th class="px-4 py-3 text-left font-semibold">Показ</th>
                    <th class="px-4 py-3 text-center font-semibold">Показов</th>
                    <th class="px-4 py-3 text-center font-semibold">Вкл.</th>
                    <th class="px-4 py-3 text-center font-semibold">Действия</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($notifications as $notification)
                    @php
                        $now = now();
                        $notStarted = $notification->starts_at && $notification->starts_at->gt($now);
                        $expired = $notification->ends_at && $notification->ends_at->lt($now);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 text-center align-top">
                            <input type="checkbox" name="selected[]" value="{{ $notification->id }}"
                                   form="bulkForm" class="bulk-checkbox border-gray-400">
                        </td>

                        <td class="px-4 py-3 align-top">
                            <div class="flex items-start gap-2">
                                @if($notification->icon)
                                    <span class="text-base leading-5">
                                        @if(str_starts_with(trim($notification->icon), 'fa'))
                                            <i class="{{ $notification->icon }} text-indigo-500"></i>
                                        @else
                                            {{ $notification->icon }}
                                        @endif
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 dark:text-white break-words">
                                        {{ $notification->title }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 break-words">
                                        {{ \Illuminate\Support\Str::limit(trim(strip_tags($notification->message)), 90) }}
                                    </div>
                                    <span class="inline-block text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 mt-1">
                                        {{ $typeLabels[$notification->type] ?? $notification->type }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-1">
                                <i class="fas fa-users text-gray-400 w-4"></i>
                                {{ $targetLabels[$notification->target] ?? $notification->target }}
                            </div>
                            <div class="flex items-center gap-1 mt-1">
                                <i class="fas fa-map-location-dot text-gray-400 w-4"></i>
                                <span class="font-mono text-xs">{{ $notification->route_filter ?: 'все страницы' }}</span>
                            </div>
                            <div class="flex items-center gap-1 mt-1">
                                <i class="fas fa-location-dot text-gray-400 w-4"></i>
                                {{ $positionLabels[$notification->position] ?? $notification->position }}
                            </div>
                        </td>

                        <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                            <div>{{ $notification->duration ? $notification->duration . ' сек' : 'до закрытия' }}</div>

                            @if($notification->starts_at || $notification->ends_at)
                                <div class="text-xs mt-1">
                                    {{ optional($notification->starts_at)->format('d.m.Y H:i') ?: '…' }}
                                    —
                                    {{ optional($notification->ends_at)->format('d.m.Y H:i') ?: '…' }}
                                </div>
                            @endif

                            @if($notStarted)
                                <span class="inline-block text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 mt-1">ещё не началось</span>
                            @elseif($expired)
                                <span class="inline-block text-xs bg-gray-200 text-gray-700 px-2 py-0.5 mt-1">срок вышел</span>
                            @elseif($notification->enabled)
                                <span class="inline-block text-xs bg-green-100 text-green-800 px-2 py-0.5 mt-1">показывается</span>
                            @endif

                            @if($notification->priority)
                                <div class="text-xs text-gray-400 mt-1">приоритет {{ $notification->priority }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center align-top text-gray-700 dark:text-gray-300">
                            {{ $notification->views_count }}
                        </td>

                        <td class="px-4 py-3 text-center align-top">
                            <button type="submit" form="toggle-{{ $notification->id }}"
                                    title="{{ $notification->enabled ? 'Отключить' : 'Включить' }}"
                                    class="admin-toggle-btn {{ $notification->enabled ? 'is-on' : '' }}">
                                <span class="dot"></span>
                            </button>
                        </td>

                        <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                            <a href="{{ route('admin.notifications.preview', $notification) }}" target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                      text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                               title="Предпросмотр">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.notifications.edit', $notification) }}"
                               class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                      text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                               title="Редактировать">
                                <i class="fas fa-pen"></i>
                            </a>
                            <button type="submit" form="delete-{{ $notification->id }}"
                                    class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                           text-gray-600 dark:text-gray-300 hover:border-red-400 hover:text-red-600 transition"
                                    title="Удалить">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>

    {{-- Формы действий строк — вне таблицы и вне формы массовых действий --}}
    @foreach ($notifications as $notification)
        <form id="toggle-{{ $notification->id }}" method="POST"
              action="{{ route('admin.notifications.toggle', $notification) }}" class="hidden">
            @csrf @method('PATCH')
        </form>
        <form id="delete-{{ $notification->id }}" method="POST"
              action="{{ route('admin.notifications.destroy', $notification) }}" class="hidden"
              onsubmit="return confirm('Удалить уведомление «{{ addslashes($notification->title) }}»?')">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif
@endsection

@push('styles')
<style>
    /* Переключатель строки: peer-checked в этой сборке Tailwind отсутствует */
    .admin-toggle-btn{ width:2.5rem; height:1.4rem; border:1px solid #d1d5db; background:#e5e7eb;
        position:relative; cursor:pointer; padding:0; transition:background-color .15s, border-color .15s; }
    .admin-toggle-btn .dot{ position:absolute; top:2px; left:2px; width:1rem; height:1rem;
        background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.25); transition:left .15s ease; }
    .admin-toggle-btn.is-on{ background:#4f46e5; border-color:#4f46e5; }
    .admin-toggle-btn.is-on .dot{ left:calc(100% - 1.13rem); }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const selectAll = document.getElementById('selectAll');
        const boxes = () => document.querySelectorAll('.bulk-checkbox');
        const counter = document.getElementById('bulkCounter');

        const refresh = () => {
            const checked = document.querySelectorAll('.bulk-checkbox:checked').length;
            if (counter) counter.textContent = checked ? `отмечено: ${checked}` : '';
            if (selectAll) selectAll.checked = checked > 0 && checked === boxes().length;
        };

        selectAll?.addEventListener('change', function () {
            boxes().forEach(cb => { cb.checked = this.checked; });
            refresh();
        });

        boxes().forEach(cb => cb.addEventListener('change', refresh));
        refresh();
    })();
</script>
@endpush
