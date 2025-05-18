@extends('layouts.app')

@section('title', $newsItem->title)

@section('content')
    <div class="max-w-4xl mx-auto px-4 overflow-hidden">
        <h1 class="text-3xl font-bold mb-4 break-words">{{ $newsItem->title }}</h1>

        <div class="text-gray-600 mb-4 flex flex-wrap items-center gap-2">
            Категории:
            @forelse ($newsItem->categories as $cat)
                <a href="{{ url('/?category=' . $cat->id) }}"
                   class="text-sm bg-gray-200 rounded px-2 py-1 hover:bg-blue-100">
                    {{ $cat->title }}
                </a>
            @empty
                <span class="text-sm text-gray-400">Без категории</span>
            @endforelse
        </div>

        {{-- Контент --}}
        <div class="news-content prose max-w-none">
            {!! $newsItem->content !!}
        </div>

        {{-- Слайдшоу --}}
        @if ($newsItem->slideshow && $newsItem->slideshow->items->count())
            <div class="mt-8">
                @include('Slideshow::public.slideshow', ['slideshow' => $newsItem->slideshow])
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('news.index') }}" class="text-blue-600 hover:underline">← Назад к списку новостей</a>
        </div>
    </div>
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
