@extends('layouts.admin')

@section('title', 'Слайдшоу')
@section('header', 'Управление слайдшоу')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🎞️ Слайдшоу</h1>
    <div class="flex gap-2">
        <input type="text" id="searchInput" class="border border-gray-300 rounded-md p-2 text-sm"
               placeholder="Поиск по названию..." oninput="filterSlideshows()">
        <a href="{{ route('admin.slideshow.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm font-semibold">
            <i class="fas fa-plus"></i> Слайдшоу
        </a>
    </div>
</div>

<div class="overflow-x-auto">
    <table id="slideshowsTable"
           class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-md overflow-hidden">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
            <tr>
                <th class="px-4 py-3 text-left">🆔 ID</th>
                <th class="px-4 py-3 text-left">🏷️ Название</th>
                <th class="px-4 py-3 text-left">🖼️ Слайды</th>
                <th class="px-4 py-3 text-center">⚙️ Действия</th>
            </tr>
        </thead>
        <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($slideshows as $slideshow)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <td class="px-4 py-3">{{ $slideshow->id }}</td>
                    <td class="px-4 py-3 slideshow-title">{{ $slideshow->title }}</td>
                    <td class="px-4 py-3">{{ $slideshow->items->count() }}</td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <a href="{{ route('admin.slideshow.edit', $slideshow->id) }}"
                           class="text-blue-600 hover:text-blue-800 mr-2 transition-transform transform hover:scale-110"
                           title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.slideshow.destroy', $slideshow->id) }}" method="POST" class="inline-block"
                              onsubmit="return confirm('Удалить слайдшоу?')">
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
                    <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400">📭 Нет слайдшоу</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $slideshows->withQueryString()->links('vendor.pagination.tailwind') }}
</div>

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
