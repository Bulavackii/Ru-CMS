@extends('layouts.admin')

@section('title', 'Слайдшоу')
@section('header', 'Управление слайдшоу')

@section('content')
    {{-- 🔘 Кнопка "Добавить" --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🎞️ Слайдшоу</h1>
        <a href="{{ route('admin.slideshow.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition">
            <i class="fas fa-plus"></i> Слайдшоу
        </a>
    </div>

    {{-- ✅ Уведомление --}}
    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded shadow">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- 📊 Таблица --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-md overflow-hidden">
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
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $slideshow->id }}</td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $slideshow->title }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $slideshow->items->count() }}</td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            {{-- ✏️ Редактировать --}}
                            <a href="{{ route('admin.slideshow.edit', $slideshow->id) }}"
                               class="text-blue-600 hover:text-blue-800 transition-transform transform hover:scale-110 mr-2"
                               title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>

                            {{-- 🗑️ Удалить --}}
                            <form action="{{ route('admin.slideshow.destroy', $slideshow->id) }}" method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('Удалить слайдшоу?');">
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
                        <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Нет слайдшоу
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
