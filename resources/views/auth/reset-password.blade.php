@extends('layouts.guest')

@section('title', 'Сброс пароля')

@section('content')
    <div class="max-w-md mx-auto bg-white border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🧩 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700">🔑 Сброс пароля</h2>

        {{-- 🔴 Ошибка валидации --}}
        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded shadow-sm text-sm">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            {{-- 🔐 Скрытый токен сброса --}}
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email', $request->email) }}"
                       required
                       autofocus
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
            </div>

            {{-- 🔒 Новый пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Новый пароль</label>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="new-password"
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
            </div>

            {{-- 🔁 Повтор пароля --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Подтвердите пароль</label>
                <input id="password_confirmation"
                       type="password"
                       name="password_confirmation"
                       required
                       autocomplete="new-password"
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
            </div>

            {{-- ✅ Кнопка сброса --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                    Сбросить пароль
                </button>
            </div>
        </form>
    </div>
@endsection
