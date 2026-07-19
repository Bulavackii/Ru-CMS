{{-- resources/views/admin/files/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    @php
        use Illuminate\Support\Str;

        // Если категории в App\Models\Category — поменяй класс ниже
        // $CategoryModel = \App\Models\Category::class;
        $CategoryModel = \Modules\Categories\Models\Category::class;

        $q = trim((string) request('q', ''));
        $perPage = (int) request()->integer('per_page', 25);
        $catId = request('category');

        $categories = $CategoryModel
            ::query()
            ->when(
                \Schema::hasColumn((new $CategoryModel())->getTable(), 'type'),
                fn($q) => $q->where(fn($w) => $w->whereNull('type')->orWhere('type', 'file'))
            )
            ->orderBy('title')
            ->get();

        $totalFiles = method_exists($files, 'total') ? $files->total() : $files->count();
        $byCat = collect($files->items() ?? $files)->groupBy(fn($f) => $f->category_id)->map->count();
        $currentCat = $categories->firstWhere('id', $catId);
    @endphp

    {{-- ====== Шапка ====== --}}
    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between mb-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📁 Файлы</h1>
                @if ($currentCat)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800">
                        {{ $currentCat->icon }} {{ $currentCat->title }}
                    </span>
                @endif
            </div>
            <div class="text-sm text-gray-500">
                Всего: {{ number_format($totalFiles, 0, ',', ' ') }}
                @if ($q !== '')
                    • Поиск: <code>{{ $q }}</code>
                @endif
            </div>
            <div class="text-[11px] text-gray-400">
                Подсказки: <kbd>Shift</kbd> — диапазон, <kbd>Ctrl</kbd>+<kbd>K</kbd> — поиск, <kbd>Del</kbd> — удалить выбранные.
            </div>
        </div>

        {{-- Действия --}}
        <div class="flex flex-wrap items-center gap-1.5 md:gap-2">
            <button type="button" onclick="uiUpload.open()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-black text-white hover:bg-gray-800 shadow text-sm">
                <i class="fa-solid fa-upload"></i><span class="hidden sm:inline">Загрузить</span>
            </button>

            <button type="button" onclick="uiBulk.deleteSelected()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-red-600 text-white hover:bg-red-700 shadow text-sm">
                <i class="fa-solid fa-trash"></i><span class="hidden sm:inline">Удалить</span>
            </button>

            <button type="button" onclick="document.getElementById('create-category-form').classList.toggle('hidden')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-black text-sm shadow bg-orange-400 hover:bg-orange-500 ring-1 ring-orange-300">
                <i class="fa-solid fa-folder-plus"></i> Категория
            </button>
        </div>
    </div>

    {{-- ====== Чипы категорий ====== --}}
    <div class="flex flex-wrap items-center gap-1.5 mb-3">
        <span class="text-xs text-gray-600 dark:text-gray-300">Категории:</span>

        <a href="{{ route('admin.files.index', array_filter(['q' => $q, 'per_page' => $perPage])) }}"
            class="px-2.5 py-1 rounded-full text-xs font-medium border border-gray-200 dark:border-gray-700 shadow-sm
            {{ $catId ? 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' : 'bg-black text-white border-transparent' }}">
            Все
            <span class="ml-1 text-[10px] px-1.5 rounded {{ $catId ? 'bg-gray-100 dark:bg-gray-800' : 'bg-white/20 border border-white/30' }}">
                {{ $totalFiles }}
            </span>
        </a>

        @foreach ($categories as $cat)
            @php $active = (string) $catId === (string) $cat->id; @endphp
            <a href="{{ route('admin.files.index', array_filter(['category' => $cat->id, 'q' => $q, 'per_page' => $perPage])) }}"
                data-cat="{{ $cat->id }}"
                class="cat-chip px-2.5 py-1 rounded-full text-xs font-medium border border-gray-200 dark:border-gray-700 shadow-sm
                {{ $active ? 'bg-black text-white border-transparent' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' }}">
                {{ $cat->icon }} {{ $cat->title }}
                <span class="ml-1 text-[10px] px-1.5 rounded {{ $active ? 'bg-white/20 border border-white/30' : 'bg-gray-100 dark:bg-gray-800' }}">
                    {{ $byCat[$cat->id] ?? 0 }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- ====== Фильтр ====== --}}
    <form method="get" action="{{ route('admin.files.index') }}"
        class="bg-white/80 dark:bg-gray-900/80 backdrop-blur rounded-md border border-gray-200 dark:border-gray-800 p-2.5
             flex flex-col gap-2 md:flex-row md:items-center md:gap-2.5 mb-3">
        <select name="category" id="category-select"
            class="h-9 w-[240px] max-w-full border border-gray-300 dark:border-gray-700 rounded px-2.5 dark:bg-gray-900 dark:text-gray-100">
            <option value="">Все категории</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string) $catId === (string) $cat->id ? 'selected' : '' }}>
                    {{ $cat->icon }} {{ $cat->title }}
                </option>
            @endforeach
        </select>

        <input type="text" name="q" value="{{ $q }}" placeholder="Искать по названию…"
            class="h-9 md:flex-1 border border-gray-300 dark:border-gray-700 rounded px-2.5 dark:bg-gray-900 dark:text-gray-100" />

        <select name="per_page"
            class="h-9 w-[120px] border border-gray-300 dark:border-gray-700 rounded px-2.5 dark:bg-gray-900 dark:text-gray-100">
            @foreach ([10, 25, 50, 100] as $opt)
                <option value="{{ $opt }}" {{ (int) $perPage === $opt ? 'selected' : '' }}>{{ $opt }}/стр</option>
            @endforeach
        </select>

        <button class="h-9 px-3 rounded bg-blue-600 hover:bg-blue-700 text-white whitespace-nowrap">Искать</button>

        @if ($catId || $q !== '')
            <a href="{{ route('admin.files.index') }}"
               class="h-9 grid place-items-center px-3 rounded bg-slate-200 hover:bg-slate-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-100 whitespace-nowrap">
                Сброс
            </a>
        @endif
    </form>

    {{-- ====== Drop-zone ====== --}}
    @php $hasFiles = $totalFiles > 0; @endphp
    <div id="dropzone"
        class="group relative mb-4 rounded-md border-2 border-dashed transition
            {{ $hasFiles ? 'p-3 text-[12px]' : 'p-4 text-sm' }}
            border-gray-300 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-400
            bg-gray-50/70 dark:bg-gray-800/60"
        title="Перетащите файлы сюда или кликните для выбора" onclick="uiUpload.open()">
        <div class="flex items-center gap-3 pointer-events-none">
            <div class="w-10 h-10 grid place-items-center rounded bg-white dark:bg-gray-900 ring-1 ring-gray-100 dark:ring-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-400 group-hover:text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7H3l9-9 9 9h-2Zm-7-7.17L6.17 10H8v7h8v-7h1.83L12 4.83Z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="font-medium text-gray-800 dark:text-gray-200">
                    Перетащите файлы сюда <span class="text-gray-400">или нажмите, чтобы выбрать</span>
                </div>
                <div class="text-[12px] text-gray-500">
                    Сначала выберите категорию сверху. Поддерживается мультизагрузка. Прогресс увидите ниже.
                </div>
            </div>
            <button type="button"
                class="pointer-events-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-black text-white text-xs hover:bg-gray-800">
                <i class="fa-solid fa-folder-open"></i> Обзор…
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-3 p-2.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- ====== Таблица ====== --}}
    <div class="overflow-x-auto border rounded-lg shadow-sm dark:border-gray-700">
        <table class="min-w-full table-auto bg-white dark:bg-gray-900 text-[13px]">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase">
                <tr class="text-left">
                    <th class="px-3 py-2.5 w-10 text-center"><input type="checkbox" id="check-all" title="Выбрать всё"></th>
                    <th class="px-3 py-2.5">Файл</th>
                    <th class="px-3 py-2.5">Категория</th>
                    <th class="px-3 py-2.5 text-center">Размер</th>
                    <th class="px-3 py-2.5">URL</th>
                    <th class="px-3 py-2.5 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($files as $file)
                    @php
                        $url = asset('storage/' . ltrim($file->path, '/'));
                        $isImage = str_starts_with((string) ($file->mime_type ?? $file->mime ?? ''), 'image/');
                    @endphp
                    <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-3 py-2.5 text-center align-middle">
                            <input type="checkbox" class="row-checkbox" value="{{ $file->id }}">
                        </td>

                        <td class="px-3 py-2.5">
                            <div class="flex items-start gap-2.5">
                                <div class="w-12 h-12 rounded bg-gray-100 dark:bg-gray-800 overflow-hidden grid place-items-center shrink-0">
                                    @if ($isImage)
                                        <img src="{{ $url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <i class="fa-regular fa-file text-gray-400"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100 break-all">
                                        {{ Str::limit($file->name, 120) }}
                                    </div>
                                    <div class="mt-0.5 text-[12px] text-gray-500 space-x-2">
                                        @if ($file->mime_type ?? $file->mime)
                                            <span>{{ $file->mime_type ?? $file->mime }}</span>
                                        @endif
                                        @if ($file->created_at)
                                            <span>· {{ $file->created_at->format('d.m.Y H:i') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-3 py-2.5 align-middle">{{ $file->category->title ?? '—' }}</td>

                        <td class="px-3 py-2.5 text-center align-middle whitespace-nowrap">
                            {{ number_format(($file->size ?? 0) / 1024, 2, ',', ' ') }} KB
                        </td>

                        <td class="px-3 py-2.5 align-middle">
                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-[12px] rounded px-2 py-1 w-fit max-w-[520px] overflow-hidden">
                                <span class="truncate">{{ $url }}</span>
                                <button type="button" class="ml-1 text-gray-500 hover:text-black dark:hover:text-white shrink-0"
                                    title="Скопировать URL" onclick="copyToClipboard(@js($url))">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                                <button type="button" class="ml-1 text-gray-500 hover:text-black dark:hover:text-white shrink-0"
                                    title="Скопировать Markdown" onclick="copyToClipboard(@js('![' . $file->name . '](' . $url . ')'))">
                                    MD
                                </button>
                            </div>
                        </td>

                        <td class="px-3 py-2.5 text-right align-middle">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.files.download', $file->id) }}" class="text-blue-600 hover:text-blue-800">Скачать</a>
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                   class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Открыть</a>
                                <form action="{{ route('admin.files.bulkDelete') }}" method="post" class="inline"
                                      onsubmit="return confirm('Удалить файл «{{ $file->name }}»?');">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="file_ids" value="{{ $file->id }}">
                                    <button class="text-red-600 hover:text-red-700">Удалить</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500 text-sm">
                            Файлов пока нет. Выберите категорию и перетащите файлы выше.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ====== Пагинация ====== --}}
    <div class="mt-4 text-sm">
        {{ $files->appends(['q' => $q, 'per_page' => $perPage, 'category' => $catId])->links('vendor.pagination.tailwind') }}
    </div>

    {{-- скрытые формы --}}
    <form id="upload-form" action="{{ route('admin.files.upload') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="hidden" name="category_id" id="upload-category-id" value="{{ $catId }}">
        <input type="file" name="file" id="upload-file" onchange="document.getElementById('upload-form').submit()">
    </form>
    <form id="bulk-delete-form" method="POST" action="{{ route('admin.files.bulkDelete') }}" class="hidden">
        @csrf @method('DELETE')
        <input type="hidden" name="file_ids" id="bulk-delete-ids">
    </form>

    {{-- Быстрое создание категории --}}
    <form id="create-category-form" action="{{ route('admin.categories.store') }}" method="POST"
          class="hidden mt-5 p-4 rounded-md shadow bg-gray-50 dark:bg-gray-800">
        @csrf
        <input type="hidden" name="type" value="file">
        <input type="hidden" name="redirect_back_to_files" value="1">
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium">📝 Название</label>
                <input type="text" name="title" required
                       class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md dark:bg-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium">🔤 Иконка (эмоджи)</label>
                <input type="text" name="icon" placeholder="📁"
                       class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md dark:bg-gray-900 dark:text-white">
            </div>
            <div class="flex items-end">
                <button class="w-full md:w-auto px-3.5 py-2 rounded-md text-white text-sm
                        bg-gradient-to-r from-fuchsia-600 to-violet-600 hover:from-fuchsia-700 hover:to-violet-700">
                    ➕ Создать
                </button>
            </div>
        </div>
    </form>

    {{-- Тост --}}
    <div id="toast" class="fixed bottom-4 left-1/2 -translate-x-1/2 hidden px-3 py-1.5 rounded bg-black text-white text-sm shadow-lg">
        Скопировано
    </div>

    {{-- ====== JS ====== --}}
    <script>
        const $ = (s, r = document) => r.querySelector(s);
        const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

        (function syncChips() {
            const select = $('#category-select');
            $$('.cat-chip').forEach(a => a.addEventListener('click', () => {
                if (select) select.value = a.dataset.cat;
            }));
        })();

        $('#check-all')?.addEventListener('change', e => $$('.row-checkbox').forEach(cb => cb.checked = e.target.checked));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Delete') uiBulk.deleteSelected();
            const meta = e.ctrlKey || e.metaKey;
            if (meta && e.key.toLowerCase() === 'k') {
                const input = document.querySelector('input[name="q"]');
                if (input) {
                    e.preventDefault();
                    input.focus();
                    input.select();
                }
            }
        });

        function toast(text) {
            const t = $('#toast');
            t.textContent = text || 'Готово';
            t.classList.remove('hidden');
            clearTimeout(window.__t);
            window.__t = setTimeout(() => t.classList.add('hidden'), 1100);
        }

        function copyToClipboard(text) {
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(text).then(() => toast('Скопировано'));
                return;
            }
            const ta = document.createElement('textarea');
            ta.value = text; ta.style.position='fixed'; ta.style.opacity='0';
            document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); document.body.removeChild(ta);
            toast('Скопировано');
        }

        const uiBulk = {
            deleteSelected() {
                const ids = $$('.row-checkbox:checked').map(cb => cb.value);
                if (!ids.length) return alert('Нет выбранных файлов.');
                if (!confirm('Удалить выбранные файлы?')) return;
                $('#bulk-delete-ids').value = ids.join(',');
                $('#bulk-delete-form').submit();
            }
        };

        const uiUpload = (() => {
            const formUrl = @json(route('admin.files.upload'));
            const token = @json(csrf_token());
            const curCat = () => $('#category-select')?.value || '';

            function open() {
                const cat = curCat();
                if (!cat) return alert('Выберите категорию перед загрузкой.');
                const input = Object.assign(document.createElement('input'), { type:'file', multiple:true });
                input.onchange = () => handleFiles(input.files, cat);
                input.click();
            }

            async function handleFiles(list, categoryId) {
                if (!list?.length) return;
                for (const file of list) await uploadOne(file, categoryId);
                location.reload();
            }

            function progressRow(name) {
                const short = name.length > 48 ? name.slice(0,20)+'…'+name.slice(-20) : name;
                const row = document.createElement('div');
                row.className = 'mb-2 p-2 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900';
                row.innerHTML = `
                    <div class="text-xs text-gray-600 dark:text-gray-300 mb-1">${short}</div>
                    <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                      <div class="h-2 bg-blue-600" style="width:0%"></div>
                    </div>`;
                return row;
            }

            async function uploadOne(file, categoryId) {
                const dz = $('#dropzone');
                const row = progressRow(file.name);
                dz.appendChild(row);
                const bar = row.querySelector('div > div');

                await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', formUrl);
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                    xhr.upload.addEventListener('progress', e => {
                        if (e.lengthComputable) bar.style.width = Math.round(e.loaded / e.total * 100) + '%';
                    });
                    xhr.onreadystatechange = () => {
                        if (xhr.readyState === 4) {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                bar.style.width = '100%';
                                bar.classList.add('bg-emerald-600');
                                resolve();
                            } else {
                                bar.classList.replace('bg-blue-600', 'bg-red-600');
                                reject();
                            }
                        }
                    };
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('category_id', categoryId);
                    xhr.send(fd);
                }).catch(() => {});
            }

            (function setupDrop() {
                const dz = $('#dropzone');
                if (!dz) return;

                ['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault();
                    dz.classList.add('ring-2','ring-blue-500','border-blue-500');
                }, false));

                ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault();
                    dz.classList.remove('ring-2','ring-blue-500','border-blue-500');
                }, false));

                dz.addEventListener('drop', e => {
                    const cat = curCat();
                    if (!cat) return alert('Выберите категорию перед загрузкой.');
                    const files = e.dataTransfer?.files;
                    if (!files?.length) return;
                    handleFiles(files, cat);
                }, false);
            })();

            return { open };
        })();
    </script>
@endsection
