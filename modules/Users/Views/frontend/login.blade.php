@extends('Users::layouts.app')

@section('title', 'Вход в аккаунт')

@section('content')
<div class="max-w-md mx-auto mt-10 bg-white dark:bg-gray-900 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
    {{-- 🧩 Заголовок --}}
    <h2 class="text-2xl font-bold text-center text-gray-800 dark:text-white mb-6">
        🔐 Вход в аккаунт
    </h2>

    {{-- ⚠️ Ошибки --}}
    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- 🔑 Форма входа --}}
    <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
        @csrf

        {{-- 📧 Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <i class="fas fa-envelope mr-1"></i> Email
            </label>
            <input type="email" name="email" id="email" required
                   placeholder="example@domain.com"
                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        {{-- 🔒 Пароль --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <i class="fas fa-lock mr-1"></i> Пароль
            </label>
            <input type="password" name="password" id="password" required
                   placeholder="Введите пароль"
                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        {{-- 🚀 Кнопка входа --}}
        <div>
            <button type="submit"
                    class="w-full bg-black hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-md shadow transition">
                <i class="fas fa-sign-in-alt mr-1"></i> Войти
            </button>
        </div>
    </form>
</div>
@endsection
