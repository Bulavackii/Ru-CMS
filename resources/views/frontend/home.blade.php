@extends('layouts.frontend')

@section('title', 'Главная')

@section('content')
    @php
        $titles = [
            'default'   => 'Новости',
            'products'  => 'Товары',
            'contacts'  => 'Контакты',
            'gallery'   => 'Галерея',
            'test'      => 'Тест',
            'slideshow' => 'Слайдшоу',
            'faq'       => 'Вопросы',
            'reviews'   => 'Отзывы',
        ];
    @endphp

    {{-- 🔝 Верхние слайдшоу --}}
    @foreach ($slideshows->where('position', 'top') as $slideshow)
        @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
    @endforeach

    {{-- 🔁 Вывод ВСЕХ шаблонов --}}
    @foreach ($templates as $key => $newsList)
        @if ($newsList->isEmpty())
            @continue
        @endif

        @php $templateView = 'frontend.templates.' . $key; @endphp

        {{-- ✅ Проверка существования кастомного шаблона --}}
        @if (View::exists($templateView))
            @include($templateView, [
                'newsList' => $newsList,
                'title' => $titles[$key] ?? ucfirst($key),
            ])
        @elseif ($key === 'slideshow')
            {{-- 🖼️ Отдельный случай slideshow --}}
            <div class="my-8">
                @foreach ($newsList as $news)
                    @if ($news->slideshow)
                        @include('Slideshow::public.slideshow', ['slideshow' => $news->slideshow])
                    @endif
                @endforeach
            </div>
        @else
            {{-- 📰 Общий шаблон для всех остальных --}}
            <div class="mb-10">
                <h2 class="text-2xl font-bold mb-4 text-center">{{ $titles[$key] ?? ucfirst($key) }}</h2>
                <x-frontend.news-grid :newsList="$newsList" :title="null" />
            </div>
        @endif
    @endforeach

    {{-- 🔻 Нижние слайдшоу --}}
    @foreach ($slideshows->where('position', 'bottom') as $slideshow)
        @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
    @endforeach
@endsection
