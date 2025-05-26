@extends('layouts.frontend')

@section('title', 'Карта сайта')

@section('content')
<section class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl shadow-xl p-8 md:p-12 text-[15px] leading-relaxed text-gray-800 space-y-8">
    <h1 class="text-3xl font-extrabold text-blue-800 text-center">🗺️ Карта сайта</h1>

    <p class="text-center text-gray-600">Быстрая навигация по основным страницам и разделам сайта Ru-CMS</p>

    {{-- Основные разделы --}}
    <div class="grid sm:grid-cols-2 gap-6 mt-6">
        @php
            $pages = [
                ['url' => '/', 'icon' => '🏠', 'title' => 'Главная'],
                ['url' => '/about', 'icon' => '📘', 'title' => 'О Ru-CMS'],
                ['url' => '/faq', 'icon' => '❓', 'title' => 'FAQ'],
                ['url' => '/contacts', 'icon' => '📞', 'title' => 'Контакты'],
                ['url' => '/privacy', 'icon' => '🔐', 'title' => 'Политика конфиденциальности'],
                ['url' => '/terms', 'icon' => '📑', 'title' => 'Пользовательское соглашение'],
                ['url' => '/concept', 'icon' => '📄', 'title' => 'Концепция'],
                ['url' => '/cooperation', 'icon' => '🤝', 'title' => 'Сотрудничество'],
                ['url' => '/developers', 'icon' => '👨‍💻', 'title' => 'Разработчикам'],
                ['url' => '/donate', 'icon' => '💸', 'title' => 'Пожертвования'],
                ['url' => '/login', 'icon' => '🔐', 'title' => 'Вход'],
                ['url' => '/register', 'icon' => '📝', 'title' => 'Регистрация'],
                ['url' => '/search', 'icon' => '🔍', 'title' => 'Поиск'],
            ];
        @endphp

        @foreach ($pages as $page)
            <a href="{{ url($page['url']) }}"
               class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 shadow-sm hover:bg-blue-50 transition text-gray-800 hover:text-blue-800">
                <span class="text-xl">{{ $page['icon'] }}</span>
                <span class="font-semibold">{{ $page['title'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Дополнительно --}}
    <div class="mt-8 text-center text-sm text-gray-500">
        Обратите внимание: контент разделов может обновляться и дополняться. Следите за новостями!
    </div>

    {{-- Назад --}}
    <div class="text-center mt-10">
        <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow transition">
            ← На главную
        </a>
    </div>
</section>
@endsection
