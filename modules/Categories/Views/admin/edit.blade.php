@extends('layouts.admin')

@section('title', 'Редактировать категорию')

@section('content')
    {{-- 🖊️ Заголовок --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2 text-gray-800 dark:text-white">
            <i class="fas fa-edit text-green-600"></i>
            Редактировать категорию
        </h1>
    </div>

    {{-- ❗ Блок ошибок --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded shadow mb-4 animate-fade-in text-sm sm:text-base">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- 📋 Форма редактирования --}}
    <form method="POST" action="{{ route('admin.categories.update', ['id' => $category->id]) }}"
          class="bg-white dark:bg-gray-800 shadow-md rounded-xl p-6 w-full max-w-2xl mx-auto animate-fade-in">

        @csrf
        @method('PUT')

        {{-- 🏷️ Поле: Название категории --}}
        <div class="mb-4">
            <label for="title" class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                🏷️ Название категории
            </label>
            <input type="text" name="title" id="title"
                   value="{{ old('title', $category->title) }}"
                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded px-4 py-2
                          focus:outline-none focus:ring-2 focus:ring-green-400 transition shadow-sm text-sm sm:text-base"
                   placeholder="Например: Новости" required>
        </div>

        {{-- 💾 Кнопка сохранения --}}
        <div class="mt-6 flex justify-end">
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg shadow
                           transition-transform transform hover:scale-105 text-sm sm:text-base">
                💾 Сохранить
            </button>
        </div>
    </form>

    {{-- 🔄 Анимация появления --}}
    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-in-out;
        }
    </style>
@endsection
