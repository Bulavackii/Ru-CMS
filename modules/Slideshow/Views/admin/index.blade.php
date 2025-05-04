@extends('layouts.admin')

@section('title', 'Слайдшоу')
@section('header', 'Управление слайдшоу')

@section('content')
    <a href="{{ route('admin.slideshow.create') }}" class="mb-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">➕ Добавить слайдшоу</a>

    @if (session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
    @endif

    <table class="w-full table-auto bg-white shadow rounded">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-2">ID</th>
                <th class="p-2">Название</th>
                <th class="p-2">Кол-во слайдов</th>
                <th class="p-2">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($slideshows as $slideshow)
                <tr class="border-b">
                    <td class="p-2">{{ $slideshow->id }}</td>
                    <td class="p-2">{{ $slideshow->title }}</td>
                    <td class="p-2">{{ $slideshow->items->count() }}</td>
                    <td class="p-2 space-x-2">
                        <a href="{{ route('admin.slideshow.edit', $slideshow->id) }}" class="text-blue-600 hover:underline">✏️</a>
                        <form action="{{ route('admin.slideshow.destroy', $slideshow->id) }}" method="POST" class="inline" onsubmit="return confirm('Удалить слайдшоу?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">🗑️</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-4 text-center text-gray-500">Нет слайдшоу</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
