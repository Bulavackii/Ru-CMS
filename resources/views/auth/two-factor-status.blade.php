@extends('layouts.guest')

@section('title', 'Статус двухфакторной аутентификации')

@section('content')
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🧩 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700 dark:text-blue-400 flex items-center justify-center gap-2">
            <i class="fas fa-shield-alt"></i> Двухфакторная аутентификация
        </h2>

        @if($enabled)
            {{-- ✅ Включена --}}
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg text-center">
                <i class="fas fa-check-circle text-4xl mb-2"></i>
                <p class="font-semibold">Двухфакторная аутентификация включена</p>
                <p class="text-sm mt-1">Ваш аккаунт защищен дополнительным уровнем безопасности.</p>
            </div>

            {{-- 🔓 Форма отключения --}}
            <form method="POST" action="{{ route('two-factor.disable') }}" class="space-y-4">
                @csrf
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                        <i class="fas fa-lock mr-1"></i> Подтвердите паролем для отключения
                    </label>
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-2 rounded-md shadow transition">
                    <i class="fas fa-times mr-1"></i> Отключить двухфакторную аутентификацию
                </button>
            </form>
        @else
            {{-- ❌ Отключена --}}
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg text-center">
                <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                <p class="font-semibold">Двухфакторная аутентификация отключена</p>
                <p class="text-sm mt-1">Рекомендуется включить для повышения безопасности аккаунта.</p>
            </div>

            <a href="{{ route('two-factor.setup') }}"
               class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-md shadow transition text-center">
                <i class="fas fa-plus mr-1"></i> Включить двухфакторную аутентификацию
            </a>
        @endif

        {{-- 🔗 Ссылка назад --}}
        <div class="text-center text-sm text-gray-600 dark:text-gray-400">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Вернуться в личный кабинет</a>
        </div>
    </div>
@endsection




