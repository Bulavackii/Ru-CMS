@extends('layouts.frontend')

@section('title', 'Карта сайта')

@push('head')
  <meta name="description" content="Быстрая навигация по основным страницам и разделам сайта Ru-CMS: главная, новости, услуги, выполненные работы, прайс-лист, контакты и др." />
@endpush

@section('content')
<section class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl shadow-xl p-8 md:p-12 text-[15px] leading-relaxed text-gray-800 space-y-8">
    <h1 class="text-3xl font-extrabold text-blue-800 text-center">🗺️ Карта сайта</h1>

    <p class="text-center text-gray-600">
        Быстрая навигация по основным страницам и разделам сайта Ru-CMS
    </p>

    {{-- Основные страницы --}}
    <div class="grid sm:grid-cols-2 gap-6 mt-6">
        @php
            $pages = [
                ['url' => '/',                     'icon' => '🏠', 'title' => 'Главная'],
                ['url' => '/news',                 'icon' => '📰', 'title' => 'Новости'],
                ['url' => '/?category_ourworks=2', 'icon' => '🧰', 'title' => 'Выполненные работы'],
                ['url' => '/page/prajs-list',      'icon' => '💼', 'title' => 'Прайс-лист'],
                ['url' => '/about',                'icon' => '📘', 'title' => 'О компании'],
                ['url' => '/faq',                  'icon' => '❓', 'title' => 'FAQ'],
                ['url' => '/contacts',             'icon' => '📞', 'title' => 'Контакты'],
                ['url' => '/privacy',              'icon' => '🔐', 'title' => 'Политика конфиденциальности'],
                ['url' => '/terms',                'icon' => '📑', 'title' => 'Пользовательское соглашение'],
                ['url' => '/concept',              'icon' => '💡', 'title' => 'Концепция'],
                ['url' => '/partnership',          'icon' => '🤝', 'title' => 'Сотрудничество'],
                ['url' => '/developers',           'icon' => '👨‍💻', 'title' => 'Разработчикам'],
                ['url' => '/donate',               'icon' => '💸', 'title' => 'Пожертвования'],
                ['url' => '/search',               'icon' => '🔍', 'title' => 'Поиск'],
                ['url' => '/login',                'icon' => '🔐', 'title' => 'Вход'],
                ['url' => '/register',             'icon' => '📝', 'title' => 'Регистрация'],
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

    {{-- Подсказка --}}
    <div class="mt-8 text-center text-sm text-gray-500">
        Контент разделов может обновляться и дополняться — заглядывайте почаще.
    </div>

    {{-- Назад --}}
    <div class="text-center mt-10">
        <a href="{{ url('/') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow transition">
            ← На главную
        </a>
    </div>
</section>
@endsection
