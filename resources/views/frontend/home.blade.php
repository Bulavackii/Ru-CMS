@extends('layouts.frontend')

@section('title', 'Главная')

@section('content')
    @php
        $titles = [
            'default' => 'Новости',
            'products' => 'Товары',
            'contacts' => 'Контакты',
            'gallery' => 'Галерея',
            'test' => 'Тест',
            'slideshow' => 'Слайдшоу',
            'test2' => 'Тест2',
            'faq' => 'Вопросы',
            'reviews' => 'Отзывы',
        ];
    @endphp

    {{-- 🔝 Слайдшоу, установленные в верхней части страницы --}}
    @foreach ($slideshows->where('position', 'top') as $slideshow)
        @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
    @endforeach

    {{-- 🔁 Вывод блоков по шаблонам --}}
    @foreach ($templates as $key => $newsList)
        @if ($newsList->isNotEmpty())
            @php
                $templateView = 'frontend.templates.' . $key;
            @endphp

            @if (View::exists($templateView))
                {{-- ✅ Кастомный шаблон --}}
                @include($templateView, [
                    'newsList' => $newsList,
                    'title' => $titles[$key] ?? ucfirst($key),
                ])
            @elseif ($key === 'slideshow')
                {{-- 🔁 Отображение слайдшоу, привязанных к новостям --}}
                <div class="my-8">
                    @foreach ($newsList as $news)
                        @if ($news->slideshow)
                            @include('Slideshow::public.slideshow', ['slideshow' => $news->slideshow])
                        @endif
                    @endforeach
                </div>
            @else
                {{-- 🔁 Общий шаблон карточек --}}
                <div class="mb-10">
                    <h2 class="text-2xl font-bold mb-4 text-center">{{ $titles[$key] ?? ucfirst($key) }}</h2>
                    <x-frontend.news-grid :newsList="$newsList" :title="null" />
                </div>
            @endif
        @endif
    @endforeach

    {{-- 🔻 Слайдшоу внизу страницы --}}
    @foreach ($slideshows->where('position', 'bottom') as $slideshow)
        @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
    @endforeach
@endsection
