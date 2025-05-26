@extends('layouts.frontend')

@section('title', 'Контакты Ru-CMS')

@section('content')
    <section class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl shadow-lg p-8 md:p-12 space-y-10">
        {{-- 🔷 Заголовок --}}
        <div class="text-center">
            <h1 class="text-4xl font-extrabold text-blue-800 mb-2">📞 Свяжитесь с командой Ru-CMS</h1>
            <p class="text-gray-600 text-base">Мы готовы помочь, обсудить проект или проконсультировать вас по возможностям системы.</p>
        </div>

        {{-- 📍 Контактные данные --}}
        <div class="grid sm:grid-cols-2 gap-10 text-gray-800 text-[15px]">
            {{-- 📍 Адрес --}}
            <div class="flex items-start gap-4">
                <span class="text-blue-600 text-xl mt-1">📍</span>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">Офис</h2>
                    <p>г. Москва, ул. Примерная, д. 123, офис 45</p>
                </div>
            </div>

            {{-- ✉️ Email --}}
            <div class="flex items-start gap-4">
                <span class="text-blue-600 text-xl mt-1">✉️</span>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">Email</h2>
                    <a href="mailto:support@russiacms.ru" class="text-blue-600 hover:underline">support@russiacms.ru</a>
                    <p class="text-gray-500 text-xs mt-1">Отвечаем в течение рабочего дня</p>
                </div>
            </div>

            {{-- 📞 Телефон --}}
            <div class="flex items-start gap-4">
                <span class="text-blue-600 text-xl mt-1">📞</span>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">Телефон</h2>
                    <a href="tel:+74951234567" class="text-blue-600 hover:underline">+7 (495) 123-45-67</a>
                    <p class="text-gray-500 text-xs mt-1">С 9:00 до 18:00 по МСК</p>
                </div>
            </div>

            {{-- 🕒 Время работы --}}
            <div class="flex items-start gap-4">
                <span class="text-blue-600 text-xl mt-1">🕒</span>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">График работы</h2>
                    <p>Понедельник – Пятница: 09:00 – 18:00</p>
                    <p class="text-gray-500">Суббота, Воскресенье — выходные</p>
                </div>
            </div>
        </div>

        {{-- 🌐 Альтернативные каналы --}}
        <div class="border-t pt-8">
            <h3 class="text-lg font-semibold text-blue-700 mb-3 flex items-center gap-2">🌐 Мы в интернете</h3>
            <ul class="text-sm space-y-2 text-blue-600">
                <li>🔗 <a href="#" target="_blank" class="hover:underline">GitHub проекта</a> — репозиторий с кодом и инструкциями</li>
                <li>🧾 <a href="{{ url('/faq') }}" class="hover:underline">FAQ — ответы на популярные вопросы</a></li>
                <li>📄 <a href="{{ url('/about') }}" class="hover:underline">О Ru-CMS — особенности и преимущества</a></li>
            </ul>
        </div>

        {{-- 📨 CTA --}}
        <div class="text-center pt-6">
            <a href="{{ url('/') }}"
               class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-lg transition-transform hover:scale-105">
                ← На главную
            </a>
        </div>
    </section>
@endsection
