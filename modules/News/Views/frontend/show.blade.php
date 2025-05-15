@extends('layouts.app')

@section('title', $newsItem->title)

@section('content')
<main class="flex-grow py-10 relative">
    {{-- 🌄 Фоновое изображение как в header --}}
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
         style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;"></div>

    {{-- 📄 Контент поверх фона --}}
    <div class="relative z-10">
        <div class="max-w-3xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-md rounded-2xl shadow-lg px-6 py-8 transition-all duration-300 overflow-hidden">
            {{-- Заголовок --}}
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white leading-tight break-words mb-6 text-center">
                {{ $newsItem->title }}
            </h1>

            {{-- Категории --}}
            @if ($newsItem->categories->isNotEmpty())
                <div class="mb-4 text-sm flex flex-wrap gap-2 justify-center">
                    @foreach ($newsItem->categories as $cat)
                        <a href="{{ url('/?category=' . $cat->id) }}"
                           class="inline-block bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-xs font-medium hover:underline transition">
                            {{ $cat->title }}
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-400 text-sm mb-4">Без категории</p>
            @endif

            {{-- Контент --}}
            <div class="news-content prose prose-sm sm:prose lg:prose-lg max-w-none text-gray-800 dark:text-gray-100 mb-8">
                {!! $newsItem->content !!}
            </div>

            {{-- Слайдшоу --}}
            @if ($newsItem->slideshow && $newsItem->slideshow->items->count())
                <div class="mt-8">
                    @include('Slideshow::public.slideshow', ['slideshow' => $newsItem->slideshow])
                </div>
            @endif

            {{-- Назад --}}
            <div class="text-center mt-10">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-2.5 rounded-lg font-medium text-sm shadow-md transition">
                    ⬅️ На главную
                </a>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
    .news-content {
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: break-word;
    }

    .news-content img,
    .news-content video,
    .news-content iframe,
    .news-content embed,
    .news-content object {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 1rem auto;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .news-content table {
        width: 100%;
        overflow-x: auto;
        display: block;
    }

    .news-content pre {
        white-space: pre-wrap;
        word-break: break-word;
    }

    .news-content a {
        word-break: break-word;
    }
</style>
@endpush
