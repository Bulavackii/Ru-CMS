@extends('layouts.admin')

@section('title', 'Создать категорию')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Добавить категорию</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Название</label>
            <input type="text" name="title" id="title" class="mt-1 block w-full border-gray-300 rounded" value="{{ old('title') }}" required>
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700">Тип</label>
            <input type="text" name="type" id="type" class="mt-1 block w-full border-gray-300 rounded" value="{{ old('type') }}" required>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Описание</label>
            <textarea name="description" id="description" rows="3" class="mt-1 block w-full border-gray-300 rounded">{{ old('description') }}</textarea>
        </div>

        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Создать</button>
            <a href="{{ route('admin.categories.index') }}" class="text-gray-600 ml-4">Отмена</a>
        </div>
    </form>
@endsection
