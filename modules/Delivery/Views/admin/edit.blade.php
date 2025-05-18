@extends('layouts.admin')

@section('title', 'Редактировать метод доставки')

@section('content')
    {{-- 🛠️ Заголовок страницы --}}
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2 text-gray-800 dark:text-white">
        ✏️ Редактировать метод доставки
    </h1>

    {{-- 📝 Форма редактирования метода --}}
    <form method="POST"
          action="{{ route('admin.delivery.update', $delivery) }}"
          class="space-y-6 bg-white dark:bg-gray-900 p-6 rounded-lg shadow max-w-2xl w-full mx-auto">
        @csrf
        @method('PUT')

        {{-- 📋 Название --}}
        <x-admin.input
            label="📦 Название метода"
            name="title"
            :value="old('title', $delivery->title)"
            placeholder="Например: Курьерская доставка"
            required
        />

        {{-- 📝 Описание --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                📝 Описание (необязательно)
            </label>
            <textarea id="description" name="description"
                      class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white resize-y"
                      rows="3"
                      placeholder="Например: Доставка в пределах города до двери"
                      title="Можно указать условия, сроки и ограничения"
            >{{ old('description', $delivery->description) }}</textarea>
        </div>

        {{-- 💰 Стоимость --}}
        <x-admin.input
            label="💰 Стоимость (₽)"
            name="price"
            type="number"
            step="0.01"
            :value="old('price', $delivery->price)"
            required
        />

        {{-- ✅ Активность --}}
        <label class="inline-flex items-center mt-2">
            <input type="checkbox" name="active" value="1"
                   class="form-checkbox rounded text-blue-600 mr-2"
                   {{ old('active', $delivery->active) ? 'checked' : '' }}>
            Активен
        </label>

        {{-- 💾 Кнопка обновления --}}
        <div class="text-right pt-4">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow-md transition-all duration-200 transform hover:scale-105">
                💾 Обновить
            </button>
        </div>
    </form>
@endsection
