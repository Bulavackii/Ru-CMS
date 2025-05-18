@extends('layouts.admin')

@push('scripts')
    {{-- Alpine.js для интерактивности --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    {{-- 🔍 Поиск по заголовку и контенту --}}
    <script>
        function filterPages() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#pagesTable tbody tr.page-row');

            rows.forEach(row => {
                const title = row.querySelector('.page-title')?.textContent.toLowerCase() || '';
                const contentRow = row.nextElementSibling;
                const content = contentRow?.dataset?.content?.toLowerCase() || '';
                const match = title.includes(search) || content.includes(search);

                row.style.display = match ? '' : 'none';
                if (contentRow && contentRow.classList.contains('page-content')) {
                    contentRow.style.display = match ? '' : 'none';
                }
            });
        }

        function toggleContent(id, btn) {
            const row = document.getElementById(`page-content-${id}`);
            if (!row.dataset.loaded) {
                fetch(`/admin/pages/${id}/preview`)
                    .then(res => res.text())
                    .then(html => {
                        row.querySelector('.page-content-body').innerHTML = html;
                        row.classList.remove('hidden');
                        row.dataset.loaded = true;
                        btn.innerHTML = '🙈 Скрыть';
                    });
            } else {
                const isHidden = row.classList.toggle('hidden');
                btn.innerHTML = isHidden ? '👁️ Показать' : '🙈 Скрыть';
            }
        }
    </script>
@endpush

@section('title', 'Страницы')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📄 Страницы</h1>

        <div class="flex flex-col sm:flex-row gap-3 sm:items-center w-full sm:w-auto">
            <input type="text" id="searchInput" placeholder="🔍 Поиск по заголовку и содержимому..."
                   oninput="filterPages()"
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 rounded-md shadow-sm w-full sm:w-72 text-sm" />
            <a href="{{ route('admin.pages.create') }}"
               class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm font-semibold transition">
                <i class="fas fa-plus"></i> Новая
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-md border border-gray-300 dark:border-gray-700 shadow">
        <table id="pagesTable" class="min-w-full bg-white dark:bg-gray-900 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">📄 Заголовок</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">🔗 Slug</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">🏷️ Категории</th>
                    <th class="px-4 py-3 text-center">✅</th>
                    <th class="px-4 py-3 text-center">🏠</th>
                    <th class="px-4 py-3 text-center">👁️</th>
                    <th class="px-4 py-3 text-center">⚙️</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($pages as $page)
                    <tr class="page-row hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white page-title">
                            <a href="{{ route('frontend.pages.show', $page->slug) }}" target="_blank"
                               class="text-blue-600 dark:text-blue-400" title="Открыть страницу">
                                {{ $page->title }}
                            </a>
                            <div class="text-xs text-gray-500 dark:text-gray-400 block md:hidden">
                                {{ $page->slug }}
                            </div>
                        </td>

                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden md:table-cell">
                            {{ $page->slug }}
                        </td>

                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden md:table-cell">
                            @forelse ($page->categories as $cat)
                                <span class="inline-block bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 text-xs rounded-full px-2 py-0.5 mr-1 mb-1">
                                    🏷️ {{ $cat->title }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400 italic">—</span>
                            @endforelse
                        </td>

                        <td class="px-4 py-3 text-center">{!! $page->published ? '✅' : '❌' !!}</td>
                        <td class="px-4 py-3 text-center">{!! $page->show_on_homepage ? '🏠' : '—' !!}</td>

                        <td class="px-4 py-3 text-center">
                            <button onclick="toggleContent({{ $page->id }}, this)"
                                    class="text-blue-600 hover:text-blue-800 text-xs underline transition">
                                👁️ Показать
                            </button>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center items-center gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}"
                                   class="text-blue-600 hover:text-blue-800 text-base" title="Редактировать">✏️</a>
                                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST"
                                      onsubmit="return confirm('Удалить страницу «{{ $page->title }}»?')" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-base" title="Удалить">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="page-content-{{ $page->id }}" data-content="{{ strip_tags($page->content) }}"
                        class="page-content hidden bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <td colspan="7" class="px-6 py-4">
                            <div class="prose max-w-none dark:prose-invert page-content-body text-sm text-gray-700 dark:text-gray-200">
                                Загрузка...
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 📑 Пагинация --}}
    <div class="mt-6">
        {{ $pages->links('vendor.pagination.tailwind') }}
    </div>
@endsection
