@extends('layouts.admin')

@section('title', 'Добавить пользователя')

@section('content')
    {{-- 🧩 Заголовок страницы (по центру) --}}
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-extrabold text-gray-800 dark:text-white">
            👤 Добавить нового пользователя
        </h1>
    </div>

    {{-- 📝 Форма добавления --}}
    <div class="max-w-3xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-xl shadow-md">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            {{-- 🧑 Имя --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-user mr-1"></i> Имя
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       placeholder="Введите имя"
                       class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                @error('name')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-envelope mr-1"></i> Email
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       placeholder="example@domain.com"
                       class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                @error('email')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🔑 Пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-lock mr-1"></i> Пароль
                </label>
                <input type="password" id="password" name="password" required
                       placeholder="Минимум 8 символов"
                       class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                @error('password')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🔁 Подтверждение пароля --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-key mr-1"></i> Подтвердите пароль
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       placeholder="Повторите пароль"
                       class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
            </div>

            {{-- 🕹️ Кнопка отправки --}}
            <div class="flex justify-center">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-5 py-2 rounded-md shadow-md text-sm font-semibold transition">
                    <i class="fas fa-user-plus"></i> Создать
                </button>
            </div>
        </form>
    </div>
@endsection
