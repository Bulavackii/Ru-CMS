@extends('layouts.admin')

@section('title', __('admin.sections.categories'))

@section('content')
    {{-- ── Шапка страницы: акцентная полоса + бейдж-иконка + действие ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-tags"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.sections.categories') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.categories.subtitle') }}
                </p>
            </div>
        </div>

        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0"
           title="{{ __('admin.categories.create_tip') }}">
            <i class="fas fa-plus"></i> {{ __('admin.categories.create') }}
        </a>
    </div>

    {{-- ── Фильтры ── --}}
    <form method="GET" action="{{ route('admin.categories.index') }}" class="admin-card p-5 mb-5">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-indigo-500"></i> {{ __('admin.categories.filters') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Поиск --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.categories.search') }}</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                         width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                           placeholder="{{ __('admin.categories.search_ph') }}">
                </div>
            </div>

            {{-- Тип --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.categories.type') }}</label>
                <select name="type" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">{{ __('admin.categories.all_types') }}</option>
                    @foreach($types ?? [] as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Родитель --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.categories.parent') }}</label>
                <select name="parent_id" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">{{ __('admin.categories.all_parents') }}</option>
                    <option value="null" {{ request('parent_id') === 'null' ? 'selected' : '' }}>{{ __('admin.categories.roots') }}</option>
                    @foreach($parentCategories ?? [] as $parent)
                        <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->title }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Активность --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.categories.activity') }}</label>
                <select name="is_active" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">{{ __('admin.categories.all') }}</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>{{ __('admin.categories.only_active') }}</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>{{ __('admin.categories.only_inactive') }}</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-4">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-magnifying-glass"></i> {{ __('admin.categories.apply') }}
            </button>
            <a href="{{ route('admin.categories.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                      text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <i class="fas fa-xmark"></i> {{ __('admin.categories.reset') }}
            </a>
        </div>
    </form>

    {{-- ── Массовые действия (панель появляется при выделении) ── --}}
    <div id="bulkActions" class="admin-card p-4 mb-5 hidden" style="border-left:3px solid #6366f1">
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold
                         bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                <i class="fas fa-check-double"></i> {{ __('admin.categories.selected') }} <span id="selCount">0</span>
            </span>

            {{-- Массовое изменение типа --}}
            <div class="flex items-center gap-2">
                <input type="text" id="bulkTypeInput" placeholder="{{ __('admin.categories.bulk_type_ph') }}"
                       class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-2.5 py-1.5 text-sm w-32
                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <button id="bulkUpdateTypeBtn"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-pen"></i> {{ __('admin.categories.bulk_type') }}
                </button>
            </div>

            {{-- Массовое изменение родителя --}}
            <div class="flex items-center gap-2">
                <select id="bulkParentSelect"
                        class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-2.5 py-1.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">{{ __('admin.categories.bulk_no_parent') }}</option>
                    @foreach($parentCategories ?? [] as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                    @endforeach
                </select>
                <button id="bulkUpdateParentBtn"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-sitemap"></i> {{ __('admin.categories.bulk_parent') }}
                </button>
            </div>

            <div class="flex items-center gap-2 ml-auto">
                <button id="bulkActivateBtn"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-circle-check"></i> {{ __('admin.categories.bulk_activate') }}
                </button>
                <button id="bulkDeactivateBtn"
                        class="inline-flex items-center gap-2 bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-ban"></i> {{ __('admin.categories.bulk_deactivate') }}
                </button>
                <button id="bulkDeleteBtn"
                        class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-trash-can"></i> {{ __('admin.categories.bulk_delete') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ── Подсказка ── --}}
    <div class="admin-note px-4 py-3 mb-5 text-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="only-keyboard flex items-center gap-2 font-medium">
                <i class="fas fa-lightbulb"></i>
                <span>{{ __('admin.categories.hint_keys') }} <kbd class="px-1.5 py-0.5 border border-indigo-300 bg-white dark:bg-gray-800">Ctrl</kbd> +
                      <kbd class="px-1.5 py-0.5 border border-indigo-300 bg-white dark:bg-gray-800">F</kbd> {{ __('admin.categories.hint_keys_2') }}</span>
            </div>
            <div class="flex items-center gap-2 text-xs shrink-0">
                <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
                    {{ __('admin.categories.shown', ['shown' => $categories->count(), 'total' => $categories->total()]) }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Таблица ── --}}
    <div class="admin-card overflow-hidden">
     <div class="overflow-x-auto">
        <table id="categoriesTable" class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 w-10 text-left">
                    <input id="checkAll" type="checkbox" class="h-4 w-4" title="{{ __('admin.categories.check_all') }}">
                </th>
                <th class="px-4 py-3 text-left font-semibold">{{ __('admin.categories.th_name') }}</th>
                <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">{{ __('admin.categories.type') }}</th>
                <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">{{ __('admin.categories.parent') }}</th>
                <th class="px-4 py-3 text-center font-semibold hidden md:table-cell">{{ __('admin.categories.th_usage') }}</th>
                <th class="px-4 py-3 text-center font-semibold hidden md:table-cell">{{ __('admin.categories.th_status') }}</th>
                <th class="px-4 py-3 text-center font-semibold w-16">{{ __('admin.categories.th_actions') }}</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($categories as $category)
                <tr class="hover:bg-indigo-50/60 dark:hover:bg-gray-800 transition" data-id="{{ $category->id }}">
                    {{-- checkbox --}}
                    <td class="px-4 py-3 align-top">
                        <input type="checkbox" value="{{ $category->id }}" class="rowCbx h-4 w-4">
                    </td>

                    {{-- title + icon --}}
                    <td class="px-4 py-3 align-top">
                        <div class="flex items-start gap-2.5">
                            <span class="text-indigo-500 mt-0.5 shrink-0">
                                @if ($category->icon)
                                    {!! $category->icon !!}
                                @else
                                    <i class="fas fa-tag"></i>
                                @endif
                            </span>

                            <div class="min-w-0">
                                <a href="{{ route('admin.categories.edit', $category->id) }}"
                                   class="titleCell font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition break-words">
                                    {{ $category->title }}
                                </a>
                                <div class="mt-1 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                    <span class="font-mono">ID {{ $category->id }}</span>
                                    @if($category->slug)
                                        <span class="font-mono">/{{ $category->slug }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- type --}}
                    <td class="px-4 py-3 align-top hidden lg:table-cell">
                        @if($category->type)
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium
                                         bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $category->type }}
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </td>

                    {{-- parent --}}
                    <td class="px-4 py-3 align-top hidden lg:table-cell">
                        @if($category->parent)
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                                <i class="fas fa-turn-up fa-rotate-90 text-gray-400"></i> {{ $category->parent->title }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.categories.root') }}</span>
                        @endif
                    </td>

                    {{-- usage counts --}}
                    <td class="px-4 py-3 align-top text-center hidden md:table-cell">
                        <div class="flex items-center justify-center gap-1.5 text-xs">
                            @if($category->news_count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium
                                             bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300" title="{{ __('admin.categories.news_count') }}">
                                    <i class="fas fa-newspaper"></i> {{ $category->news_count }}
                                </span>
                            @endif
                            @if($category->pages_count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium
                                             bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" title="{{ __('admin.categories.pages_count') }}">
                                    <i class="fas fa-file-lines"></i> {{ $category->pages_count }}
                                </span>
                            @endif
                            @if($category->children_count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium
                                             bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200" title="{{ __('admin.categories.children_count') }}">
                                    <i class="fas fa-folder-tree"></i> {{ $category->children_count }}
                                </span>
                            @endif
                            @if($category->news_count == 0 && $category->pages_count == 0 && $category->children_count == 0)
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </div>
                    </td>

                    {{-- status --}}
                    <td class="px-4 py-3 align-top text-center hidden md:table-cell">
                        @if($category->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                         bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                <i class="fas fa-circle-check"></i> {{ __('admin.categories.active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                         bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                                <i class="fas fa-ban"></i> {{ __('admin.categories.inactive') }}
                            </span>
                        @endif
                    </td>

                    {{-- actions --}}
                    <td class="px-4 py-3 align-top text-center">
                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                           class="inline-flex items-center justify-center w-8 h-8 bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition"
                           title="{{ __('admin.categories.edit') }}">
                            <i class="fas fa-pen"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <span class="admin-icon-badge mx-auto mb-3"><i class="fas fa-tags"></i></span>
                        <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('admin.categories.empty') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('admin.categories.empty_hint') }}
                            <a href="{{ route('admin.categories.create') }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('admin.categories.empty_link') }}</a>.
                        </p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
     </div>
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
            if(!confirm(@js(__('admin.categories.js_confirm_delete')))) return;

            fetch(bulkDeleteUrl, {
                method:'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ category_ids: ids.map(id => parseInt(id)) })
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    toast(@js(__('admin.categories.js_deleted')) + ' ' + data.deleted);
                    if(data.warning) toast(data.warning, true);
                    setTimeout(()=>location.reload(), 1000);
                } else {
                    toast(data.error || @js(__('admin.categories.js_err_delete')), true);
                }
            }).catch(()=> toast(@js(__('admin.categories.js_err_net')), true));
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
                    toast(@js(__('admin.categories.js_updated')) + ' ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                } else {
                    toast(data.error || @js(__('admin.categories.js_err')), true);
                }
            }).catch(()=> toast(@js(__('admin.categories.js_err_net')), true));
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
                    toast(@js(__('admin.categories.js_updated')) + ' ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                } else {
                    toast(data.error || @js(__('admin.categories.js_err')), true);
                }
            }).catch(()=> toast(@js(__('admin.categories.js_err_net')), true));
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
                    toast(@js(__('admin.categories.js_activated')) + ' ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                }
            }).catch(()=> toast(@js(__('admin.categories.js_err_net')), true));
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
                    toast(@js(__('admin.categories.js_deactivated')) + ' ' + data.updated);
                    setTimeout(()=>location.reload(), 1000);
                }
            }).catch(()=> toast(@js(__('admin.categories.js_err_net')), true));
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
