@extends('layouts.guest')

@section('title', 'Двухфакторная аутентификация')

@section('content')
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🧩 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700 dark:text-blue-400 flex items-center justify-center gap-2">
            <i class="fas fa-shield-alt"></i> Двухфакторная аутентификация
        </h2>

        {{-- 📘 Инфо --}}
        <p class="text-sm text-gray-700 dark:text-gray-300 text-center">
            Введите код из приложения Google Authenticator для завершения входа.
        </p>

        {{-- ❗ Ошибки --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Ошибка:</strong>
                </div>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 🔐 Форма ввода кода --}}
        <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-6">
            @csrf

            {{-- 🔑 Код 2FA --}}
            <div>
                <label for="code" class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                    <i class="fas fa-key mr-1"></i> Код из приложения
                </label>
                <input id="code"
                       type="text"
                       name="code"
                       required
                       autofocus
                       maxlength="6"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       title="Введите 6-значный код из Google Authenticator"
                       class="w-full px-4 py-2 text-center text-2xl tracking-widest border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none @error('code') border-red-500 @enderror"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
                    Откройте приложение Google Authenticator и введите 6-значный код.
                </p>
                @error('code')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🚀 Кнопка --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-md shadow transition transform hover:scale-105">
                    <i class="fas fa-check mr-1"></i> Подтвердить
                </button>
            </div>
        </form>

        {{-- 🔗 Ссылка на вход --}}
        <div class="text-center text-sm text-gray-600 dark:text-gray-400">
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Вернуться на страницу входа</a>
        </div>
    </div>

    <script>
        // Автоматический фокус на поле ввода
        document.getElementById('code')?.focus();

        // Автоматическая отправка при вводе 6 цифр
        document.getElementById('code')?.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Можно автоматически отправить форму
                // this.form.submit();
            }
        });
    </script>
@endsection




