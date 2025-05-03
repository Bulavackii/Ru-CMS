@extends('layouts.frontend')

@section('title', $news->title)

@section('content')
    <article class="max-w-3xl mx-auto bg-white rounded shadow p-6 overflow-hidden">
        <h1 class="text-3xl font-bold mb-4">{{ $news->title }}</h1>

        {{-- Категории --}}
        @if ($news->categories->isNotEmpty())
            <div class="mb-4 text-sm text-gray-600">
                Категории:
                @foreach ($news->categories as $category)
                    <a href="{{ url('/?category=' . $category->id) }}"
                       class="text-blue-600 hover:underline">
                        {{ $category->title }}
                    </a>@if (!$loop->last), @endif
                @endforeach
            </div>
        @endif

        {{-- Контент --}}
        <div class="prose max-w-none news-content">
            {!! $news->content !!}
        </div>

        {{-- Назад к списку --}}
        <div class="mt-6 text-center">
            <a href="{{ route('news.index') }}" class="text-blue-600 hover:underline">
                ← Вернуться к списку новостей
            </a>
        </div>
    </article>
@endsection

@push('styles')
    <style>
        .news-content {
            word-break: break-word;
        }

        .news-content img,
        .news-content video,
        .news-content iframe {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 1rem auto;
            border-radius: 0.5rem;
        }

        .news-content table {
            width: 100%;
            overflow-x: auto;
            display: block;
        }

        .news-content * {
            word-wrap: break-word;
        }
    </style>
@endpush
