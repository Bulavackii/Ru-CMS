@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Список категорий</h1>
        <a href="{{ route('admin.categories.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            + Добавить категорию
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full bg-white shadow rounded overflow-hidden">
        <thead>
            <tr class="bg-gray-100 border-b">
                <th class="text-left px-4 py-2">Название</th>
                <th class="text-left px-4 py-2">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $category->title }}</td>
                    <td class="px-4 py-2 space-x-2">
                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                           class="text-blue-600 hover:underline">Редактировать</a>

                        <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                              class="inline-block"
                              onsubmit="return confirm('Удалить эту категорию?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Удалить</button>
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
@endsection
