@extends('layouts.frontend')

@section('title', $news->title)

@section('content')
    <article class="max-w-3xl mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-lg px-6 py-8 transition-all duration-300 overflow-hidden">
        {{-- Заголовок --}}
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white leading-tight break-words mb-6">
            {{ $news->title }}
        </h1>

        {{-- Категории --}}
        @if ($news->categories->isNotEmpty())
            <div class="mb-4 text-sm flex flex-wrap gap-2">
                @foreach ($news->categories as $category)
                    <a href="{{ url('/?category=' . $category->id) }}"
                       class="inline-block bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-xs font-medium hover:underline transition">
                        {{ $category->title }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Контент --}}
        <div class="prose prose-sm sm:prose lg:prose-lg max-w-none news-content text-gray-800 dark:text-gray-100">
            {!! $news->content !!}
        </div>

        {{-- Назад --}}
        <div class="mt-10 text-center">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline transition">
                <i class="fas fa-arrow-left"></i> Назад
            </a>
        </div>
    </article>
@endsection

@push('styles')
    <style>
        .news-content {
            word-break: break-word;
        }

        .news-content * {
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .news-content img,
        .news-content video,
        .news-content iframe {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 1.5rem auto;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .news-content table {
            display: block;
            width: 100%;
            overflow-x: auto;
            border-collapse: collapse;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .news-content table th,
        .news-content table td {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
        }

        .news-content h1, .news-content h2, .news-content h3 {
            font-weight: 700;
            margin-top: 1.5rem;
        }

        @media (max-width: 640px) {
            .news-content img,
            .news-content video,
            .news-content iframe {
                max-width: 100%;
                height: auto;
            }
        }
    </style>
@endpush
