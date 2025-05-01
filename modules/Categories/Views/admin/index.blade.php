@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Категории</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.categories.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Добавить категорию</a>
    </div>

    <table class="min-w-full bg-white shadow rounded">
        <thead>
            <tr class="border-b bg-gray-100">
                <th class="text-left px-4 py-2">Название</th>
                <th class="text-left px-4 py-2">Тип</th>
                <th class="text-left px-4 py-2">Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $category->title }}</td>
                    <td class="px-4 py-2">{{ $category->type }}</td>
                    <td class="px-4 py-2 flex gap-2">
                        <a href="{{ route('admin.categories.edit', $category->id) }}" class="text-blue-600">Редактировать</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600" onclick="return confirm('Удалить категорию?')">Удалить</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
