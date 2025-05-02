@extends('layouts.app')

@section('title', $newsItem->title)

@section('content')
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold mb-4">{{ $newsItem->title }}</h1>

        <div class="text-gray-600 mb-4">
            Категории:
            @foreach ($newsItem->categories as $cat)
                <a href="{{ url('/?category=' . $cat->id) }}"
                   class="text-sm bg-gray-200 rounded px-2 py-1 mr-1 hover:bg-blue-100 inline-block">
                    {{ $cat->title }}
                </a>
            @endforeach
        </div>

        {{-- Контент новости --}}
        <div class="news-content">
            {!! $newsItem->content !!}
        </div>

        <div class="mt-6">
            <a href="{{ route('news.index') }}" class="text-blue-600 hover:underline">← Назад к списку новостей</a>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .news-content {
            width: 100%;
            overflow-wrap: break-word;
        }

        .news-content img,
        .news-content video,
        .news-content iframe {
            max-width: 100% !important;
            height: auto !important;
            display: block;
            margin: 1rem auto;
        }

        .news-content table {
            width: 100% !important;
            overflow-x: auto;
            display: block;
        }

        .news-content * {
            word-wrap: break-word;
        }
    </style>
@endpush
