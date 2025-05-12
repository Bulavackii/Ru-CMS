@extends('layouts.admin')

@section('title', 'Добавить пользователя')

@section('content')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Добавить нового пользователя</h1>
    </div>

    <div class="max-w-4xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-black dark:text-white">Имя <i class="fas fa-user"></i></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-black transition-all duration-300"
                       placeholder="Введите имя пользователя">
                @error('name')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-black dark:text-white">Email <i class="fas fa-envelope"></i></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-black transition-all duration-300"
                       placeholder="Введите email пользователя">
                @error('email')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-black dark:text-white">Пароль <i class="fas fa-lock"></i></label>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-black transition-all duration-300"
                       placeholder="Введите пароль">
                @error('password')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-black dark:text-white">Подтверждение пароля <i class="fas fa-key"></i></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="mt-1 block w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-black transition-all duration-300"
                       placeholder="Подтвердите пароль">
            </div>

            <div class="flex justify-center">
                <button type="submit"
                        class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-300">
                    <i class="fas fa-plus-circle"></i> Создать пользователя
                </button>
            </div>
        </form>
    </div>

@endsection
