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
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="files-grid">
        @foreach($files as $file)
            <div class="admin-card p-3 hover:shadow-lg transition cursor-pointer file-item"
                 data-file-id="{{ $file->id }}"
                 onclick="openFileModal({{ $file->id }})">
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

    {{-- Пагинация --}}
    <div class="mt-6">
        {{ $files->links('vendor.pagination.tailwind') }}
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

@push('scripts')
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

