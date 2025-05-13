@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    {{-- 🔘 Заголовок + панель управления --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🏷️ Список категорий</h1>
        <div class="flex gap-2">
            <input type="text" id="searchInput"
                   class="border border-gray-300 rounded-md p-2 text-sm"
                   placeholder="Поиск..." oninput="filterCategories()">
            <button onclick="submitBulkDelete()"
                class="inline-flex items-center gap-2 bg-red-600 text-white hover:bg-red-700 px-4 py-2 rounded-md shadow text-sm">
                <i class="fas fa-trash"></i> Удалить
            </button>
            <a href="{{ route('admin.categories.create') }}"
               class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm">
                <i class="fas fa-plus"></i> Категория
            </a>
        </div>
    </div>

    {{-- 📊 Таблица категорий --}}
    <form id="bulk-delete-form" method="POST" action="{{ route('admin.categories.bulkDelete') }}">
        @csrf
        @method('DELETE')
        <input type="hidden" name="category_ids" id="bulk-delete-ids">

        <div class="overflow-x-auto">
            <table id="categoriesTable" class="min-w-full bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left"><input type="checkbox" id="check-all"></th>
                        <th class="text-left px-4 py-3">🏷️ Название</th>
                        <th class="text-center px-4 py-3">⚙️ Действия</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="px-4 py-3"><input type="checkbox" class="row-checkbox" value="{{ $category->id }}"></td>
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium category-title">
                                {{ $category->icon }} {{ $category->title }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('admin.categories.edit', $category->id) }}"
                                   class="text-blue-600 hover:text-blue-800 mr-3 transition-transform transform hover:scale-110"
                                   title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                                      class="inline-block" onsubmit="return confirm('Удалить эту категорию?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 transition-transform transform hover:scale-110"
                                            title="Удалить">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-500 dark:text-gray-400 py-6">
                                📭 Категорий пока нет.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $categories->withQueryString()->links('vendor.pagination.tailwind') }}
    </div>

    <script>
        function filterCategories() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#categoriesTable tbody tr');

            rows.forEach(row => {
                const title = row.querySelector('.category-title')?.textContent.toLowerCase();
                const match = title.includes(search);
                row.style.display = match ? '' : 'none';
            });
        }

        function submitBulkDelete() {
            const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if (!selected.length) return alert('Выберите категории для удаления.');
            if (!confirm('Удалить выбранные категории?')) return;
            document.getElementById('bulk-delete-ids').value = selected.join(',');
            document.getElementById('bulk-delete-form').submit();
        }

        document.getElementById('check-all')?.addEventListener('change', e => {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    </script>
@endsection
