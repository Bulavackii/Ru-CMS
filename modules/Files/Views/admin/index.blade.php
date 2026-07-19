@extends('layouts.admin')

@section('title', 'Медиа-библиотека')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📁 Медиа-библиотека</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Управление файлами и изображениями</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openUploadModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-upload mr-2"></i>Загрузить файлы
            </button>
        </div>
    </div>

    {{-- Фильтры --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">Поиск</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="w-full border rounded px-3 py-2" placeholder="Название файла...">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Тип</label>
                <select name="type" class="border rounded px-3 py-2">
                    <option value="">Все</option>
                    <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Изображения</option>
                    <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Видео</option>
                    <option value="document" {{ request('type') === 'document' ? 'selected' : '' }}>Документы</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Категория</label>
                <select name="category_id" class="border rounded px-3 py-2">
                    <option value="">Все</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                🔍 Поиск
            </button>
        </form>
    </div>

    {{-- Сетка файлов --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="files-grid">
        @foreach($files as $file)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 hover:shadow-lg transition cursor-pointer file-item" 
                 data-file-id="{{ $file->id }}"
                 onclick="openFileModal({{ $file->id }})">
                @if($file->isImage())
                    <img src="{{ Storage::url($file->path) }}" 
                         alt="{{ $file->alt_text ?? $file->original_name }}"
                         class="w-full h-32 object-cover rounded mb-2">
                @else
                    <div class="w-full h-32 bg-gray-200 dark:bg-gray-700 rounded mb-2 flex items-center justify-center">
                        <i class="fas fa-file text-4xl text-gray-400"></i>
                    </div>
                @endif
                <div class="text-xs font-medium truncate" title="{{ $file->original_name }}">
                    {{ $file->original_name }}
                </div>
                <div class="text-xs text-gray-500">{{ $file->human_size }}</div>
            </div>
        @endforeach
    </div>

    {{-- Пагинация --}}
    <div class="mt-6">
        {{ $files->links() }}
    </div>
</div>

{{-- Модальное окно загрузки --}}
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4">
        <h2 class="text-2xl font-bold mb-4">Загрузить файлы</h2>
        <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center" 
                 id="dropZone"
                 ondrop="handleDrop(event)" 
                 ondragover="handleDragOver(event)" 
                 ondragleave="handleDragLeave(event)">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400">Перетащите файлы сюда или</p>
                <label class="inline-block mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg cursor-pointer hover:bg-blue-700">
                    Выбрать файлы
                    <input type="file" name="files[]" multiple class="hidden" onchange="handleFileSelect(event)">
                </label>
            </div>
            <div id="fileList" class="space-y-2"></div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Загрузить
                </button>
                <button type="button" onclick="closeUploadModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg">
                    Отмена
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Модальное окно просмотра файла --}}
<div id="fileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
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

