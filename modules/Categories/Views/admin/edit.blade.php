@extends('layouts.admin')

@section('title', 'Редактировать категорию')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Редактировать категорию</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.update', ['id' => $category->id]) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="title" class="block mb-1 font-semibold">Название</label>
            <input type="text" name="title" id="title" value="{{ old('title', $category->title) }}"
                   class="w-full border rounded px-3 py-2" required>
        </div>

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Сохранить изменения
        </button>
    </form>
@endsection
