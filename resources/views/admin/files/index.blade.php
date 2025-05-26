@extends('layouts.admin')

{{-- @section('title', '📁 Управление файлами') --}}

@section('content')
    {{-- 🔷 Заголовок и панель управления --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📁 Управление файлами</h1>

        <div class="flex flex-wrap items-center gap-2">
            {{-- 🔍 Поиск --}}
            <input type="text" id="searchInput" placeholder="🔍 Поиск по названию..."
                   class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-white"
                   oninput="filterFiles()">

            {{-- ⬆️ Загрузить --}}
            <button onclick="triggerFileUpload()"
                    class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm">
                <i class="fas fa-upload"></i> Загрузить
            </button>

            {{-- 🗑️ Удалить --}}
            <button onclick="submitBulkDelete()"
                    class="inline-flex items-center gap-2 bg-red-600 text-white hover:bg-red-700 px-4 py-2 rounded-md shadow text-sm">
                <i class="fas fa-trash"></i> Удалить
            </button>

            {{-- ➕ Категория --}}
            <button onclick="document.getElementById('create-category-form').classList.toggle('hidden')"
                    class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-700 px-4 py-2 rounded-md shadow text-sm">
                <i class="fas fa-folder-plus"></i> Категория
            </button>
        </div>
    </div>

    {{-- 📂 Категории --}}
    @php $categories = \App\Models\Category::all(); @endphp
    <div class="flex flex-wrap gap-2 items-center bg-gray-100 dark:bg-gray-800 p-3 rounded mb-4">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Категории:</span>

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

    {{-- 🧭 Подпись выбранной категории --}}
    <div class="text-sm text-gray-600 dark:text-gray-300 mb-4">
        Вы выбрали: <strong>
            {{ request('category') ? $categories->firstWhere('id', request('category'))?->icon . ' ' . $categories->firstWhere('id', request('category'))?->title : 'Все категории' }}
        </strong>
    </div>

    {{-- 📄 Таблица файлов --}}
    <div class="overflow-x-auto border rounded-lg shadow-sm dark:border-gray-700">
        <table id="filesTable" class="min-w-full table-auto bg-white dark:bg-gray-900 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-left">
            <tr>
                <th class="px-4 py-3 text-center w-10"><input type="checkbox" id="check-all"></th>
                <th class="px-4 py-3">📄 Название</th>
                <th class="px-4 py-3">📁 Категория</th>
                <th class="px-4 py-3 text-center">📦 Размер</th>
                <th class="px-4 py-3 text-center">⚙️ Действия</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($files as $file)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <td class="px-4 py-3 text-center align-middle">
                        <input type="checkbox" class="row-checkbox" value="{{ $file->id }}">
                    </td>
                    <td class="px-4 py-3 file-name break-all align-middle">{{ $file->name }}</td>
                    <td class="px-4 py-3 align-middle">{{ $file->category->title ?? '—' }}</td>
                    <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                        {{ number_format($file->size / 1024, 2) }} KB
                    </td>
                    <td class="px-4 py-3 text-center align-middle">
                        <div class="flex flex-col items-center space-y-1">
                            <a href="{{ route('admin.files.download', $file->id) }}"
                               class="text-blue-600 hover:text-blue-800 text-xs">
                                <i class="fas fa-download"></i> Скачать
                            </a>
                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-xs rounded px-2 py-1 w-fit max-w-[240px] overflow-hidden">
                                <span class="truncate">{{ asset('storage/' . $file->path) }}</span>
                                <button onclick="copyLink('{{ asset('storage/' . $file->path) }}')"
                                        class="ml-1 text-gray-500 hover:text-black dark:hover:text-white">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $files->withQueryString()->onEachSide(1)->links('vendor.pagination.tailwind') }}
    </div>

    {{-- 🧾 Скрытые формы --}}
    <form id="upload-form" action="{{ route('admin.files.upload') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="hidden" name="category_id" id="upload-category-id">
        <input type="file" name="file" id="upload-file" onchange="document.getElementById('upload-form').submit()">
    </form>

    <form id="bulk-delete-form" method="POST" action="{{ route('admin.files.bulkDelete') }}" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" name="file_ids" id="bulk-delete-ids">
    </form>

    {{-- 🆕 Форма создания категории --}}
    <form id="create-category-form" action="{{ route('admin.categories.store') }}" method="POST"
          class="hidden mt-6 p-4 rounded-md shadow bg-gray-100 dark:bg-gray-800">
        @csrf
        <input type="hidden" name="type" value="file">
        <input type="hidden" name="redirect_back_to_files" value="1">

        <div class="mb-4">
            <label for="category_title" class="block text-sm font-medium">📝 Название</label>
            <input type="text" name="title" id="category_title" required
                   class="mt-1 w-full p-2 border rounded-md dark:bg-gray-900 dark:text-white">
        </div>

        <div class="mb-4">
            <label for="category_icon" class="block text-sm font-medium">🔤 Иконка (эмодзи)</label>
            <input type="text" name="icon" id="category_icon"
                   class="mt-1 w-full p-2 border rounded-md dark:bg-gray-900 dark:text-white">
        </div>

        <button type="submit"
                class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition">
            ➕ Создать категорию
        </button>
    </form>

    {{-- 🧠 Скрипты --}}
    <script>
        function triggerFileUpload() {
            const selectedCategory = '{{ request('category') }}';
            if (!selectedCategory) return alert('Выберите категорию перед загрузкой.');
            document.getElementById('upload-category-id').value = selectedCategory;
            document.getElementById('upload-file').click();
        }

        function submitBulkDelete() {
            const selected = [...document.querySelectorAll('.row-checkbox:checked')].map(cb => cb.value);
            if (!selected.length) return alert('Нет выбранных файлов для удаления.');
            if (!confirm('Удалить выбранные файлы?')) return;
            document.getElementById('bulk-delete-ids').value = selected.join(',');
            document.getElementById('bulk-delete-form').submit();
        }

        function filterFiles() {
            const value = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#filesTable tbody tr');
            rows.forEach(row => {
                const text = row.querySelector('.file-name').textContent.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        }

        function copyLink(link) {
            navigator.clipboard.writeText(link).then(() => alert('Ссылка скопирована ✅'));
        }

        document.getElementById('check-all')?.addEventListener('change', e => {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    </script>
@endsection
