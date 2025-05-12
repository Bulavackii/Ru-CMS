@extends('layouts.admin')

@section('title', 'Управление файлами')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📁 Управление файлами</h1>
        <!-- Кнопка "Загрузить файл" -->
        <button onclick="document.getElementById('file-input').click();"
            class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-upload"></i> Загрузить файл
        </button>
        <!-- Кнопка для создания категории -->
        <button onclick="document.getElementById('create-category-form').classList.toggle('hidden');"
            class="inline-flex items-center gap-2 bg-green-500 text-white hover:bg-green-600 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-plus"></i> Создать категорию
        </button>
    </div>

    {{-- 🧭 Фильтр по категориям --}}
    <div class="flex flex-wrap items-center gap-2 mb-6 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
        @php
            $categories = App\Models\FileCategory::all();
            $currentCategory = request('category');
        @endphp

        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Категории:</span>

        <a href="{{ route('admin.files.index') }}"
            class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm
                  {{ !$currentCategory ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            Все
        </a>

        @foreach ($categories as $category)
            <a href="{{ route('admin.files.index', ['category' => $category->id]) }}"
                class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm
                      {{ $currentCategory == $category->id ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                {{ $category->icon }} {{ $category->name }}
            </a>
        @endforeach
    </div>

    {{-- 📊 Список файлов --}}
    <h2 class="mt-6">Загруженные файлы</h2>
    <table
        class="min-w-full mt-4 table-auto border border-gray-300 rounded-lg overflow-hidden shadow-md bg-white dark:bg-gray-900">
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
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" name="selected[]" value="{{ $file->id }}" class="row-checkbox">
                    </td>
                    <td class="px-4 py-3">{{ $file->name }}</td>
                    <td class="px-4 py-3">{{ $file->category->name }}</td>
                    <td class="px-4 py-3">{{ number_format($file->size / 1024, 2) }} KB</td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('admin.files.download', $file->id) }}" class="text-blue-500">Скачать</a> |
                        <a href="{{ asset('storage/' . $file->path) }}" target="_blank" class="text-blue-500">Ссылка</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $files->withQueryString()->onEachSide(1)->links('vendor.pagination.tailwind') }}
    </div>

    {{-- 📜 Сценарии для массовых действий --}}
    <script>
        document.getElementById('check-all')?.addEventListener('change', e =>
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked)
        );
    </script>

    {{-- 📤 Скрытая форма для загрузки файла --}}
    <form action="{{ route('admin.files.upload') }}" method="post" enctype="multipart/form-data" style="display:none;">
        @csrf
        <input type="file" id="file-input" name="file" required class="hidden" onchange="this.form.submit()">
        <div class="mb-4">
            <label for="category_id" class="block">Выберите категорию</label>
            <select name="category_id" required class="mt-2 p-2 border rounded-md">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- 📜 Форма для создания новой категории --}}
    <form id="create-category-form" action="{{ route('admin.categories.store') }}" method="post"
        class="hidden mt-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-md shadow-md">
        @csrf
        <div class="mb-4">
            <label for="category_name" class="block">Название категории</label>
            <input type="text" id="category_name" name="name" required class="mt-2 p-2 border rounded-md w-full"
                placeholder="Введите название категории">
            @error('name')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-4">
            <label for="category_icon" class="block">Иконка категории (например, 🎶, 📷)</label>
            <input type="text" id="category_icon" name="icon" class="mt-2 p-2 border rounded-md w-full"
                placeholder="Введите иконку категории">
        </div>
        <button type="submit"
            class="inline-flex items-center gap-2 bg-blue-500 text-white hover:bg-blue-600 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            Создать категорию
        </button>
    </form>
@endsection
