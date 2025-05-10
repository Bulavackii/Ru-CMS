@extends('layouts.admin')

@section('title', 'Редактировать категорию')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-edit text-green-600"></i>
            Редактировать категорию
        </h1>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded shadow mb-4 animate-fade-in">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.update', ['id' => $category->id]) }}"
          class="bg-white shadow-md rounded-lg p-6 w-full max-w-lg animate-fade-in">
        @csrf
        @method('PUT')

        {{-- Поле ввода --}}
        <div class="mb-4">
            <label for="title" class="block mb-1 font-semibold text-sm text-gray-700">
                🏷️ Название категории
            </label>
            <input type="text" name="title" id="title" value="{{ old('title', $category->title) }}"
                   class="w-full border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring focus:ring-green-300 transition shadow-sm"
                   placeholder="Например: Новости" required>
        </div>

        {{-- Кнопка --}}
        <div class="mt-6">
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded shadow transition transform hover:scale-105">
                💾 Сохранить
            </button>
        </div>
    </form>

    {{-- 🔄 Анимация --}}
    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fade-in 0.4s ease-in-out;
        }
    </style>
@endsection
