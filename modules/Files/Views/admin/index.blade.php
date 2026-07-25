@extends('layouts.admin')

@section('title', 'Медиа-библиотека')

@section('content')
{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-folder-open"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Медиа-библиотека</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Изображения, видео и документы сайта. Загрузка перетаскиванием, обрезка и alt-тексты.
            </p>
        </div>
    </div>

    <button onclick="openUploadModal()"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0">
        <i class="fas fa-upload"></i> Загрузить файлы
    </button>
</div>

{{-- ── Фильтры ── --}}
<form method="GET" class="admin-card p-5 mb-5">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
        <i class="fas fa-filter text-indigo-500"></i> Фильтры
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Поиск</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                     width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    placeholder="Название файла…">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Тип</label>
            <select name="type" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Все</option>
                <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Изображения</option>
                <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Видео</option>
                <option value="document" {{ request('type') === 'document' ? 'selected' : '' }}>Документы</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Категория</label>
            <select name="category_id" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">Все</option>
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
            <i class="fas fa-magnifying-glass"></i> Найти
        </button>
        @if(request('search') || request('type') || request('category_id'))
            <a href="{{ route('admin.files.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                      text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <i class="fas fa-xmark"></i> Сбросить
            </a>
        @endif
    </div>
</form>

{{-- ── Подсказка ── --}}
<div class="admin-hint px-4 py-3 mb-5 text-sm">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-2 font-medium">
            <i class="fas fa-lightbulb"></i>
            <span>Клик по файлу открывает карточку: alt-текст, описание, обрезка и ссылка для вставки.</span>
        </div>
        <div class="flex items-center gap-2 text-xs shrink-0">
            <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
                Всего: {{ $files->total() }}
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
            <p class="text-gray-600 dark:text-gray-300 font-medium">По вашему запросу ничего не найдено.</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Попробуйте изменить фильтры или
                <a href="{{ route('admin.files.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">сбросить их</a>.
            </p>
        @else
            <p class="text-gray-600 dark:text-gray-300 font-medium">В библиотеке пока нет файлов.</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Нажмите «Загрузить файлы» или перетащите их в окно загрузки.
            </p>
            <button onclick="openUploadModal()"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 mt-4 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-upload"></i> Загрузить файлы
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
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Загрузить файлы</h2>
        </div>
        <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 p-8 text-center transition"
                 id="dropZone"
                 ondrop="handleDrop(event)"
                 ondragover="handleDragOver(event)"
                 ondragleave="handleDragLeave(event)">
                <i class="fas fa-cloud-arrow-up text-4xl text-indigo-400 mb-3"></i>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Перетащите файлы сюда или</p>
                <label class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm cursor-pointer transition">
                    <i class="fas fa-folder-open"></i> Выбрать файлы
                    <input type="file" name="files[]" multiple class="hidden" onchange="handleFileSelect(event)">
                </label>
            </div>
            <div id="fileList" class="space-y-2"></div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-upload"></i> Загрузить
                </button>
                <button type="button" onclick="closeUploadModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Отмена
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
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка загрузки: ' + error.message);
    }
});

function openFileModal(fileId) {
    // Загрузка информации о файле через AJAX
    fetch(`/admin/files/${fileId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('fileModalContent').innerHTML = `
                <h2 class="text-2xl font-bold mb-4">${data.file.original_name}</h2>
                ${data.file.is_image ? `<img src="${data.file.url}" class="w-full rounded mb-4">` : ''}
                <div class="space-y-2">
                    <div><strong>Размер:</strong> ${data.file.human_size}</div>
                    <div><strong>Тип:</strong> ${data.file.mime_type}</div>
                    <div><strong>Загружен:</strong> ${data.file.created_at}</div>
                </div>
                <div class="flex gap-3 mt-4">
                    <a href="/admin/files/${fileId}/download" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                        Скачать
                    </a>
                    <button onclick="deleteFile(${fileId})" class="px-4 py-2 bg-red-600 text-white rounded-lg">
                        Удалить
                    </button>
                </div>
            `;
            document.getElementById('fileModal').classList.remove('hidden');
        });
}

function deleteFile(fileId) {
    if (!confirm('Удалить этот файл?')) return;
    
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
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    });
}
</script>
@endpush
@endsection

