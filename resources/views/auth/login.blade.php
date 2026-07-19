@extends('layouts.guest')

@section('title', 'Вход')

@section('content')
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🧩 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700 dark:text-blue-400 flex items-center justify-center gap-2">
            <i class="fas fa-sign-in-alt"></i> Вход в систему
        </h2>

        {{-- ✅ Сообщение об успехе --}}
        @if (session('status'))
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg shadow-sm text-sm flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- ❗ Ошибки валидации --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Ошибка входа:</strong>
                </div>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 🔐 Форма входа --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                    <i class="fas fa-envelope mr-1"></i> E-mail
                </label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       placeholder="Введите e-mail"
                       title="Введите ваш адрес электронной почты"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Используйте e-mail, с которым вы регистрировались.</p>
            </div>

            {{-- 🔒 Пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                    <i class="fas fa-lock mr-1"></i> Пароль
                </label>
                <div class="relative">
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="Введите пароль"
                           title="Введите ваш пароль"
                           class="w-full px-4 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none @error('password') border-red-500 @enderror">
                    <button type="button"
                            onclick="togglePassword('password')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i id="password-toggle-icon" class="fas fa-eye"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🔁 Запомнить + забыли --}}
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="remember" class="rounded text-blue-600 border-gray-300 dark:border-gray-600 focus:ring-blue-500">
                    <span class="ml-2">Запомнить меня</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">Забыли пароль?</a>
            </div>

            {{-- 🚀 Кнопка входа --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-1"></i> Войти
                </button>
            </div>
        </form>

        {{-- ➕ Регистрация --}}
        <div class="text-center text-sm text-gray-600 dark:text-gray-400">
            Нет аккаунта?
            <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-semibold">Зарегистрироваться</a>
        </div>
    </div>

    <script>
        // Показ/скрытие пароля
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-toggle-icon');
            if (field && icon) {
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }
    </script>
@endsection
