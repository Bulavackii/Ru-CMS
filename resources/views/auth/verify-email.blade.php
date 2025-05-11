@extends('layouts.guest')

@section('title', 'Подтверждение E-mail')

@section('content')
    <div class="max-w-md mx-auto bg-white border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 📧 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700">📧 Подтверждение E-mail</h2>

        {{-- ℹ️ Информация --}}
        <p class="text-gray-700 text-sm text-center leading-relaxed">
            Спасибо за регистрацию! Пожалуйста, подтвердите свой адрес электронной почты, перейдя по ссылке, которую мы отправили.
            <br class="hidden sm:block"> Если вы не получили письмо, вы можете запросить повторную отправку.
        </p>

        {{-- ✅ Уведомление об отправке новой ссылки --}}
        @if (session('status') == 'verification-link-sent')
            <div class="bg-green-100 text-green-800 text-sm border border-green-300 rounded px-4 py-2 text-center shadow-sm">
                ✅ Новая ссылка была отправлена на ваш e-mail.
            </div>
        @endif

        {{-- 🔘 Действия --}}
        <div class="flex justify-center gap-4 mt-4">
            {{-- 🔁 Повторная отправка письма --}}
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                    Отправить повторно
                </button>
            </form>

            {{-- 🚪 Выход --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                    Выйти
                </button>
            </form>
        </div>
    </div>
@endsection
