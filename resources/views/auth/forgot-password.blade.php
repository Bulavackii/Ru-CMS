@extends('layouts.guest')

@section('title', 'Восстановление пароля')

@section('content')
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 📨 Заголовок --}}
        <h1 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <i class="fas fa-envelope-open-text text-blue-600"></i> Восстановление пароля
        </h1>

        {{-- 📘 Инфо --}}
        <p class="text-sm text-gray-700 dark:text-gray-300">
            Забыли пароль? Укажите ваш e-mail — мы вышлем ссылку для сброса.
        </p>

        {{-- ✅ Сообщение об успешной отправке --}}
        @if (session('status'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded shadow-sm text-sm flex items-center gap-2">
                <i class="fas fa-check-circle text-green-600"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- 🔁 Форма --}}
        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-envelope mr-1"></i> E-mail
                </label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       placeholder="Введите ваш e-mail"
                       title="Введите почту, с которой регистрировались"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Мы отправим ссылку для сброса пароля на этот адрес.
                </p>

                @error('email')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🚀 Кнопка --}}
            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-md shadow transition transform hover:scale-105"
                        title="Отправить письмо для сброса">
                    <i class="fas fa-paper-plane"></i> Отправить ссылку
                </button>
            </div>
        </form>
    </div>
@endsection
