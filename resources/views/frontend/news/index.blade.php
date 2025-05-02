@extends('layouts.frontend')

@section('title', 'Новости')

@section('content')
    <h1 class="text-3xl font-bold text-center mb-8">Новости</h1>

    @if ($newsList->count())
        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($newsList as $news)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-4 flex flex-col">
                    <h2 class="text-lg font-semibold mb-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:underline">
                            {{ $news->title }}
                        </a>
                    </h2>
                    <p class="text-gray-600 text-sm mb-2">
                        Категории:
                        @forelse ($news->categories as $category)
                            <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">
                                {{ $category->title }}
                            </a>
                            @if (!$loop->last)
                                ,
                            @endif
                        @empty
                            <span class="text-gray-400">Без категории</span>
                        @endforelse
                    </p>
                    <div class="text-sm text-gray-700 mb-4">
                        {!! Str::limit(strip_tags($news->content), 100) !!}
                    </div>
                    <a href="{{ route('news.show', $news->slug) }}"
                        class="mt-auto text-blue-600 hover:underline text-sm font-medium">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-center">Нет опубликованных новостей.</p>
    @endif

    <div class="mt-8">
        {{ $newsList->withQueryString()->links() }}
    </div>
@endsection
