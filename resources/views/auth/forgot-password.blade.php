@extends('layouts.guest')

@section('title', 'Восстановление пароля')

@section('content')
    <div class="max-w-md mx-auto bg-white border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 📘 Информационное сообщение --}}
        <div class="text-sm text-gray-700">
            Забыли пароль? Без проблем. Укажите ваш e-mail, и мы вышлем вам ссылку для сброса пароля.
        </div>

        {{-- ✅ Статус (например, "Ссылка отправлена") --}}
        @if (session('status'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded shadow-sm text-sm">
                ✅ {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            {{-- 📧 Поле ввода e-mail --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
                @error('email')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🚀 Кнопка отправки --}}
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105">
                    Отправить ссылку для сброса пароля
                </button>
            </div>
        </form>
    </div>
@endsection
