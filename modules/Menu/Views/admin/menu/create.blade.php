@extends('layouts.admin')

@section('title', 'Создать меню')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">➕ Создать новое меню</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Укажите название и позицию для нового набора пунктов меню.</p>
    </div>

    <form action="{{ route('admin.menus.store') }}" method="POST"
          class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg p-6 max-w-2xl">
        @csrf

        {{-- Название --}}
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">Название меню</label>
            <input type="text" name="title"
                   class="w-full border rounded px-4 py-2 dark:bg-gray-800 dark:text-white"
                   required>
        </div>

        {{-- Позиция --}}
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">Позиция</label>
            <select name="position"
                    class="w-full border rounded px-4 py-2 dark:bg-gray-800 dark:text-white">
                <option value="header">🔝 Шапка сайта (header)</option>
                <option value="footer">🔚 Подвал сайта (footer)</option>
                <option value="sidebar">📑 Боковая панель (sidebar)</option>
            </select>
        </div>

        {{-- Статус --}}
        <div class="mb-4">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="active" value="1" checked
                       class="rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Активировать меню сразу</span>
            </label>
        </div>

        <div class="mt-6">
            <button type="submit"
                    class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded shadow text-sm">
                💾 Сохранить меню
            </button>
            <a href="{{ route('admin.menus.index') }}"
               class="ml-4 text-sm text-gray-500 hover:underline">Отмена</a>
        </div>
    </form>
@endsection
