@extends('layouts.admin')

@section('title', 'Создать меню')

@section('content')
    {{-- 🔹 Заголовок страницы --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">➕ Создать новое меню</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Укажите название и позицию для нового набора пунктов меню.
        </p>
    </div>

    {{-- 📝 Адаптивная форма создания меню --}}
    <form action="{{ route('admin.menus.store') }}" method="POST"
          class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl shadow p-6 w-full max-w-3xl mx-auto space-y-5">
        @csrf

        {{-- 🏷️ Название меню --}}
        <div class="flex flex-col">
            <label for="title" class="text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">
                🏷️ Название меню
            </label>
            <input type="text" id="title" name="title"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500"
                   placeholder="Например: Основное меню"
                   required>
        </div>

        {{-- 📍 Позиция отображения --}}
        <div class="flex flex-col">
            <label for="position" class="text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">
                📍 Позиция меню
            </label>
            <select id="position" name="position"
                    class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="header">🔝 Шапка сайта (header)</option>
                <option value="footer">🔚 Подвал сайта (footer)</option>
                <option value="sidebar">📑 Боковая панель (sidebar)</option>
            </select>
        </div>

        {{-- ✅ Чекбокс активности --}}
        <div class="flex items-start">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="active" value="1" checked
                       class="rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">✅ Активировать меню сразу</span>
            </label>
        </div>

        {{-- 💾 Кнопки действий --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-4">
            <button type="submit"
                    class="w-full sm:w-auto bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md shadow text-sm transition-all duration-200">
                💾 Сохранить меню
            </button>

            <a href="{{ route('admin.menus.index') }}"
               class="w-full sm:w-auto text-sm text-gray-500 hover:underline text-center">
                ⬅️ Отмена и возврат
            </a>
        </div>
    </form>
@endsection
