@extends('layouts.guest')

@section('title', 'Подтверждение E-mail')

@section('content')
    <div class="max-w-md mx-auto bg-white border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 📧 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700">
            <i class="fas fa-envelope-open-text mr-1"></i> Подтверждение E-mail
        </h2>

        {{-- ℹ️ Информация --}}
        <p class="text-gray-700 text-sm text-center leading-relaxed">
            Мы отправили письмо со ссылкой для подтверждения на ваш адрес электронной почты.
            <br class="hidden sm:block">Если вы не получили письмо — вы можете отправить его повторно.
        </p>

        {{-- ✅ Уведомление о повторной отправке --}}
        @if (session('status') == 'verification-link-sent')
            <div class="bg-green-100 text-green-800 text-sm border border-green-300 rounded px-4 py-2 text-center shadow-sm">
                <i class="fas fa-check-circle mr-1"></i> Новая ссылка была отправлена на ваш e-mail.
            </div>
        @endif

        {{-- 🔘 Действия --}}
        <div class="flex justify-center gap-4 mt-4 flex-wrap">
            {{-- 🔁 Повторная отправка письма --}}
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-1"></i> Отправить повторно
                </button>
            </form>

            {{-- 🚪 Выход --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                    <i class="fas fa-sign-out-alt mr-1"></i> Выйти
                </button>
            </form>
        </div>
    </div>
@endsection
