@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    {{-- ───────────── Header + actions ───────────── --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🏷️ Список категорий</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Управляйте категориями: ищите, редактируйте, выделяйте и удаляйте пачками.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- ➕ Добавить --}}
            <a href="{{ route('admin.categories.create') }}"
               class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm transition">
                @themeIcon('plus') Добавить
            </a>
        </div>
    </div>

    {{-- ───────────── Filters ───────────── --}}
    <form method="GET" action="{{ route('admin.categories.index') }}" class="mb-4 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Поиск --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🔍 Поиск</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm"
                       placeholder="Название, описание...">
            </div>

            {{-- Тип --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📋 Тип</label>
                <select name="type" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm">
                    <option value="">Все типы</option>
                    @foreach($types ?? [] as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Родитель --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🔗 Родитель</label>
                <select name="parent_id" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm">
                    <option value="">Все категории</option>
                    <option value="null" {{ request('parent_id') === 'null' ? 'selected' : '' }}>Корневые</option>
                    @foreach($parentCategories ?? [] as $parent)
                        <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->title }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Активность --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">✅ Активность</label>
                <select name="is_active" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm">
                    <option value="">Все</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Активные</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Неактивные</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-4">
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md shadow text-sm transition">
                @themeIcon('search') Применить фильтры
            </button>
            <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                @themeIcon('xmark') Сбросить
            </a>
        </div>
    </form>

    {{-- ───────────── Bulk Actions ───────────── --}}
    <div id="bulkActions" class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 hidden">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                Выбрано: <span id="selCount">0</span>
            </span>
            
            {{-- Массовое удаление --}}
            <button id="bulkDeleteBtn" class="inline-flex items-center gap-2 bg-red-600 text-white hover:bg-red-700 px-3 py-1.5 rounded-md shadow text-sm transition">
                @themeIcon('trash') Удалить
            </button>

            {{-- Массовое изменение типа --}}
            <div class="flex items-center gap-2">
                <input type="text" id="bulkTypeInput" placeholder="Тип" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-2 py-1.5 text-sm w-32">
                <button id="bulkUpdateTypeBtn" class="inline-flex items-center gap-2 bg-purple-600 text-white hover:bg-purple-700 px-3 py-1.5 rounded-md shadow text-sm transition">
                    @themeIcon('edit') Изменить тип
                </button>
            </div>

            {{-- Массовое изменение родителя --}}
            <div class="flex items-center gap-2">
                <select id="bulkParentSelect" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-2 py-1.5 text-sm">
                    <option value="">Убрать родителя</option>
                    @foreach($parentCategories ?? [] as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                    @endforeach
                </select>
                <button id="bulkUpdateParentBtn" class="inline-flex items-center gap-2 bg-indigo-600 text-white hover:bg-indigo-700 px-3 py-1.5 rounded-md shadow text-sm transition">
                    @themeIcon('edit') Изменить родителя
                </button>
            </div>

            {{-- Массовое изменение активности --}}
            <button id="bulkActivateBtn" class="inline-flex items-center gap-2 bg-green-600 text-white hover:bg-green-700 px-3 py-1.5 rounded-md shadow text-sm transition">
                @themeIcon('check') Активировать
            </button>
            <button id="bulkDeactivateBtn" class="inline-flex items-center gap-2 bg-gray-600 text-white hover:bg-gray-700 px-3 py-1.5 rounded-md shadow text-sm transition">
                @themeIcon('xmark') Деактивировать
            </button>
        </div>
    </div>

    {{-- ───────────── Info strip ───────────── --}}
    <div class="mb-4 rounded-xl border border-blue-200/70 dark:border-blue-900/40 bg-blue-50 dark:bg-blue-900/30 p-3 text-sm text-blue-900 dark:text-blue-100 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            @themeIcon('lightbulb') Подсказки:
            <span>Shift-клик — выделение диапазона.</span>
            <span class="hidden sm:inline">Ctrl+F — фокус на поиск.</span>
        </div>
        <div class="text-blue-800/80 dark:text-blue-100/80">
            Показано <b>{{ $categories->count() }}</b> из <b>{{ $categories->total() }}</b>
        </div>
    </div>

    {{-- ───────────── Table ───────────── --}}
    <div class="overflow-x-auto rounded-xl shadow border border-gray-200 dark:border-gray-800">
        <table id="categoriesTable" class="min-w-full bg-white dark:bg-gray-900 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 w-10">
                    <input id="checkAll" type="checkbox" class="h-4 w-4 rounded text-blue-600">
                </th>
                <th class="px-4 py-3 text-left">🏷️ Название</th>
                <th class="px-4 py-3 text-left hidden lg:table-cell">📋 Тип</th>
                <th class="px-4 py-3 text-left hidden lg:table-cell">🔗 Родитель</th>
                <th class="px-4 py-3 text-center hidden md:table-cell">📊 Использование</th>
                <th class="px-4 py-3 text-center hidden md:table-cell">✅ Статус</th>
                <th class="px-4 py-3 text-center">⚙️ Действия</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($categories as $category)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition" data-id="{{ $category->id }}">
                    {{-- checkbox --}}
                    <td class="px-4 py-3">
                        <input type="checkbox" value="{{ $category->id }}"
                               class="rowCbx h-4 w-4 rounded text-blue-600">
                    </td>

                    {{-- title + icon --}}
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                        <div class="flex items-center gap-2">
                            @if ($category->icon)
                                <span class="text-lg">{!! $category->icon !!}</span>
                            @else
                                <span class="text-gray-400">@themeIcon('tag')</span>
                            @endif

                            <div class="flex flex-col">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold
                                    bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 titleCell">
                                    {{ $category->title }}
                                </span>
                                @if($category->slug)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $category->slug }}</span>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- type --}}
                    <td class="px-4 py-3 hidden lg:table-cell">
                        @if($category->type)
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ $category->type }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>

                    {{-- parent --}}
                    <td class="px-4 py-3 hidden lg:table-cell">
                        @if($category->parent)
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $category->parent->title }}</span>
                        @else
                            <span class="text-gray-400 text-xs">Корневая</span>
                        @endif
                    </td>

                    {{-- usage counts --}}
                    <td class="px-4 py-3 text-center hidden md:table-cell">
                        <div class="flex items-center justify-center gap-2 text-xs">
                            @if($category->news_count > 0)
                                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded" title="Новостей">
                                    📰 {{ $category->news_count }}
                                </span>
                            @endif
                            @if($category->pages_count > 0)
                                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded" title="Страниц">
                                    📄 {{ $category->pages_count }}
                                </span>
                            @endif
                            @if($category->children_count > 0)
                                <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded" title="Дочерних категорий">
                                    📁 {{ $category->children_count }}
                                </span>
                            @endif
                            @if($category->news_count == 0 && $category->pages_count == 0 && $category->children_count == 0)
                                <span class="text-gray-400">—</span>
                            @endif
                        </div>
                    </td>

                    {{-- status --}}
                    <td class="px-4 py-3 text-center hidden md:table-cell">
                        @if($category->is_active)
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                Активна
                            </span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                Неактивна
                            </span>
                        @endif
                    </td>

                    {{-- actions --}}
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md shadow text-xs font-medium transition"
                           title="Редактировать">
                            @themeIcon('edit') Редактировать
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-10 text-center text-gray-500 dark:text-gray-400">
                        📭 Категорий не найдено. Нажмите «Добавить» для создания новой категории.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- pagination --}}
    <div class="mt-6">
        {{ $categories->withQueryString()->links('vendor.pagination.tailwind') }}
    </div>

    {{-- ───────────── Scripts ───────────── --}}
    <script>
        const csrfToken = @json(csrf_token());
        const bulkDeleteUrl = @json(route('admin.categories.bulkDelete'));
        const bulkUpdateTypeUrl = @json(route('admin.categories.bulk-update-type'));
        const bulkUpdateParentUrl = @json(route('admin.categories.bulk-update-parent'));
        const bulkUpdateActiveUrl = @json(route('admin.categories.bulk-update-active'));

        const $  = (s, r=document) => r.querySelector(s);
        const $$ = (s, r=document) => [...r.querySelectorAll(s)];

        const checkAll      = $('#checkAll');
        const cbxRows       = $$('.rowCbx');
        const bulkActions   = $('#bulkActions');
        const selCount      = $('#selCount');
        const bulkDeleteBtn = $('#bulkDeleteBtn');
        const bulkUpdateTypeBtn = $('#bulkUpdateTypeBtn');
        const bulkUpdateParentBtn = $('#bulkUpdateParentBtn');
        const bulkActivateBtn = $('#bulkActivateBtn');
        const bulkDeactivateBtn = $('#bulkDeactivateBtn');

        /* ---------- toast ---------- */
        function toast(msg, error=false){
            const t = document.createElement('div');
            t.className = 'fixed bottom-6 right-6 z-[100] px-4 py-3 rounded-md shadow-lg text-sm text-white ' + (error ? 'bg-red-600':'bg-green-600');
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(()=>t.remove(), 3000);
        }

        /* ---------- selection helpers ---------- */
        let lastChecked = null;

        function updateSelectedState(){
            const selected = cbxRows.filter(c=>c.checked).length;
            selCount.textContent = selected;
            bulkActions.classList.toggle('hidden', selected === 0);
            checkAll.checked = selected === cbxRows.length && selected > 0;
            checkAll.indeterminate = selected > 0 && selected < cbxRows.length;
        }

        checkAll?.addEventListener('change', e=>{
            cbxRows.forEach(c=> c.checked = e.target.checked);
            updateSelectedState();
        });

        cbxRows.forEach(cb=>{
            cb.addEventListener('click', (e)=>{
                // Shift-range select
                if (e.shiftKey && lastChecked){
                    const start = cbxRows.indexOf(cb);
                    const end   = cbxRows.indexOf(lastChecked);
                    const [a,b] = start < end ? [start, end] : [end, start];
                    for (let i=a; i<=b; i++) cbxRows[i].checked = lastChecked.checked;
                }
                lastChecked = cb;
                updateSelectedState();
            });
        });

        /* ---------- bulk delete ---------- */
        function selectedIds(){
            return cbxRows.filter(c=>c.checked).map(c=>c.value);
        }

        function submitBulkDelete(){
            const ids = selectedIds();
            if(!ids.length) return;
            if(!confirm('Удалить выбранные категории?')) return;

            fetch(bulkDeleteUrl, {
                method:'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ category_ids: ids.map(id => parseInt(id)) })
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    toast('Удалено: ' + data.deleted);
                    if(data.warning) toast(data.warning, true);
                    setTimeout(()=>location.reload(), 1000);
                } else {
                    toast(data.error || 'Ошибка удаления', true);
                }
            }).catch(()=> toast('Ошибка сети', true));
        }

        bulkDeleteBtn?.addEventListener('click', submitBulkDelete);

        /* ---------- bulk update type ---------- */
        bulkUpdateTypeBtn?.addEventListener('click', ()=>{
            const ids = selectedIds();
            const type = $('#bulkTypeInput').value;
            if(!ids.length) return;

            fetch(bulkUpdateTypeUrl, {
                method:'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ category_ids: ids.map(id => parseInt(id)), type: type })
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    toast('Обновлено: ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                } else {
                    toast(data.error || 'Ошибка', true);
                }
            }).catch(()=> toast('Ошибка сети', true));
        });

        /* ---------- bulk update parent ---------- */
        bulkUpdateParentBtn?.addEventListener('click', ()=>{
            const ids = selectedIds();
            const parentId = $('#bulkParentSelect').value;
            if(!ids.length) return;

            fetch(bulkUpdateParentUrl, {
                method:'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ category_ids: ids.map(id => parseInt(id)), parent_id: parentId ? parseInt(parentId) : null })
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    toast('Обновлено: ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                } else {
                    toast(data.error || 'Ошибка', true);
                }
            }).catch(()=> toast('Ошибка сети', true));
        });

        /* ---------- bulk activate/deactivate ---------- */
        bulkActivateBtn?.addEventListener('click', ()=>{
            const ids = selectedIds();
            if(!ids.length) return;

            fetch(bulkUpdateActiveUrl, {
                method:'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ category_ids: ids.map(id => parseInt(id)), is_active: true })
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    toast('Активировано: ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                }
            }).catch(()=> toast('Ошибка сети', true));
        });

        bulkDeactivateBtn?.addEventListener('click', ()=>{
            const ids = selectedIds();
            if(!ids.length) return;

            fetch(bulkUpdateActiveUrl, {
                method:'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ category_ids: ids.map(id => parseInt(id)), is_active: false })
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    toast('Деактивировано: ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                }
            }).catch(()=> toast('Ошибка сети', true));
        });

        // hotkeys
        document.addEventListener('keydown', (e)=>{
            // Ctrl+F -> focus search
            if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='f'){
                e.preventDefault();
                $('input[name="search"]')?.focus();
            }
            // Del -> bulk delete
            if(e.key === 'Delete' && !bulkDeleteBtn.disabled && selectedIds().length > 0){
                e.preventDefault();
                submitBulkDelete();
            }
        });

        // init
        updateSelectedState();
    </script>
@endsection
