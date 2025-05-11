@extends('layouts.guest')

@section('title', 'Вход')

@section('content')
    <div class="max-w-md mx-auto bg-white border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🧩 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700">🔐 Вход в систему</h2>

        {{-- 🔴 Ошибка при входе --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded shadow-sm text-sm">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
            </div>

            {{-- 🔒 Пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
            </div>

            {{-- 🔁 Запомнить и восстановление --}}
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                    <span class="ml-2 text-gray-700">Запомнить меня</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">Забыли пароль?</a>
            </div>

            {{-- 🚀 Кнопка входа --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                    Войти
                </button>
            </div>
        </form>

        {{-- 🔗 Ссылка на регистрацию --}}
        <div class="text-center text-sm text-gray-600">
            Нет аккаунта?
            <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-semibold">Зарегистрироваться</a>
        </div>
    </div>
@endsection
