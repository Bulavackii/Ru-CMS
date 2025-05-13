@extends('layouts.admin')

@section('title', 'Управление файлами')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📁 Управление файлами</h1>
    <div class="flex gap-2">
        <input type="text" id="searchInput"
               class="border border-gray-300 rounded-md p-2 text-sm"
               placeholder="Поиск по названию..." oninput="filterFiles()">
        <button onclick="triggerFileUpload()"
            class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm">
            <i class="fas fa-upload"></i> Загрузить
            
        </button>
        <button onclick="submitBulkDelete()"
            class="inline-flex items-center gap-2 bg-red-600 text-white hover:bg-red-700 px-4 py-2 rounded-md shadow text-sm">
            <i class="fas fa-trash"></i> Удалить
        </button>
        <button onclick="document.getElementById('create-category-form').classList.toggle('hidden');"
            class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-700 px-4 py-2 rounded-md shadow text-sm">
            <i class="fas fa-plus"></i> Категория
        </button>
    </div>
</div>

@php $categories = \App\Models\Category::all(); @endphp

<div class="flex flex-wrap items-center gap-2 mb-2 p-3 rounded bg-gray-100 dark:bg-gray-800">
    <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Категории:</span>
    <a href="{{ route('admin.files.index') }}"
       class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm {{ request('category') ? 'bg-white text-gray-700 hover:bg-gray-100' : 'bg-black text-white' }}">
        Все
    </a>
    @foreach ($categories as $category)
        <a href="{{ route('admin.files.index', ['category' => $category->id]) }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm {{ request('category') == $category->id ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            {{ $category->icon }} {{ $category->title }}
        </a>
    @endforeach
</div>

<div class="mb-6 text-sm text-gray-700 dark:text-gray-300">
    Вы выбрали: <span class="font-semibold">
        {{ request('category') ? ($categories->firstWhere('id', request('category'))?->icon . ' ' . $categories->firstWhere('id', request('category'))?->title) : 'Все категории' }}
    </span>
</div>

<table id="filesTable" class="min-w-full table-auto border border-gray-300 rounded shadow bg-white dark:bg-gray-900">
    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
        <tr>
            <th class="px-4 py-3"><input type="checkbox" id="check-all"></th>
            <th>Название</th>
            <th>Категория</th>
            <th>Размер</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
        @foreach ($files as $file)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <td class="px-4 py-3 text-center"><input type="checkbox" class="row-checkbox" value="{{ $file->id }}"></td>
                <td class="px-4 py-3 file-name">{{ $file->name }}</td>
                <td class="px-4 py-3">{{ $file->category->title ?? '—' }}</td>
                <td class="px-4 py-3">{{ number_format($file->size / 1024, 2) }} KB</td>
                <td class="px-4 py-3 text-left">
                    <div class="flex flex-col space-y-1">
                        <a href="{{ route('admin.files.download', $file->id) }}"
                           class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-download"></i> Скачать
                        </a>
                        <div class="flex items-center text-xs text-gray-500 bg-gray-100 rounded px-2 py-1">
                            <span class="truncate max-w-[200px]">{{ asset('storage/' . $file->path) }}</span>
                            <button onclick="copyLink('{{ asset('storage/' . $file->path) }}')"
                                    class="ml-2 text-gray-500 hover:text-black transition" title="Скопировать ссылку">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-6">
    {{ $files->withQueryString()->onEachSide(1)->links('vendor.pagination.tailwind') }}
</div>

<form id="upload-form" action="{{ route('admin.files.upload') }}" method="POST" enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="hidden" name="category_id" id="upload-category-id">
    <input type="file" name="file" id="upload-file" class="hidden" onchange="document.getElementById('upload-form').submit()">
</form>

<form id="create-category-form" action="{{ route('admin.categories.store') }}" method="post"
    class="hidden mt-6 p-4 rounded-md shadow-md bg-gray-100 dark:bg-gray-800">
    @csrf
    <input type="hidden" name="type" value="file">
    <input type="hidden" name="redirect_back_to_files" value="1">

    <div class="mb-4">
        <label for="category_title" class="block text-sm">Название категории</label>
        <input type="text" id="category_title" name="title" required class="mt-1 p-2 border rounded-md w-full">
    </div>

    <div class="mb-4">
        <label for="category_icon" class="block text-sm">Иконка категории</label>
        <input type="text" id="category_icon" name="icon" class="mt-1 p-2 border rounded-md w-full">
    </div>

    <button type="submit"
        class="bg-black text-white px-4 py-2 rounded-md shadow hover:bg-gray-800 text-sm font-semibold">
        Создать категорию
    </button>
</form>

<form id="bulk-delete-form" method="POST" action="{{ route('admin.files.bulkDelete') }}" class="hidden">
    @csrf
    @method('DELETE')
    <input type="hidden" name="file_ids" id="bulk-delete-ids">
</form>

<script>
    function triggerFileUpload() {
        const selected = '{{ request('category') }}';
        if (!selected) {
            alert('Сначала выберите категорию.');
            return;
        }
        document.getElementById('upload-category-id').value = selected;
        document.getElementById('upload-file').click();
    }

    function copyLink(link) {
        navigator.clipboard.writeText(link).then(() => {
            alert('Ссылка скопирована!');
        });
    }

    function submitBulkDelete() {
        const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
        if (!selected.length) return alert('Выберите файлы для удаления.');
        if (!confirm('Удалить выбранные файлы?')) return;
        document.getElementById('bulk-delete-ids').value = selected.join(',');
        document.getElementById('bulk-delete-form').submit();
    }

    function filterFiles() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#filesTable tbody tr');
        rows.forEach(row => {
            const name = row.querySelector('.file-name').textContent.toLowerCase();
            const match = name.includes(search);
            row.style.display = match ? '' : 'none';
        });
    }

    document.getElementById('check-all')?.addEventListener('change', e => {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked);
    });
</script>
@endsection
