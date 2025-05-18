@extends('layouts.admin')

@section('title', 'Добавить метод доставки')

@section('content')
    {{-- 🔰 Заголовок страницы --}}
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2 text-gray-800 dark:text-white">
        ➕ Добавить метод доставки
    </h1>

    {{-- 📝 Форма создания метода доставки --}}
    <form method="POST"
          action="{{ route('admin.delivery.store') }}"
          class="space-y-6 bg-white dark:bg-gray-900 p-6 rounded-lg shadow max-w-2xl w-full mx-auto">
        @csrf

        {{-- 📋 Название метода --}}
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🏷️ Название метода доставки
            </label>
            <input type="text" id="title" name="title"
                   value="{{ old('title') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="Например: Курьером, Почта России, Самовывоз"
                   title="Введите понятное название метода доставки"
                   required>
        </div>

        {{-- 📝 Описание (опционально) --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                📄 Описание (необязательно)
            </label>
            <textarea id="description" name="description" rows="3"
                      class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                      placeholder="Например: Доставка курьером по Москве в течение 2-3 дней"
                      title="Уточните условия или сроки этого метода доставки">{{ old('description') }}</textarea>
        </div>

        {{-- 💰 Стоимость --}}
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                💰 Стоимость (₽)
            </label>
            <input type="number" id="price" name="price" step="0.01"
                   value="{{ old('price') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="Укажите цену, например: 299"
                   title="Укажите стоимость данного метода доставки в рублях"
                   required>
        </div>

        {{-- ✅ Статус активности --}}
        <div class="flex items-center gap-2">
            <input type="checkbox" name="active" id="active" value="1"
                   {{ old('active', true) ? 'checked' : '' }}
                   class="form-checkbox rounded text-blue-600 dark:bg-gray-700 dark:border-gray-600">
            <label for="active" class="text-sm text-gray-700 dark:text-gray-300">
                ✅ Метод активен (будет доступен клиентам)
            </label>
        </div>

        {{-- 💾 Кнопка сохранения --}}
        <div class="text-right">
            <button type="submit"
                    class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded shadow-md transition-all duration-200 transform hover:scale-105">
                💾 Сохранить
            </button>
        </div>
    </form>
@endsection
