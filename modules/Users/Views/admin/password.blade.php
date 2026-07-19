@extends('layouts.admin')

@section('title', 'Изменение пароля')

@section('content')
    {{-- 🔐 Заголовок --}}
    <div class="max-w-xl mx-auto bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
        <h1 class="text-2xl font-bold text-center text-gray-800 dark:text-white mb-6">
            🔒 Изменение пароля<br>
            <span class="text-base font-normal text-gray-500 dark:text-gray-400">для пользователя: <strong>{{ $user->name }}</strong></span>
        </h1>

        {{-- 📝 Форма смены пароля --}}
        <form action="{{ route('admin.users.password.update', $user->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- 🔑 Новый пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                    <i class="fas fa-lock mr-1"></i> Новый пароль
                </label>
                <input type="password" name="password" id="password" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                       placeholder="Введите новый пароль">
            </div>

            {{-- 🔁 Подтверждение --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                    <i class="fas fa-key mr-1"></i> Подтверждение пароля
                </label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                       placeholder="Повторите новый пароль">
            </div>

            {{-- 🕹️ Кнопка сохранения --}}
            <div class="text-center pt-4">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md shadow text-sm font-semibold transition">
                    <i class="fas fa-save"></i> Обновить пароль
                </button>
            </div>
        </form>
    </div>
@endsection
