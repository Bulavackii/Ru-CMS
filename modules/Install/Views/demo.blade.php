@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            {{-- Шаги установки --}}
            <ol class="flex items-center justify-center gap-2 text-xs text-gray-500 mb-8">
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">1. Приветствие</li>
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">2. Требования</li>
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">3. База данных</li>
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">4. Администратор</li>
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">5. Лицензия</li>
                <li class="px-2 py-1 rounded bg-blue-600 text-white font-semibold">6. Демо-данные</li>
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">7. Готово</li>
            </ol>

            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">📦 Демо-данные</h1>
                <p class="text-lg text-gray-600">Установить стартовые данные для быстрого начала работы?</p>
            </div>

            <form method="POST" action="{{ route('install.demo') }}">
                @csrf
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Что будет установлено:</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li>✅ Примерные категории (Новости, Товары, Услуги)</li>
                        <li>✅ Несколько демо-новостей</li>
                        <li>✅ Главное меню с пунктами</li>
                        <li>✅ Базовая структура контента</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="install_demo" value="1" {{ $installDemo ? 'checked' : '' }} 
                               class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                        <span class="text-gray-700 font-medium">Да, установить демо-данные</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-2 ml-8">
                        Вы всегда сможете удалить демо-данные позже из админ-панели
                    </p>
                </div>

                <div class="flex justify-between items-center">
                    <a href="{{ route('install.admin') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        ← Назад
                    </a>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                        Завершить установку →
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

