@extends('layouts.admin')

@section('title', 'Слайдшоу')
@section('header', 'Управление слайдшоу')

@section('content')
    {{-- 🔝 Заголовок и действия --}}
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🎞️ Слайдшоу</h1>
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
            {{-- 🔍 Поиск --}}
            <input type="text" id="searchInput"
                   class="border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm w-full md:w-64 shadow-sm"
                   placeholder="🔎 Поиск по названию..." oninput="filterSlideshows()">
            {{-- ➕ Кнопка добавления --}}
            <a href="{{ route('admin.slideshow.create') }}"
               class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-4 py-2 rounded-md shadow text-sm font-semibold transition">
                <i class="fas fa-plus"></i> Слайдшоу
            </a>
        </div>
    </div>

    {{-- 📋 Таблица слайдшоу --}}
    <div class="overflow-x-auto rounded-xl shadow border border-gray-200 dark:border-gray-800">
        <table id="slideshowsTable"
               class="min-w-full text-sm bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">🆔 ID</th>
                    <th class="px-4 py-3 text-left">🏷️ Название</th>
                    <th class="px-4 py-3 text-left">🖼️ Слайды</th>
                    <th class="px-4 py-3 text-center">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-200">
                @forelse ($slideshows as $slideshow)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3">{{ $slideshow->id }}</td>
                        <td class="px-4 py-3 slideshow-title font-medium">{{ $slideshow->title }}</td>
                        <td class="px-4 py-3">{{ $slideshow->items->count() }}</td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            {{-- ✏️ Редактировать --}}
                            <a href="{{ route('admin.slideshow.edit', $slideshow->id) }}"
                               class="text-blue-600 hover:text-blue-800 mr-3 transition-transform transform hover:scale-110"
                               title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                            {{-- 🗑️ Удалить --}}
                            <form action="{{ route('admin.slideshow.destroy', $slideshow->id) }}" method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('Удалить это слайдшоу?')">
                                @csrf @method('DELETE')
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
                        <td colspan="4"
                            class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 italic">
                            📭 Нет созданных слайдшоу
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 📑 Пагинация --}}
    <div class="mt-6">
        {{ $slideshows->withQueryString()->links('vendor.pagination.tailwind') }}
    </div>

    {{-- 🧠 JS-фильтрация таблицы по названию --}}
    <script>
        function filterSlideshows() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#slideshowsTable tbody tr');

            rows.forEach(row => {
                const title = row.querySelector('.slideshow-title')?.textContent.toLowerCase();
                row.style.display = title && title.includes(search) ? '' : 'none';
            });
        }
    </script>
@endsection
