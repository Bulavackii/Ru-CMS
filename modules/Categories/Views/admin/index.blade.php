@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    {{-- 🔘 Заголовок + кнопка --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🏷️ Список категорий</h1>
        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition">
            <i class="fas fa-plus"></i> Категория
        </a>
    </div>

    {{-- 📊 Таблица категорий --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                <tr>
                    <th class="text-left px-4 py-3">🏷️ Название</th>
                    <th class="text-center px-4 py-3">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($categories as $index => $category)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium">
                            {{ $category->title }}
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            {{-- ✏️ Редактировать --}}
                            <a href="{{ route('admin.categories.edit', $category->id) }}"
                               class="text-blue-600 hover:text-blue-800 mr-3 transition-transform transform hover:scale-110"
                               title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>

                            {{-- 🗑️ Удалить --}}
                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('Удалить эту категорию?')">
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
                        <td colspan="2" class="text-center text-gray-500 dark:text-gray-400 py-6">
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
@endsection
