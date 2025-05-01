@extends('layouts.app')

@section('title', $newsItem->title)

@section('content')
    <h1 class="text-3xl font-bold mb-4">{{ $newsItem->title }}</h1>

    <div class="text-gray-600 mb-4">
        Категории:
        @foreach ($newsItem->categories as $cat)
            <span class="text-sm bg-gray-200 rounded px-2 py-1">{{ $cat->title }}</span>
        @endforeach
    </div>

    <div class="prose">
        {!! $newsItem->content !!}
    </div>

    <div class="mt-6">
        <a href="{{ route('news.index') }}" class="text-blue-600 hover:underline">← Назад к списку новостей</a>
    </div>
@endsection
