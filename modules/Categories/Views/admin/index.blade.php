@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    {{-- 🧭 Заголовок и панель управления --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🏷️ Список категорий</h1>

        <div class="flex flex-wrap gap-2">
            {{-- 🔍 Поиск --}}
            <input type="text" id="searchInput"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="🔎 Поиск..." oninput="filterCategories()">

            {{-- 🗑️ Массовое удаление --}}
            <button onclick="submitBulkDelete()" id="bulkDeleteBtn"
                class="inline-flex items-center gap-2 bg-red-600 text-white hover:bg-red-700 px-4 py-2 rounded-md shadow text-sm transition disabled:opacity-50"
                disabled>
                <i class="fas fa-trash"></i> Удалить
            </button>

            {{-- ➕ Добавить --}}
            <a href="{{ route('admin.categories.create') }}"
                class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm transition">
                <i class="fas fa-plus"></i> Добавить
            </a>
        </div>
    </div>

    {{-- 📋 Таблица категорий --}}
    <div class="overflow-x-auto rounded-lg shadow">
        <table id="categoriesTable"
            class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-sm sm:text-base">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs sm:text-sm">
                <tr>
                    <th class="px-4 py-3 w-10 text-left">
                        <input type="checkbox" id="check-all" class="form-checkbox h-4 w-4 text-blue-600">
                    </th>
                    <th class="px-4 py-3 text-left">🏷️ Название</th>
                    <th class="px-4 py-3 text-center">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($categories as $category)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition" data-id="{{ $category->id }}">
                        {{-- ✅ Выбор --}}
                        <td class="px-4 py-3">
                            <input type="checkbox" class="row-checkbox form-checkbox h-4 w-4 text-blue-600"
                                value="{{ $category->id }}">
                        </td>

                        {{-- 🏷️ Название + метка --}}
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white category-title">
                            <div class="flex items-center gap-2">
                                @if ($category->icon)
                                    <span class="text-xl">{{ $category->icon }}</span>
                                @else
                                    <i class="fas fa-tag text-gray-400"></i>
                                @endif

                                <span
                                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $category->title }}
                                </span>
                            </div>
                        </td>

                        {{-- 🛠️ Действия --}}
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $category->id) }}"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md shadow text-xs font-medium transition"
                                title="Редактировать категорию" aria-label="Редактировать категорию">
                                <i class="fas fa-edit"></i> Редактировать
                            </a>
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

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $categories->withQueryString()->links('vendor.pagination.tailwind') }}
    </div>

    {{-- 📜 JS --}}
    <script>
        const csrfToken = '{{ csrf_token() }}';

        function filterCategories() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#categoriesTable tbody tr');

            rows.forEach(row => {
                const title = row.querySelector('.category-title')?.textContent.toLowerCase() || '';
                row.style.display = title.includes(search) ? '' : 'none';
            });
        }

        function submitBulkDelete() {
            const selected = [...document.querySelectorAll('.row-checkbox:checked')].map(cb => cb.value);
            if (!selected.length) return alert('Выберите категории для удаления.');
            if (!confirm('Удалить выбранные категории?')) return;

            selected.forEach(id => {
                const row = document.querySelector(`tr[data-id='${id}']`);
                if (row) {
                    row.classList.add('opacity-30', 'transition-all', 'duration-300');
                }
            });

            fetch("{{ route('admin.categories.bulkDelete') }}", {

                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ category_ids: selected.join(',') })
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Ошибка при удалении категорий.');
                }
            });
        }

        document.getElementById('check-all')?.addEventListener('change', e => {
            const checked = e.target.checked;
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checked);
            toggleDeleteButton();
        });

        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', toggleDeleteButton);
        });

        function toggleDeleteButton() {
            const selectedCount = document.querySelectorAll('.row-checkbox:checked').length;
            document.getElementById('bulkDeleteBtn').disabled = selectedCount === 0;
        }
    </script>
@endsection
