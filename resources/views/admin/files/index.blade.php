@extends('layouts.admin')

@section('title', 'Управление файлами')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📁 Управление файлами</h1>
        <button onclick="triggerFileUpload()"
            class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-upload"></i> Загрузить файл
        </button>
        <button onclick="document.getElementById('create-category-form').classList.toggle('hidden');"
            class="inline-flex items-center gap-2 bg-green-500 text-white hover:bg-green-600 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-plus"></i> Создать категорию
        </button>
    </div>

    <style>
        .active-category {
            background-color: #000 !important;
            color: #fff !important;
            border-color: #000 !important;
        }
    </style>

    @php $categories = \App\Models\Category::all(); @endphp

    <div class="flex flex-wrap items-center gap-2 mb-2 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Категории:</span>

        <a href="#" onclick="selectCategory(null); event.preventDefault();"
            class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm bg-white text-gray-700 hover:bg-gray-100"
            id="cat-all">
            Все
        </a>

        @foreach ($categories as $category)
            <a href="#" onclick="selectCategory({{ $category->id }}); event.preventDefault();"
                class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm bg-white text-gray-700 hover:bg-gray-100"
                data-id="{{ $category->id }}" id="cat-{{ $category->id }}">
                {{ $category->icon }} {{ $category->title }}
            </a>
        @endforeach
    </div>

    <div class="mb-6 text-sm text-gray-700 dark:text-gray-300">
        Выбрана: <span id="selected-category-label" class="font-semibold">—</span>
    </div>

    <h2 class="mt-6">Загруженные файлы</h2>
    <table class="min-w-full mt-4 table-auto border border-gray-300 rounded-lg overflow-hidden shadow-md bg-white dark:bg-gray-900">
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
                    <td class="px-4 py-3 text-center"><input type="checkbox" name="selected[]" value="{{ $file->id }}" class="row-checkbox"></td>
                    <td class="px-4 py-3">{{ $file->name }}</td>
                    <td class="px-4 py-3">{{ $file->category->title ?? '—' }}</td>
                    <td class="px-4 py-3">{{ number_format($file->size / 1024, 2) }} KB</td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('admin.files.download', $file->id) }}" class="text-blue-500">Скачать</a> |
                        <a href="{{ asset('storage/' . $file->path) }}" target="_blank" class="text-blue-500">Ссылка</a>
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
        class="hidden mt-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-md shadow-md">
        @csrf
        <input type="hidden" name="type" value="file">
        <input type="hidden" name="redirect_back_to_files" value="1">

        <div class="mb-4">
            <label for="category_title" class="block">Название категории</label>
            <input type="text" id="category_title" name="title" required class="mt-2 p-2 border rounded-md w-full" placeholder="Введите название категории">
            @error('title')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="category_icon" class="block">Иконка категории (например, 🎶, 📷)</label>
            <input type="text" id="category_icon" name="icon" class="mt-2 p-2 border rounded-md w-full" placeholder="Введите иконку категории">
        </div>

        <button type="submit"
            class="inline-flex items-center gap-2 bg-blue-500 text-white hover:bg-blue-600 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            Создать категорию
        </button>
    </form>

    <script>
        let selectedCategory = null;

        function selectCategory(categoryId) {
            selectedCategory = categoryId;
            document.getElementById('upload-category-id').value = categoryId;

            document.querySelectorAll('[id^="cat-"]').forEach(el => {
                el.classList.remove('active-category');
            });

            if (categoryId === null) {
                document.getElementById('cat-all')?.classList.add('active-category');
                document.getElementById('selected-category-label').textContent = 'Все категории';
            } else {
                const active = document.getElementById('cat-' + categoryId);
                if (active) {
                    active.classList.add('active-category');
                    document.getElementById('selected-category-label').textContent = active.textContent.trim();
                }
            }
        }

        function triggerFileUpload() {
            if (!selectedCategory) {
                alert('Сначала выберите категорию.');
                return;
            }
            document.getElementById('upload-file').click();
        }

        document.getElementById('check-all')?.addEventListener('change', e => {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    </script>
@endsection
