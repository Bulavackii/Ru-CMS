@extends('layouts.frontend')

@section('title', 'FAQ — Часто задаваемые вопросы')

@section('content')
    <div class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl p-8 md:p-10 shadow-xl text-[15px] text-gray-800 space-y-8">
        {{-- 🧠 Заголовок --}}
        <h1 class="text-3xl font-extrabold text-center text-blue-800">❓ Часто задаваемые вопросы (FAQ)</h1>
        <p class="text-center text-gray-600 text-sm -mt-3">Нужна помощь? Здесь собраны ответы на самые популярные вопросы по работе с Ru-CMS</p>

        {{-- 🔍 Вопросы --}}
        <div class="space-y-6">
            <div>
                <h2 class="font-semibold text-blue-700 text-lg">📌 Как зарегистрироваться на сайте?</h2>
                <p>Нажмите <a href="{{ route('register') }}" class="text-blue-600 hover:underline">«Регистрация»</a> в верхнем меню. Заполните форму и подтвердите email.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🛠 Я забыл(а) пароль. Что делать?</h2>
                <p>Перейдите на <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">страницу восстановления пароля</a>, введите ваш email — и получите инструкцию на почту.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🏢 Можно ли зарегистрироваться как организация?</h2>
                <p>Да, при регистрации выберите тип «Юридическое лицо» — появятся поля для ИНН, ОГРН и адреса.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🧩 Где управлять модулями?</h2>
                <p>В админке на странице <a href="{{ url('/admin/modules') }}" class="text-blue-600 hover:underline">Модули</a> вы можете включать, отключать, архивировать и скачивать ZIP-архивы модулей.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🎨 Как подключить свой шаблон?</h2>
                <p>Создайте файл шаблона в директории: <code class="bg-gray-100 px-2 py-1 rounded text-xs">resources/views/frontend/templates/название.blade.php</code>. Он автоматически появится в списке при создании новости.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🖼️ Можно ли использовать видео и изображения?</h2>
                <p>Да! Вы можете загружать медиафайлы при создании записи (TinyMCE) или использовать <a href="{{ url('/admin/files') }}" class="text-blue-600 hover:underline">менеджер файлов</a> в админке.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🔒 Насколько безопасна Ru-CMS?</h2>
                <p>Ru-CMS использует <strong>bcrypt</strong> для паролей, <strong>JWT</strong> для API-аутентификации и политику разделения ролей.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">⚙️ Как обновить информацию о себе?</h2>
                <p>Зайдите в <a href="{{ route('dashboard.edit') }}" class="text-blue-600 hover:underline">личный кабинет</a>, чтобы отредактировать имя, email, пароль и другие данные.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">📬 Как обратиться в поддержку?</h2>
                <p>Вы можете заполнить форму на <a href="{{ url('/contacts') }}" class="text-blue-600 hover:underline">странице «Контакты»</a> или отправить сообщение через модуль «Сообщения» в админке.</p>
            </div>
        </div>

        {{-- 📚 База знаний --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-6 mt-12 space-y-4 shadow-sm">
            <h3 class="text-lg font-semibold text-blue-700 flex items-center gap-2">
                📚 База знаний и документация
            </h3>
            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                <li><a href="{{ url('/about') }}" class="text-blue-600 hover:underline">Что такое Ru-CMS и как она работает?</a></li>
                <li><a href="{{ url('/faq') }}" class="text-blue-600 hover:underline">Настройка шаблонов, блоков и категорий</a></li>
                <li><a href="{{ url('/contacts') }}" class="text-blue-600 hover:underline">Как получить помощь и поддержку</a></li>
            </ul>
        </div>

        {{-- 🔙 Кнопка назад --}}
        <div class="text-center pt-10">
            <a href="{{ url('/') }}"
               class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow hover:scale-105 transition-all">
                <i class="fas fa-arrow-left mr-2"></i> На главную
            </a>
        </div>
    </div>
@endsection
