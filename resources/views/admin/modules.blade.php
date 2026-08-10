@extends('layouts.admin')

@section('title', __('admin.sections.modules'))

@section('content')
    @php
        // Значки разделов. В `module.json` у каждого модуля свой эмодзи, и в
        // колонке они выглядели вразнобой: разный рисунок, разная толщина, а
        // часть на мелком кегле просто не читалась. Здесь один набор — тот же
        // Font Awesome, что во всей панели.
        //
        // Это ОФОРМЛЕНИЕ, а не правка данных: `title` в module.json не
        // трогается, эмодзи оттуда просто не выводится.
        $moduleIcons = [
            'System'        => 'fa-gear',
            'Menu'          => 'fa-bars',
            'Categories'    => 'fa-folder-tree',
            'News'          => 'fa-newspaper',
            'NewsIO'        => 'fa-right-left',
            'Files'         => 'fa-folder-open',
            'Users'         => 'fa-users',
            'Notifications' => 'fa-bell',
            'Messages'      => 'fa-envelope',
            'Slideshow'     => 'fa-images',
            'Payments'      => 'fa-credit-card',
            'Delivery'      => 'fa-truck',
            'Reviews'       => 'fa-star',
            'Search'        => 'fa-magnifying-glass',
            'Seo'           => 'fa-chart-line',
            'Localization'  => 'fa-globe',
            'Captcha'       => 'fa-shield-halved',
            'Accessibility' => 'fa-universal-access',
            'Forms'         => 'fa-clipboard-list',
            'Visual'        => 'fa-palette',
            'Comments'      => 'fa-comments',
        ];
    @endphp

    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-puzzle-piece"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.sections.modules') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.modules.index_hint') }}
                </p>
            </div>
        </div>

        {{-- Сводка вместо формы установки: в шапке стояло системное поле
             выбора файла («Файл не выбран») — единственный неоформленный
             элемент на странице. Установка переехала в свою карточку ниже. --}}
        <div class="mod-summary">
            <span class="mod-summary__item">
                <i class="fas fa-cubes"></i> {{ __('admin.modules.total') }} <b>{{ $totalCount }}</b>
            </span>
            <span class="mod-summary__item {{ $activeCount ? 'is-on' : '' }}">
                <i class="fas fa-circle-check"></i> {{ __('admin.modules.active_count') }} <b>{{ $activeCount }}</b>
            </span>
            <span class="mod-summary__item">
                <i class="fas fa-shield-halved"></i> {{ __('admin.modules.system_count') }} <b>{{ $protectedCount }}</b>
            </span>
            @if($missingCount > 0)
                <span class="mod-summary__item is-warn">
                    <i class="fas fa-triangle-exclamation"></i> {{ __('admin.modules.no_files') }} <b>{{ $missingCount }}</b>
                </span>
            @endif
        </div>
    </div>

    {{-- ── Установка из архива ──
         Поле выбора файла системное и в панели выглядело чужим. Здесь та же
         форма, но с обычной кнопкой и именем выбранного файла рядом. --}}
    <div class="admin-card mb-5 mod-install">
        <div class="mod-install__body">
            <span class="mod-install__ico"><i class="fas fa-file-zipper"></i></span>

            <div class="mod-install__text">
                <span class="mod-install__title">{{ __('admin.modules.install_title') }}</span>
                <span class="mod-hint">{{ __('admin.modules.install_hint') }}</span>
            </div>

            <form action="{{ route('admin.modules.install') }}" method="POST" enctype="multipart/form-data"
                  class="mod-install__form">
                @csrf
                <input id="module-file" type="file" name="module" required accept=".zip" class="hidden">

                <button type="button" class="mod-btn" onclick="document.getElementById('module-file').click()">
                    <i class="fas fa-folder-open"></i> {{ __('admin.modules.choose_file') }}
                </button>

                <span id="module-file-name" class="mod-hint">{{ __('admin.modules.no_file_chosen') }}</span>

                <button type="submit" class="mod-btn mod-btn--primary">
                    <i class="fas fa-upload"></i> {{ __('admin.modules.install') }}
                </button>
            </form>
        </div>
    </div>

    {{-- ── Фильтры. Контроллер уже поддерживает search/status/signed/protected,
         но формы для них раньше не было — параметры можно было задать только руками
         в адресной строке. ── --}}
    <form method="GET" action="{{ route('admin.modules.index') }}" class="admin-card p-5 mb-5">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-indigo-500"></i> {{ __('admin.common.filters') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.header.search') }}</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                         width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ __('admin.modules.search_ph') }}"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.modules.filter_status') }}</label>
                <select name="status" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                             focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">{{ __('admin.modules.any') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('admin.modules.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('admin.modules.disabled') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.modules.type') }}</label>
                <select name="protected" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">{{ __('admin.modules.all_modules') }}</option>
                    <option value="yes" @selected(request('protected') === 'yes')>{{ __('admin.modules.system_only') }}</option>
                    <option value="no" @selected(request('protected') === 'no')>{{ __('admin.modules.extra_only') }}</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-4">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-magnifying-glass"></i> {{ __('admin.common.apply') }}
            </button>
            @if(request('search') || request('status') || request('protected') || request('signed'))
                <a href="{{ route('admin.modules.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                          text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <i class="fas fa-xmark"></i> {{ __('admin.users.reset') }}
                </a>
            @endif
        </div>
    </form>

    {{-- ── Подсказка ──
         Счётчики уехали в шапку: здесь они прижимались к правому краю
         подсказки и читались как её продолжение. --}}
    <div class="admin-note px-4 py-3 mb-5 text-sm">
        <div class="flex items-center gap-2 font-medium">
            <i class="fas fa-lightbulb"></i>
            <span>{{ __('admin.modules.order_hint') }}</span>
        </div>
    </div>

    {{-- ── Таблица ── --}}
    <div class="admin-card overflow-hidden">
     <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">{{ __('admin.modules.name') }}</th>
                    <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">{{ __('admin.modules.version') }}</th>
                    <th class="px-4 py-3 text-center font-semibold">{{ __('admin.modules.filter_status') }}</th>
                    <th class="px-4 py-3 text-center font-semibold hidden lg:table-cell">{{ __('admin.modules.files') }}</th>
                    <th class="px-4 py-3 text-center font-semibold w-28">{{ __('admin.modules.priority') }}</th>
                    <th class="px-4 py-3 text-center font-semibold w-32">{{ __('admin.common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" data-offset="{{ ($modules->firstItem() ?? 1) - 1 }}">
            @forelse ($modules as $module)
                <tr class="mod-row">
                    {{-- Название + бейджи (системный / подписан) --}}
                    @php
                        // Ведущий эмодзи из title убираем: значок теперь рисует
                        // плитка слева, и два знака подряд читались как ошибка.
                        $moduleTitle = trim(preg_replace('~^[^\p{L}\p{N}]+~u', '', $module->title ?? $module->name));
                        $moduleIcon = $moduleIcons[$module->name] ?? 'fa-puzzle-piece';
                    @endphp
                    <td class="px-4 py-3 align-top">
                        <div class="mod-name">
                            <span class="mod-ico {{ $module->is_protected ? 'is-system' : '' }}">
                                <i class="fas {{ $moduleIcon }}"></i>
                            </span>

                            <div class="mod-name__body">
                                <span class="mod-name__title">{{ $moduleTitle ?: $module->name }}</span>

                                <span class="mod-name__meta">
                                    <span class="mod-name__slug">{{ $module->name }}</span>
                            @if ($module->is_protected)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold
                                             bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300"
                                      title="{{ __('admin.modules.system_title') }}">
                                    <i class="fas fa-shield-halved"></i> {{ __('admin.modules.system') }}
                                </span>
                            @endif
                            @if ($module->is_signed)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold
                                             bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300"
                                      title="{{ __('admin.modules.signed_title') }}">
                                    <i class="fas fa-certificate"></i> {{ __('admin.modules.signed') }}
                                </span>
                            @endif
                                </span>
                            </div>
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
                                <i class="fas fa-circle-check"></i> {{ __('admin.modules.is_active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                         bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                <i class="fas fa-circle-minus"></i> {{ __('admin.modules.is_off') }}
                            </span>
                        @endif
                    </td>

                    {{-- Наличие файлов модуля --}}
                    <td class="px-4 py-3 align-top text-center hidden lg:table-cell">
                        @if ($module->is_installed)
                            {{-- Раньше здесь был одинокий значок папки: понять по
                                 нему, что именно проверено, было нельзя. --}}
                            <span class="mod-chip is-ok" title="{{ __('admin.modules.dir_ok') }}">
                                <i class="fas fa-folder-open"></i> {{ __('admin.modules.files_ok') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                         bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300"
                                  title="{{ __('admin.modules.dir_missing') }}">
                                <i class="fas fa-triangle-exclamation"></i> {{ __('admin.modules.no_files') }}
                            </span>
                        @endif
                    </td>

                    {{-- Приоритет (перетаскивание) --}}
                    <td class="px-4 py-3 align-top text-center cursor-move handle" data-id="{{ $module->id }}"
                        title="{{ __('admin.modules.drag_title') }}">
                        <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                            <i class="fas fa-grip-vertical text-gray-400"></i> {{ $module->priority ?? 0 }}
                        </span>
                    </td>

                    {{-- Действия.
                         Кнопка включения была залита оранжевым или зелёным и
                         оказывалась самым громким пятном страницы, хотя это
                         рядовое действие. Теперь ряд одинаковых значков, а
                         цветом выделено только удаление — единственное
                         необратимое. --}}
                    <td class="px-4 py-3 align-top text-center">
                        <div class="mod-actions">
                            {{-- Вкл/выкл: у системных модулей кнопка заблокирована (сервер всё
                                 равно откажет — раньше об этом узнавали только после клика) --}}
                            @if ($module->can_disable || ! $module->active)
                                <form method="POST" action="{{ route('admin.modules.toggle', $module->id) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="mod-icon {{ $module->active ? 'is-warn' : 'is-go' }}"
                                            title="{{ $module->active ? __('admin.modules.turn_off') : __('admin.modules.turn_on') }}">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                            @else
                                <span class="mod-icon is-locked" title="{{ __('admin.modules.must_stay') }}">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif

                            <form method="POST" action="{{ route('admin.modules.archive', $module->id) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="mod-icon" title="{{ __('admin.modules.archive') }}">
                                    <i class="fas fa-box-archive"></i>
                                </button>
                            </form>

                            @php $archivePath = base_path("modules/archives/{$module->name}.zip"); @endphp
                            @if (file_exists($archivePath))
                                <a href="{{ route('admin.modules.downloadArchive', ['name' => $module->name]) }}"
                                   class="mod-icon" title="{{ __('admin.modules.download') }}">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif

                            @if ($module->can_delete)
                                <form method="POST" action="{{ route('admin.modules.destroy', $module->id) }}"
                                      class="inline"
                                      onsubmit="return confirm(@js(__('admin.modules.delete_confirm', ['name' => $module->title ?? $module->name])))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="mod-icon is-danger" title="{{ __('admin.modules.remove') }}">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            @else
                                <span class="mod-icon is-locked" title="{{ __('admin.modules.cant_remove') }}">
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
                        <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('admin.modules.not_found') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            @if(request('search') || request('status') || request('protected'))
                                {{ __('admin.modules.change_filters') }}
                                <a href="{{ route('admin.modules.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('admin.modules.reset_them') }}</a>.
                            @else
                                {{ __('admin.modules.install_from_zip') }}
                            @endif
                        </p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
     </div>
    </div>

    {{-- ── Постраничный вывод ──
         Тот же компонент, что во всех списках проекта. Рядом — выбор размера
         страницы: модулей может быть и двадцать, и две сотни. --}}
    <div class="mt-6 flex flex-col md:flex-row md:items-center gap-3">
        <form method="GET" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <span>{{ __('admin.common.per_page') }}</span>
            <select name="per_page" onchange="this.form.submit()"
                    class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-2 py-1 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>

            {{-- Своя подпись только когда страница одна: у общего компонента
                 она уже есть, и на нескольких страницах счётчик выводился бы
                 дважды. --}}
            @unless($modules->hasPages())
                <span class="text-gray-400">
                    {{ __('admin.files.showing', [
                        'from'  => $modules->firstItem() ?? 0,
                        'to'    => $modules->lastItem() ?? 0,
                        'total' => $modules->total(),
                    ]) }}
                </span>
            @endunless
        </form>

        <div class="md:ml-auto flex-1">
            {{ $modules->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ local_js('sortable.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.querySelector('tbody');
            if (!tbody) return;

            // Смещение страницы: без него на второй странице первая строка
            // получила бы приоритет 1 и перетёрла порядок первой.
            const offset = Number(tbody.dataset.offset || 0);

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
                        ids.push({ id: el.dataset.id, priority: offset + index + 1 });
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
                            if (label) label.lastChild.textContent = ' ' + (offset + index + 1);
                        });
                    })
                    .catch(() => alert(@js(__('admin.modules.order_error'))));
                }
            });
        });
    </script>
    <script>
        // Имя выбранного архива рядом с кнопкой: системное поле выбора файла
        // скрыто, и без этой строки было бы непонятно, выбран ли файл.
        (function () {
            var input = document.getElementById('module-file');
            var label = document.getElementById('module-file-name');

            if (!input || !label) {
                return;
            }

            var empty = label.textContent;

            input.addEventListener('change', function () {
                label.textContent = input.files && input.files[0] ? input.files[0].name : empty;
            });
        })();
    </script>
@endpush

@push('styles')
    <style>
        /* Литеральный CSS: в сборке проекта нет ни прозрачности через дробь
           (`hover:bg-indigo-50/60` не рендерился вовсе), ни произвольных
           значений. Скругления сняты общим рубильником `body.admin-sharp`. */

        .mod-ghost { opacity:.5; background:#e0e7ff }
        .mod-chosen { background:#eef2ff }

        .mod-row{ transition:background-color .15s }
        .mod-row:hover{ background:color-mix(in srgb, var(--admin-primary) 6%, transparent) }

        .mod-hint{ font-size:.75rem; color:#6b7280 }
        .dark .mod-hint{ color:#9ca3af }

        /* Сводка в шапке */
        .mod-summary{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; flex:none }
        .mod-summary__item{ display:inline-flex; align-items:center; gap:.4rem;
            padding:.3rem .6rem; font-size:.78rem; color:#4b5563;
            background:#f9fafb; border:1px solid #e5e7eb }
        .mod-summary__item i{ color:#9ca3af }
        .mod-summary__item b{ color:#111827 }
        .mod-summary__item.is-on i{ color:#16a34a }
        .mod-summary__item.is-warn{ color:#92400e; background:#fffbeb; border-color:#f0d9a8 }
        .mod-summary__item.is-warn i, .mod-summary__item.is-warn b{ color:#b45309 }
        .dark .mod-summary__item{ color:#d1d5db; background:#111827; border-color:#374151 }
        .dark .mod-summary__item b{ color:#f3f4f6 }

        /* Установка из архива */
        .mod-install__body{ display:flex; flex-wrap:wrap; align-items:center; gap:1rem; padding:1rem 1.25rem }
        .mod-install__ico{ display:inline-flex; align-items:center; justify-content:center; flex:none;
            width:2.5rem; height:2.5rem; font-size:1rem; color:var(--admin-on-primary,#fff);
            background:linear-gradient(135deg, var(--admin-primary), var(--admin-accent, var(--admin-primary))) }
        .mod-install__text{ display:flex; flex-direction:column; gap:.15rem; min-width:12rem; flex:1 }
        .mod-install__title{ font-size:.9rem; font-weight:600; color:#111827 }
        .dark .mod-install__title{ color:#f3f4f6 }
        .mod-install__form{ display:flex; flex-wrap:wrap; align-items:center; gap:.6rem }

        /* Кнопки */
        .mod-btn{ display:inline-flex; align-items:center; gap:.45rem; padding:.5rem .8rem;
            font-size:.8rem; font-weight:600; white-space:nowrap; cursor:pointer;
            color:#374151; background:#fff; border:1px solid #d1d5db; text-decoration:none;
            transition:background-color .15s, border-color .15s, color .15s }
        .mod-btn:hover{ background:#f3f4f6; border-color:var(--admin-primary); color:var(--admin-primary) }
        .dark .mod-btn{ color:#d1d5db; background:#1f2937; border-color:#374151 }
        .mod-btn--primary{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
            border-color:var(--admin-primary) }
        .mod-btn--primary:hover{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
            border-color:var(--admin-primary); filter:brightness(1.08) }

        /* Значки действий */
        .mod-actions{ display:inline-flex; align-items:center; gap:.35rem }
        .mod-icon{ display:inline-flex; align-items:center; justify-content:center;
            width:2rem; height:2rem; font-size:.8rem; cursor:pointer;
            color:#4b5563; background:#fff; border:1px solid #e5e7eb;
            transition:border-color .15s, color .15s }
        .mod-icon:hover{ border-color:var(--admin-primary); color:var(--admin-primary) }
        .dark .mod-icon{ color:#d1d5db; background:#111827; border-color:#374151 }
        .mod-icon.is-go{ color:#15803d }
        .mod-icon.is-go:hover{ border-color:#16a34a; color:#15803d }
        .mod-icon.is-warn{ color:#b45309 }
        .mod-icon.is-warn:hover{ border-color:#d97706; color:#b45309 }
        .mod-icon.is-danger{ color:#b91c1c }
        .mod-icon.is-danger:hover{ border-color:#dc2626; color:#b91c1c }
        .mod-icon.is-locked{ color:#9ca3af; background:#f9fafb; cursor:not-allowed }
        .mod-icon.is-locked:hover{ border-color:#e5e7eb; color:#9ca3af }
        .dark .mod-icon.is-locked{ background:#1f2937 }

        /* Название модуля: плитка со значком, имя, системное имя и бейджи */
        .mod-name{ display:flex; align-items:flex-start; gap:.65rem; min-width:0 }
        .mod-ico{ display:inline-flex; align-items:center; justify-content:center; flex:none;
            width:2.1rem; height:2.1rem; font-size:.85rem;
            color:var(--admin-primary);
            background:color-mix(in srgb, var(--admin-primary) 12%, transparent);
            border:1px solid color-mix(in srgb, var(--admin-primary) 25%, transparent) }
        .mod-ico.is-system{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
            border-color:var(--admin-primary) }
        .mod-name__body{ display:flex; flex-direction:column; gap:.2rem; min-width:0 }
        .mod-name__title{ font-size:.9rem; font-weight:600; color:#111827 }
        .dark .mod-name__title{ color:#f3f4f6 }
        .mod-name__meta{ display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; font-size:.72rem }
        .mod-name__slug{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; color:#9ca3af }

        /* Чип «файлы на месте» */
        .mod-chip{ display:inline-flex; align-items:center; gap:.35rem;
            padding:.15rem .5rem; font-size:.72rem; font-weight:600; white-space:nowrap }
        .mod-chip.is-ok{ color:#15803d; background:#dcfce7; border:1px solid #86efac }
    </style>
@endpush
