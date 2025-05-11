@extends('layouts.guest')

@section('title', 'Подтверждение пароля')

@section('content')
    <div class="max-w-md mx-auto bg-white border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">
        {{-- 🔒 Информационное сообщение --}}
        <div class="text-sm text-gray-700">
            Это защищённая часть приложения. Пожалуйста, подтвердите ваш пароль для продолжения.
        </div>

        {{-- 🔁 Форма подтверждения --}}
        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            {{-- 🔑 Пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
                @error('password')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- ✅ Кнопка подтверждения --}}
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105">
                    Подтвердить
                </button>
            </div>
        </form>
    </div>
@endsection
