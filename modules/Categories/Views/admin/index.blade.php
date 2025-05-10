@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Список категорий</h1>
        <a href="{{ route('admin.categories.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition">
            + Добавить категорию
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded shadow">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
            <thead class="bg-gray-100 border-b text-sm text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">🏷️ Название</th>
                    <th class="text-center px-4 py-3">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse ($categories as $index => $category)
                    <tr
                        class="transition duration-150 ease-in-out {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-blue-50">
                        <td class="px-4 py-3">
                            {{ $category->title }}
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $category->id) }}"
                                class="text-blue-600 hover:text-blue-800 mr-2 transition-transform transform hover:scale-110"
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
                        <td colspan="2" class="text-center text-gray-500 py-6">Категорий пока нет.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Пагинация --}}
        <div class="mt-4">
            {{ $categories->links() }}
        </div>

    </div>
@endsection
