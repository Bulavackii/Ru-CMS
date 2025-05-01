@extends('layouts.app')

@section('title', 'Новости')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Новости</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($news as $item)
            <div class="bg-white p-4 shadow rounded">
                <h2 class="text-xl font-semibold">
                    <a href="{{ route('news.show', $item->slug) }}" class="text-blue-600 hover:underline">
                        {{ $item->title }}
                    </a>
                </h2>
                <p class="text-gray-600 mt-2">{{ Str::limit(strip_tags($item->content), 120) }}</p>
                <p class="text-sm text-gray-400 mt-2">Категории:
                    @foreach ($item->categories as $cat)
                        <span class="text-xs bg-gray-200 rounded px-2 py-1">{{ $cat->title }}</span>
                    @endforeach
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $news->links() }}
    </div>
@endsection
