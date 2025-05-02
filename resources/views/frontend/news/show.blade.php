@extends('layouts.frontend')

@section('title', $news->title)

@section('content')
    <article class="max-w-3xl mx-auto bg-white rounded shadow p-6">
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
        <div class="prose max-w-none">
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
