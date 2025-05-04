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
        ];
    @endphp

    {{-- 🔁 Слайдшоу (на всю ширину, до всех блоков) --}}
    @foreach ($slideshows as $slideshow)
        @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
    @endforeach

    {{-- 🔁 Вывод блоков по шаблонам --}}
    @foreach ($templates as $key => $newsList)
    @if ($newsList->isNotEmpty())
        @php
            $templateView = 'frontend.templates.' . $key;
        @endphp

        @if (View::exists($templateView))
            {{-- ✅ Если есть кастомный шаблон --}}
            @include($templateView, [
                'newsList' => $newsList,
                'title' => $titles[$key] ?? ucfirst($key),
            ])
        @elseif ($key === 'slideshow')
            {{-- ✅ Специальный случай для слайдшоу --}}
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

@endsection
