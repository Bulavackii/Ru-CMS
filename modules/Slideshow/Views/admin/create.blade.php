@extends('layouts.admin')

@section('title', 'Новое слайдшоу')
@section('header', 'Создание слайдшоу')

@section('content')
    <form method="POST" action="{{ route('admin.slideshow.store') }}" class="max-w-lg">
        @csrf

        <div class="mb-4">
            <label class="block font-semibold mb-1" for="title">Название</label>
            <input type="text" name="title" id="title" required class="w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label for="position" class="block font-semibold mb-1">Позиция на странице</label>
            <select name="position" id="position" class="w-full border rounded px-3 py-2">
                <option value="top" {{ old('position') == 'top' ? 'selected' : '' }}>Вверху страницы</option>
                <option value="bottom" {{ old('position') == 'bottom' ? 'selected' : '' }}>Внизу страницы</option>
            </select>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Создать</button>
    </form>
@endsection
