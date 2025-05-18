@extends('layouts.admin')

@section('title', 'Новое слайдшоу')
@section('header', 'Создание слайдшоу')

@section('content')
    {{-- 🧾 Форма создания слайдшоу --}}
    <form method="POST" action="{{ route('admin.slideshow.store') }}"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-6 max-w-xl space-y-6">
        @csrf

        {{-- 🏷️ Название --}}
        <div>
            <label for="title" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">
                🏷️ Название слайдшоу
            </label>
            <input type="text" name="title" id="title" required
                   class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 shadow-sm focus:outline-none focus:ring focus:ring-indigo-300 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
        </div>

        {{-- 📍 Позиция --}}
        <div>
            <label for="position" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">
                📍 Позиция на странице
            </label>
            <select name="position" id="position"
                    class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 shadow-sm focus:outline-none focus:ring focus:ring-indigo-300 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                <option value="top" {{ old('position') == 'top' ? 'selected' : '' }}>🔝 Вверху страницы</option>
                <option value="bottom" {{ old('position') == 'bottom' ? 'selected' : '' }}>🔽 Внизу страницы</option>
            </select>
        </div>

        {{-- ✅ Кнопка отправки --}}
        <div class="text-right">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md text-sm font-semibold shadow transition">
                <i class="fas fa-save"></i> Создать
            </button>
        </div>
    </form>
@endsection
