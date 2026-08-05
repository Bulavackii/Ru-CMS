@extends('layouts.admin')

@section('title', __('admin.files.media_title'))

@section('content')
{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-folder-open"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.files.media_title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.files.media_hint') }}
            </p>
        </div>
    </div>

    <button onclick="openUploadModal()"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0">
        <i class="fas fa-upload"></i> {{ __('admin.files.upload_files') }}
    </button>
</div>

{{-- ── Фильтры ── --}}
<form method="GET" class="admin-card p-5 mb-5">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
        <i class="fas fa-filter text-indigo-500"></i> {{ __('admin.common.filters') }}
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.header.search') }}</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                     width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    placeholder="{{ __('admin.files.name_ph') }}">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.files.type') }}</label>
            <select name="type" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">{{ __('admin.common.all') }}</option>
                <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>{{ __('admin.files.images') }}</option>
                <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>{{ __('admin.files.videos') }}</option>
                <option value="document" {{ request('type') === 'document' ? 'selected' : '' }}>{{ __('admin.files.documents') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.common.category') }}</label>
            <select name="category_id" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">{{ __('admin.common.all') }}</option>
                @foreach($categories as $category)
                    {{-- У модели Categories поле называется title (не name) —
                         раньше здесь был $category->name и список был пустым. --}}
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->title }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex items-center gap-2 mt-4">
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-magnifying-glass"></i> {{ __('admin.files.find') }}
        </button>
        @if(request('search') || request('type') || request('category_id'))
            <a href="{{ route('admin.files.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                      text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <i class="fas fa-xmark"></i> {{ __('admin.users.reset') }}
            </a>
        @endif
    </div>
</form>

{{-- ── Подсказка ── --}}
<div class="admin-note px-4 py-3 mb-5 text-sm">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-2 font-medium">
            <i class="fas fa-lightbulb"></i>
            <span>{{ __('admin.files.note') }}</span>
        </div>
        <div class="flex items-center gap-2 text-xs shrink-0">
            <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
                {{ __('admin.common.total') }} {{ $files->total() }}
            </span>
        </div>
    </div>
</div>

{{-- ── Сетка файлов ── --}}
@if($files->count())
    {{-- Полоса выбора. Появляется, только когда что-то отмечено: постоянная
         панель с неактивными кнопками занимает место и ничего не сообщает. --}}
    <div id="bulk-bar" class="admin-card p-3 mb-4 hidden items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" id="bulk-all">
            {{ __('admin.files.select_all') }}
        </label>

        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin.files.selected') }} <b id="bulk-count">0</b>
        </span>

        <div class="ml-auto flex items-center gap-2">
            <button type="button" id="bulk-clear"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600
                           text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                {{ __('admin.files.clear_selection') }}
            </button>
            <button type="button" id="bulk-delete"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition">
                <i class="fas fa-trash"></i> {{ __('admin.common.delete') }}
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="files-grid">
        @foreach($files as $file)
            {{-- Плитка целиком открывает карточку, поэтому отметка и кнопки
                 гасят всплытие: раньше любой щелчок по ним заодно распахивал
                 модалку поверх только что нажатого действия. --}}
            <div class="admin-card p-3 hover:shadow-lg transition cursor-pointer file-item relative group"
                 data-file-id="{{ $file->id }}"
                 data-file-name="{{ $file->original_name }}"
                 onclick="openFileModal({{ $file->id }})">

                <label class="file-pick" onclick="event.stopPropagation()">
                    <input type="checkbox" class="file-check" value="{{ $file->id }}">
                </label>

                <div class="file-actions" onclick="event.stopPropagation()">
                    <a href="{{ route('admin.files.download', $file) }}"
                       class="file-action" title="{{ __('admin.files.download') }}">
                        <i class="fas fa-download"></i>
                    </a>
                    <button type="button" class="file-action file-action--danger"
                            title="{{ __('admin.common.delete') }}"
                            onclick="deleteOne({{ $file->id }}, this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                @if($file->isImage())
                    <img src="{{ Storage::url($file->path) }}"
                         alt="{{ $file->alt_text ?? $file->original_name }}"
                         class="w-full h-32 object-cover mb-2">
                @else
                    <div class="w-full h-32 bg-gray-100 dark:bg-gray-800 mb-2 flex items-center justify-center">
                        <i class="fas {{ str_contains((string) $file->mime_type, 'video') ? 'fa-file-video' : 'fa-file-lines' }} text-4xl text-indigo-300"></i>
                    </div>
                @endif
                <div class="text-xs font-semibold text-gray-900 dark:text-white truncate" title="{{ $file->original_name }}">
                    {{ $file->original_name }}
                </div>
                <div class="mt-0.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $file->human_size }}</span>
                    @if($file->width && $file->height)
                        <span class="font-mono">{{ $file->width }}×{{ $file->height }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Постраничный вывод.

         Сводка показывается всегда, а не только когда страниц несколько:
         при восемнадцати файлах и двадцати четырёх на странице ссылок нет
         вовсе, и понять, всё ли показано, было неоткуда. Рядом — выбор
         размера страницы: у кого-то библиотека на три файла, у кого-то на
         три тысячи. --}}
    <div class="mt-6 flex flex-col md:flex-row md:items-center gap-3">
        <form method="GET" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <span>{{ __('admin.files.per_page') }}</span>
            <select name="per_page" onchange="this.form.submit()"
                    class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-2 py-1 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                @foreach([24, 48, 96] as $size)
                    <option value="{{ $size }}" {{ (int) request('per_page', 24) === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>

            {{-- Свою подпись показываем ТОЛЬКО когда страница одна: у общего
                 компонента пагинации она уже есть, и на нескольких страницах
                 счётчик выводился бы дважды. --}}
            @unless($files->hasPages())
                <span class="text-gray-400">
                    {{ __('admin.files.showing', [
                        'from'  => $files->firstItem(),
                        'to'    => $files->lastItem(),
                        'total' => $files->total(),
                    ]) }}
                </span>
            @endunless
        </form>

        {{-- Тот же компонент, что в кабинете на «Истории входов», — один файл
             на все двадцать восемь списков проекта. --}}
        <div class="md:ml-auto flex-1">
            {{ $files->withQueryString()->links() }}
        </div>
    </div>
@else
    {{-- Пустое состояние: раньше его не было вовсе и страница выглядела «пустой» --}}
    <div class="admin-card p-12 text-center">
        <span class="admin-icon-badge mx-auto mb-3"><i class="fas fa-folder-open"></i></span>
        @if(request('search') || request('type') || request('category_id'))
            <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('admin.files.nothing_found') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ __('admin.files.try_filters') }}
                <a href="{{ route('admin.files.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('admin.files.reset_them') }}</a>.
            </p>
        @else
            <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('admin.files.library_empty') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ __('admin.files.empty_hint') }}
            </p>
            <button onclick="openUploadModal()"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 mt-4 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-upload"></i> {{ __('admin.files.upload_files') }}
            </button>
        @endif
    </div>
@endif

{{-- Модальное окно загрузки. Затемнение задаём инлайн: bg-opacity-* держится на
     CSS-переменной Tailwind и в этой статической сборке ненадёжно. --}}
<div id="uploadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.5)">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-6 max-w-2xl w-full mx-4 shadow-xl">
        <div class="flex items-center gap-3 mb-5">
            <span class="admin-icon-badge"><i class="fas fa-upload"></i></span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.files.upload_files') }}</h2>
        </div>
        <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 p-8 text-center transition"
                 id="dropZone"
                 ondrop="handleDrop(event)"
                 ondragover="handleDragOver(event)"
                 ondragleave="handleDragLeave(event)">
                <i class="fas fa-cloud-arrow-up text-4xl text-indigo-400 mb-3"></i>
                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ __('admin.files.drop_or') }}</p>
                <label class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm cursor-pointer transition">
                    <i class="fas fa-folder-open"></i> {{ __('admin.files.choose_files') }}
                    <input type="file" name="files[]" multiple class="hidden" onchange="handleFileSelect(event)">
                </label>
            </div>
            <div id="fileList" class="space-y-2"></div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-upload"></i> {{ __('admin.files.upload') }}
                </button>
                <button type="button" onclick="closeUploadModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    {{ __('admin.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Модальное окно просмотра файла --}}
<div id="fileModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.5)">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-6 max-w-4xl w-full mx-4 overflow-y-auto shadow-xl" style="max-height:90vh">
        <div id="fileModalContent"></div>
    </div>
</div>

@push('styles')
<style>
    /* Отметка и кнопки появляются при наведении, а у отмеченного файла
       отметка видна всегда — иначе непонятно, что именно выбрано. */
    .file-pick{ position:absolute; top:8px; left:8px; z-index:2; opacity:0; transition:opacity .15s ease }
    .file-item:hover .file-pick,
    .file-item.is-picked .file-pick{ opacity:1 }
    .file-pick input{ width:18px; height:18px; cursor:pointer }

    .file-actions{ position:absolute; top:8px; right:8px; z-index:2; display:flex; gap:4px;
                   opacity:0; transition:opacity .15s ease }
    .file-item:hover .file-actions{ opacity:1 }
    .file-action{ display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
                  font-size:11px; color:#374151; background:rgba(255,255,255,.94);
                  border:1px solid #e5e7eb; cursor:pointer }
    .file-action:hover{ color:#111827; background:#fff }
    .file-action--danger:hover{ color:#fff; background:#dc2626; border-color:#dc2626 }

    .file-item.is-picked{ outline:2px solid var(--admin-primary); outline-offset:-2px }

    #bulk-bar.is-open{ display:flex }
</style>
@endpush

@push('scripts')
<script>
    // ── Выбор файлов и массовое удаление ─────────────────────────────
    (function () {
        const bar = document.getElementById('bulk-bar');
        if (!bar) { return; }

        const countBox = document.getElementById('bulk-count');
        const checks = () => Array.from(document.querySelectorAll('.file-check'));
        const picked = () => checks().filter(c => c.checked);

        const sync = () => {
            const list = picked();

            bar.classList.toggle('is-open', list.length > 0);
            countBox.textContent = list.length;
            document.getElementById('bulk-all').checked = list.length === checks().length && list.length > 0;

            checks().forEach(c => c.closest('.file-item').classList.toggle('is-picked', c.checked));
        };

        document.addEventListener('change', e => {
            if (e.target.classList.contains('file-check')) { sync(); }
        });

        document.getElementById('bulk-all').addEventListener('change', e => {
            checks().forEach(c => { c.checked = e.target.checked; });
            sync();
        });

        document.getElementById('bulk-clear').addEventListener('click', () => {
            checks().forEach(c => { c.checked = false; });
            sync();
        });

        document.getElementById('bulk-delete').addEventListener('click', () => {
            const ids = picked().map(c => Number(c.value));
            if (!ids.length) { return; }

            if (!confirm(@js(__('admin.files.confirm_bulk')).replace(':count', ids.length))) { return; }

            fetch(@js(route('admin.files.bulkDelete')), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids: ids }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { throw new Error(data.message || 'error'); }
                // Перезагружаем страницу, а не убираем плитки на месте: после
                // удаления меняется и разбивка по страницам, и счётчики.
                window.location.reload();
            })
            .catch(() => alert(@js(__('admin.files.delete_failed'))));
        });

        // Одиночное удаление той же кнопкой на плитке.
        window.deleteOne = function (id, button) {
            if (!confirm(@js(__('admin.files.confirm_one')))) { return; }

            fetch(@js(route('admin.files.index')) + '/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { throw new Error(); }
                button.closest('.file-item').remove();
                sync();
            })
            .catch(() => alert(@js(__('admin.files.delete_failed'))));
        };

        sync();
    })();
</script>

<script>
let selectedFiles = [];

function openUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    selectedFiles = [];
    document.getElementById('fileList').innerHTML = '';
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('border-blue-500');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('border-blue-500');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('border-blue-500');
    const files = Array.from(e.dataTransfer.files);
    addFiles(files);
}

function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    addFiles(files);
}

function addFiles(files) {
    selectedFiles = [...selectedFiles, ...files];
    updateFileList();
}

function updateFileList() {
    const list = document.getElementById('fileList');
    list.innerHTML = selectedFiles.map((file, index) => `
        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
            <span class="text-sm">${file.name}</span>
            <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFileList();
}

document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    selectedFiles.forEach(file => {
        formData.append('files[]', file);
    });
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const response = await fetch('{{ route("admin.files.upload") }}', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(@js(__('admin.common.error')) + ' ' + (data.message || @js(__('admin.files.unknown_error'))));
        }
    } catch (error) {
        alert(@js(__('admin.files.upload_error')) + ' ' + error.message);
    }
});

/* Экранирование значений, приходящих с сервера, перед вставкой в innerHTML. */
function escFile(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function openFileModal(fileId) {
    // Загрузка информации о файле через AJAX
    fetch(`/admin/files/${fileId}`)
        .then(r => r.json())
        .then(data => {
            const f = data.file;
            // Абсолютный URL — его удобно вставлять в редактор или отдавать наружу
            const absUrl = new URL(f.url, window.location.origin).href;

            document.getElementById('fileModalContent').innerHTML = `
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="admin-icon-badge shrink-0"><i class="fas fa-file-image"></i></span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">${escFile(f.original_name)}</h2>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                ${escFile(f.human_size)} · ${escFile(f.mime_type)} · ${escFile(f.created_at)}
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeFileModal()" title="{{ __('admin.files.close_esc') }}" aria-label="{{ __('admin.common.close') }}"
                            class="shrink-0 inline-flex items-center justify-center w-9 h-9 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <i class="fas fa-xmark text-lg"></i>
                    </button>
                </div>

                ${f.is_image ? `<div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 mb-5 flex items-center justify-center">
                    <img src="${escFile(f.url)}" alt="${escFile(f.alt_text || f.original_name)}" class="max-w-full" style="max-height:52vh">
                </div>` : ''}

                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.files.file_link') }}</label>
                <div class="flex gap-2 mb-5">
                    <input id="fileUrlInput" type="text" readonly value="${escFile(absUrl)}"
                           onclick="this.select()"
                           class="flex-1 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm font-mono
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <button type="button" onclick="copyFileUrl()"
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition whitespace-nowrap">
                        <i class="fa-regular fa-copy"></i> {{ __('admin.files.copy') }}
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="/admin/files/${fileId}/download"
                       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                              text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <i class="fas fa-download"></i> {{ __('admin.files.download') }}
                    </a>
                    <a href="${escFile(f.url)}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                              text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <i class="fas fa-arrow-up-right-from-square"></i> {{ __('admin.files.open') }}
                    </a>
                    <button onclick="deleteFile(${fileId})"
                            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition ml-auto">
                        <i class="fa-regular fa-trash-can"></i> {{ __('admin.delete') }}
                    </button>
                </div>
            `;
            document.getElementById('fileModal').classList.remove('hidden');
        });
}

/* Закрытие карточки файла. Раньше её нельзя было закрыть вообще: не было ни
   крестика, ни обработчика Esc, ни клика по подложке. */
function closeFileModal() {
    document.getElementById('fileModal').classList.add('hidden');
}

/* Копирование ссылки: сначала Clipboard API, иначе — выделение поля. */
function copyFileUrl() {
    const input = document.getElementById('fileUrlInput');
    if (!input) return;

    const done = () => {
        const btn = document.querySelector('#fileModalContent button[onclick="copyFileUrl()"]');
        if (!btn) return;
        const html = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> ' + @js(__('admin.files.copied_short'));
        setTimeout(() => (btn.innerHTML = html), 1500);
    };

    if (navigator.clipboard) {
        navigator.clipboard.writeText(input.value).then(done).catch(() => { input.select(); });
    } else {
        input.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
    }
}

/* Esc закрывает любую открытую модалку; клик по подложке — только карточку файла. */
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    closeFileModal();
    if (typeof closeUploadModal === 'function') closeUploadModal();
});

document.getElementById('fileModal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeFileModal();
});

function deleteFile(fileId) {
    if (!confirm(@js(__('admin.files.delete_one')))) return;
    
    fetch(`/admin/files/${fileId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(@js(__('admin.common.error')) + ' ' + (data.message || @js(__('admin.files.unknown_error'))));
        }
    });
}
</script>
@endpush
@endsection

