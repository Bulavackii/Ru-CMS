@extends('layouts.frontend')

@section('title', 'FAQ — Часто задаваемые вопросы')

@section('content')
    <div class="max-w-3xl mx-auto bg-white border border-black rounded-xl p-8 shadow-lg text-sm text-gray-800 space-y-6">
        <h1 class="text-3xl font-bold text-center text-blue-900 mb-6">❓ Часто задаваемые вопросы</h1>

        <div class="space-y-4">
            <div>
                <h2 class="font-semibold text-blue-700">📌 Как зарегистрироваться на сайте?</h2>
                <p>Перейдите на страницу регистрации, заполните форму и подтвердите email.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700">🛠 Что делать, если забыли пароль?</h2>
                <p>Перейдите на страницу восстановления пароля и следуйте инструкциям в письме.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700">🏢 Можно ли зарегистрироваться как организация?</h2>
                <p>Да, при регистрации укажите, что вы юридическое лицо, и заполните данные о компании.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700">📬 Как связаться с поддержкой?</h2>
                <p>Вы можете воспользоваться <a href="{{ url('/contacts') }}" class="text-blue-600 hover:underline">страницей «Контакты»</a>.</p>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="{{ url('/') }}" class="inline-block px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded shadow transition-transform transform hover:scale-105">
                ← На главную
            </a>
        </div>
    </div>
@endsection
