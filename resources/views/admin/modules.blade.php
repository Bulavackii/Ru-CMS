@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    @php
        $activeCount    = $modules->where('active', true)->count();
        $missingCount   = $modules->where('is_installed', false)->count();
        $protectedCount = $modules->where('is_protected', true)->count();
    @endphp

    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-puzzle-piece"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Модули</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Подключение разделов CMS, порядок загрузки и установка новых модулей из архива.
                </p>
            </div>
        </div>

        {{-- Установка модуля из ZIP --}}
        <form action="{{ route('admin.modules.install') }}" method="POST" enctype="multipart/form-data"
              class="flex items-center gap-2 shrink-0">
            @csrf
            <input type="file" name="module" required accept=".zip"
                   class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-1.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition whitespace-nowrap">
                <i class="fas fa-upload"></i> Установить
            </button>
        </form>
    </div>

    {{-- ── Фильтры. Контроллер уже поддерживает search/status/signed/protected,
         но формы для них раньше не было — параметры можно было задать только руками
         в адресной строке. ── --}}
    <form method="GET" action="{{ route('admin.modules.index') }}" class="admin-card p-5 mb-5">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-indigo-500"></i> Фильтры
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Поиск</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                         width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Название или системное имя…"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Статус</label>
                <select name="status" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                             focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">Любой</option>
                    <option value="active" @selected(request('status') === 'active')>Активные</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Отключённые</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Тип</label>
                <select name="protected" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">Все модули</option>
                    <option value="yes" @selected(request('protected') === 'yes')>Только системные</option>
                    <option value="no" @selected(request('protected') === 'no')>Только дополнительные</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-4">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-magnifying-glass"></i> Применить
            </button>
            @if(request('search') || request('status') || request('protected') || request('signed'))
                <a href="{{ route('admin.modules.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                          text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <i class="fas fa-xmark"></i> Сбросить
                </a>
            @endif
        </div>
    </form>

    {{-- ── Подсказка + сводка ── --}}
    <div class="admin-hint px-4 py-3 mb-5 text-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2 font-medium">
                <i class="fas fa-lightbulb"></i>
                <span>Порядок загрузки меняется перетаскиванием строки за колонку «Приоритет». Системные модули отключить нельзя.</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs shrink-0">
                <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
                    Всего: {{ $modules->count() }}
                </span>
                <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
                    Активных: {{ $activeCount }}
                </span>
                @if($missingCount > 0)
                    <span class="bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-1 font-semibold">
                        Нет файлов: {{ $missingCount }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Таблица ── --}}
    <div class="admin-card overflow-hidden">
     <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Название</th>
                    <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">Версия</th>
                    <th class="px-4 py-3 text-center font-semibold">Статус</th>
                    <th class="px-4 py-3 text-center font-semibold hidden lg:table-cell">Файлы</th>
                    <th class="px-4 py-3 text-center font-semibold w-28">Приоритет</th>
                    <th class="px-4 py-3 text-center font-semibold w-32">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($modules as $module)
                <tr class="hover:bg-indigo-50/60 dark:hover:bg-gray-800 transition">
                    {{-- Название + бейджи (системный / подписан) --}}
                    <td class="px-4 py-3 align-top">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $module->title ?? $module->name }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="font-mono text-gray-400 dark:text-gray-500">{{ $module->name }}</span>
                            @if ($module->is_protected)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold
                                             bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300"
                                      title="Системный модуль: его нельзя отключить или удалить">
                                    <i class="fas fa-shield-halved"></i> Системный
                                </span>
                            @endif
                            @if ($module->is_signed)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold
                                             bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300"
                                      title="Модуль имеет цифровую подпись">
                                    <i class="fas fa-certificate"></i> Подписан
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Версия --}}
                    <td class="px-4 py-3 align-top hidden md:table-cell">
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono
                                     bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ $module->version ?? '—' }}
                        </span>
                    </td>

                    {{-- Статус --}}
                    <td class="px-4 py-3 align-top text-center">
                        @if ($module->active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                         bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                <i class="fas fa-circle-check"></i> Активен
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                         bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                <i class="fas fa-circle-minus"></i> Отключён
                            </span>
                        @endif
                    </td>

                    {{-- Наличие файлов модуля --}}
                    <td class="px-4 py-3 align-top text-center hidden lg:table-cell">
                        @if ($module->is_installed)
                            <span class="text-green-600 dark:text-green-400" title="Каталог модуля на месте">
                                <i class="fas fa-folder-open"></i>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                         bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300"
                                  title="Запись есть в базе, но каталога modules/{{ $module->name }} нет">
                                <i class="fas fa-triangle-exclamation"></i> Нет файлов
                            </span>
                        @endif
                    </td>

                    {{-- Приоритет (перетаскивание) --}}
                    <td class="px-4 py-3 align-top text-center cursor-move handle" data-id="{{ $module->id }}"
                        title="Перетащите строку, чтобы изменить порядок загрузки">
                        <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                            <i class="fas fa-grip-vertical text-gray-400"></i> {{ $module->priority ?? 0 }}
                        </span>
                    </td>

                    {{-- Действия --}}
                    <td class="px-4 py-3 align-top text-center">
                        <div class="inline-flex items-center gap-1.5">
                            {{-- Вкл/выкл: у системных модулей кнопка заблокирована (сервер всё
                                 равно откажет — раньше об этом узнавали только после клика) --}}
                            @if ($module->can_disable || ! $module->active)
                                <form method="POST" action="{{ route('admin.modules.toggle', $module->id) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button title="{{ $module->active ? 'Отключить модуль' : 'Включить модуль' }}"
                                            class="inline-flex items-center justify-center w-8 h-8 shadow-sm transition
                                                   {{ $module->active
                                                      ? 'bg-yellow-500 hover:bg-yellow-600 text-white'
                                                      : 'bg-green-600 hover:bg-green-700 text-white' }}">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                            @else
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed"
                                      title="Системный модуль должен оставаться активным">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif

                            <form method="POST" action="{{ route('admin.modules.archive', $module->id) }}" class="inline">
                                @csrf @method('PATCH')
                                <button title="Архивировать модуль в ZIP"
                                        class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                               text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition">
                                    <i class="fas fa-box-archive"></i>
                                </button>
                            </form>

                            @php $archivePath = base_path("modules/archives/{$module->name}.zip"); @endphp
                            @if (file_exists($archivePath))
                                <a href="{{ route('admin.modules.downloadArchive', ['name' => $module->name]) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                          text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                                   title="Скачать архив модуля">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif

                            @if ($module->can_delete)
                                <form method="POST" action="{{ route('admin.modules.destroy', $module->id) }}"
                                      class="inline"
                                      onsubmit="return confirm('Удалить модуль «{{ $module->title ?? $module->name }}»? Файлы модуля будут удалены безвозвратно.')">
                                    @csrf @method('DELETE')
                                    <button title="Удалить модуль"
                                            class="inline-flex items-center justify-center w-8 h-8 bg-red-600 hover:bg-red-700 text-white shadow-sm transition">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            @else
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed"
                                      title="Системный модуль удалить нельзя">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center">
                        <span class="admin-icon-badge mx-auto mb-3"><i class="fas fa-puzzle-piece"></i></span>
                        <p class="text-gray-600 dark:text-gray-300 font-medium">Модули не найдены.</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            @if(request('search') || request('status') || request('protected'))
                                Измените фильтры или
                                <a href="{{ route('admin.modules.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">сбросьте их</a>.
                            @else
                                Установите модуль из ZIP-архива в шапке страницы.
                            @endif
                        </p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
     </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ local_js('sortable.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.querySelector('tbody');
            if (!tbody) return;

            new Sortable(tbody, {
                handle: '.handle',
                animation: 150,
                // Односложные классы: SortableJS применяет их через classList,
                // а тот падает на строке с пробелами (см. CLAUDE.md).
                ghostClass: 'mod-ghost',
                chosenClass: 'mod-chosen',
                onEnd: function () {
                    let ids = [];
                    document.querySelectorAll('.handle').forEach((el, index) => {
                        ids.push({ id: el.dataset.id, priority: index + 1 });
                    });

                    fetch('{{ route('admin.modules.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ order: ids })
                    })
                    .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
                    .then(() => {
                        // Показываем новые значения приоритета сразу, без перезагрузки
                        document.querySelectorAll('.handle').forEach((el, index) => {
                            const label = el.querySelector('span');
                            if (label) label.lastChild.textContent = ' ' + (index + 1);
                        });
                    })
                    .catch(() => alert('Не удалось сохранить порядок модулей. Обновите страницу и попробуйте ещё раз.'));
                }
            });
        });
    </script>
    <style>
        .mod-ghost { opacity: .5; background: #e0e7ff; }
        .mod-chosen { background: #eef2ff; }
    </style>
@endpush
