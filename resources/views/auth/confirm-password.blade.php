@extends('layouts.guest')

@section('title', 'Подтверждение пароля')

@section('content')
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🔐 Инфо-блок --}}
        <h1 class="text-xl font-bold text-gray-800 dark:text-white mb-2 flex items-center gap-2">
            <i class="fas fa-shield-alt text-blue-600"></i> Подтверждение пароля
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Это защищённая часть приложения. Пожалуйста, введите пароль для продолжения.
        </p>

        {{-- 🔁 Форма подтверждения --}}
        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
            @csrf

            {{-- 🔑 Пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-lock mr-1"></i> Пароль
                </label>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="Введите ваш пароль"
                       title="Введите тот пароль, с которым вы вошли в систему"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                {{-- 💬 Подсказка под полем --}}
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Введите текущий пароль для подтверждения действия.
                </p>

                {{-- Ошибка --}}
                @error('password')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- ✅ Кнопка --}}
            <div class="flex justify-between items-center">
                <a href="{{ url()->previous() }}"
                   class="text-sm text-gray-500 dark:text-gray-400 hover:underline transition"
                   title="Вернуться назад">
                    ← Назад
                </a>
                <button type="submit"
                        title="Подтвердить пароль и продолжить"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-md shadow transition transform hover:scale-105">
                    <i class="fas fa-check-circle"></i> Подтвердить
                </button>
            </div>
        </form>
    </div>
@endsection
